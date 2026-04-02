<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SOSService
{
    private Client $client;
    private CookieJar $cookies;
    private bool $authenticated = false;
    private string $viewState = '';
    private string $inicioHtml = '';
    private string $consultaPageHtml = '';
    private string $logPrefix;

    public function __construct()
    {
        $this->logPrefix = date('Y-m-d_His') . '_' . substr(uniqid(), -5);
        $this->cookies = new CookieJar();
        $this->client = new Client([
            'cookies'         => $this->cookies,
            'verify'          => false,
            'timeout'         => config('sos.timeout', 30),
            'allow_redirects' => ['max' => 5, 'track_redirects' => true],
            'headers'         => [
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'es-CO,es;q=0.9,en;q=0.8',
            ],
        ]);
    }

    private function saveHtmlLog(string $step, string $html, array $extra = []): void
    {
        $dir = 'sos_logs/' . date('Y-m-d');
        Storage::makeDirectory($dir);

        $filename = "{$dir}/{$this->logPrefix}_{$step}.html";
        Storage::put($filename, $html);

        if (!empty($extra)) {
            $metaFile = "{$dir}/{$this->logPrefix}_{$step}_meta.json";
            Storage::put($metaFile, json_encode($extra, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        Log::channel('sos')->info("SOS [{$step}]: HTML guardado en storage/app/{$filename} (" . strlen($html) . " bytes)", $extra);
    }

    public function login(): bool
    {
        try {
            // 1. GET login page to get JSESSIONID cookie
            Log::channel('sos')->info('SOS: Iniciando login - GET ' . config('sos.login_url'));
            $response = $this->client->get(config('sos.login_url'));
            $html = (string) $response->getBody();
            $statusCode = $response->getStatusCode();

            Log::channel('sos')->info("SOS: Login page GET - Status: {$statusCode}, Size: " . strlen($html));
            $this->saveHtmlLog('01_login_page', $html, [
                'status_code' => $statusCode,
                'cookies'     => $this->getCookiesArray(),
            ]);

            // This portal uses j_security_check (Java EE container auth), NOT JSF ViewState
            // Form action: /ValidadorWeb2/j_security_check
            // Fields: j_username, j_password
            $loginPostUrl = config('sos.base_url') . '/j_security_check';

            $postData = [
                'j_username' => config('sos.username'),
                'j_password' => config('sos.password'),
            ];

            Log::channel('sos')->info("SOS: POST login a {$loginPostUrl}", [
                'j_username' => $postData['j_username'],
                'j_password' => '***HIDDEN***',
                'cookies'    => $this->getCookiesArray(),
            ]);

            // 2. POST credentials to j_security_check
            $response = $this->client->post($loginPostUrl, [
                'form_params' => $postData,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Referer'      => config('sos.login_url'),
                    'Origin'       => 'https://centralaplicaciones.sos.com.co',
                ],
            ]);

            $responseHtml = (string) $response->getBody();
            $statusCode = $response->getStatusCode();

            Log::channel('sos')->info("SOS: Login POST response - Status: {$statusCode}, Size: " . strlen($responseHtml));
            $this->saveHtmlLog('02_login_response', $responseHtml, [
                'status_code'    => $statusCode,
                'cookies'        => $this->getCookiesArray(),
                'effective_url'  => (string) ($response->getHeader('X-Guzzle-Redirect-History')[0] ?? ''),
                'response_headers' => array_map(fn($v) => implode(', ', $v), $response->getHeaders()),
            ]);

            // 3. Check if login was successful
            if (str_contains($responseHtml, 'Consultar Afiliado') || str_contains($responseHtml, 'Cerrar Sesion') || str_contains($responseHtml, 'Opciones')) {
                $this->authenticated = true;
                $this->inicioHtml = $responseHtml;
                Log::channel('sos')->info('SOS: Login exitoso (detectado en respuesta POST)');
                return true;
            }

            // 4. Try navigating to inicio page (might have been redirected)
            Log::channel('sos')->info('SOS: POST no mostró menú, intentando GET inicio...');
            $response = $this->client->get(config('sos.inicio_url'));
            $responseHtml = (string) $response->getBody();

            $this->saveHtmlLog('03_inicio_page', $responseHtml, [
                'status_code' => $response->getStatusCode(),
                'cookies'     => $this->getCookiesArray(),
            ]);

            if (str_contains($responseHtml, 'Consultar Afiliado') || str_contains($responseHtml, 'Cerrar Sesion') || str_contains($responseHtml, 'Opciones')) {
                $this->authenticated = true;
                $this->inicioHtml = $responseHtml;
                Log::channel('sos')->info('SOS: Login exitoso (redirect a inicio)');
                return true;
            }

            // 5. Also try the base URL
            Log::channel('sos')->info('SOS: Intentando GET base URL...');
            $response = $this->client->get(config('sos.base_url') . '/');
            $responseHtml = (string) $response->getBody();

            $this->saveHtmlLog('04_base_url', $responseHtml, [
                'status_code' => $response->getStatusCode(),
                'cookies'     => $this->getCookiesArray(),
            ]);

            if (str_contains($responseHtml, 'Consultar Afiliado') || str_contains($responseHtml, 'Cerrar Sesion') || str_contains($responseHtml, 'Opciones')) {
                $this->authenticated = true;
                $this->inicioHtml = $responseHtml;
                Log::channel('sos')->info('SOS: Login exitoso (base URL)');
                return true;
            }

            Log::channel('sos')->error('SOS: Login falló - no se detectó menú de opciones en ninguna página');
            return false;
        } catch (\Exception $e) {
            Log::channel('sos')->error('SOS: Error en login: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return false;
        }
    }

    public function consultarCedula(string $cedula, string $tipoId = 'CC'): array
    {
        if (!$this->authenticated && !$this->login()) {
            return ['error' => 'No se pudo autenticar en el portal SOS'];
        }

        try {
            // 1. Navigate to consulta page via JSF menu (required by portal)
            // Reuse cached page for subsequent calls in the same session
            if (!empty($this->consultaPageHtml)) {
                Log::channel('sos')->info("SOS: Reutilizando página de consulta cacheada para {$cedula}");
                $html = $this->consultaPageHtml;
            } else {
                Log::channel('sos')->info("SOS: Consultando cédula {$cedula} - Navegando a página de consulta");
                $html = $this->navigateToConsulta();

                if (empty($html)) {
                    // Session might have expired — try re-login once
                    Log::channel('sos')->warning("SOS: Navegación vacía, reintentando login para {$cedula}");
                    $this->authenticated = false;
                    $this->inicioHtml = '';
                    $this->consultaPageHtml = '';
                    if (!$this->login()) {
                        return ['error' => 'No se pudo re-autenticar en el portal SOS'];
                    }
                    $html = $this->navigateToConsulta();
                    if (empty($html)) {
                        return ['error' => 'No se pudo acceder a la página de consulta después de re-login'];
                    }
                }

                $this->saveHtmlLog("05_consulta_page_{$cedula}", $html, [
                    'cedula' => $cedula,
                    'size'   => strlen($html),
                ]);

                // Check if we got the error page (access denied)
                if (str_contains($html, 'Error de Acceso') || str_contains($html, 'no está permitida')) {
                    Log::channel('sos')->warning("SOS: Acceso bloqueado, reintentando con re-login para {$cedula}");
                    // Clear everything and re-authenticate
                    $this->authenticated = false;
                    $this->inicioHtml = '';
                    $this->consultaPageHtml = '';
                    $this->cookies = new CookieJar();
                    $this->client = new Client([
                        'cookies'         => $this->cookies,
                        'verify'          => false,
                        'timeout'         => config('sos.timeout', 30),
                        'allow_redirects' => ['max' => 5, 'track_redirects' => true],
                        'headers'         => [
                            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                            'Accept-Language' => 'es-CO,es;q=0.9,en;q=0.8',
                        ],
                    ]);
                    if (!$this->login()) {
                        return ['error' => 'No se pudo re-autenticar en el portal SOS'];
                    }
                    $html = $this->navigateToConsulta();
                    if (empty($html) || str_contains($html, 'Error de Acceso')) {
                        Log::channel('sos')->error("SOS: Acceso seguido bloqueado después de re-login para {$cedula}");
                        return ['error' => 'Acceso bloqueado a la página de consulta. El portal rechazó la navegación.'];
                    }
                }

                // Cache for subsequent calls
                $this->consultaPageHtml = $html;
            }

            $this->viewState = $this->extractViewState($html);

            if (empty($this->viewState)) {
                Log::channel('sos')->error("SOS: No ViewState en página de consulta para {$cedula}");
                return ['error' => 'No se pudo obtener ViewState de la página de consulta'];
            }

            // 2. Build and POST consultation form
            $formData = $this->buildConsultaFormData($html, $cedula, $tipoId);

            Log::channel('sos')->info("SOS: POST consulta cédula {$cedula} con datos:", $formData);
            $this->saveHtmlLog("06_consulta_post_data_{$cedula}", json_encode($formData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $response = $this->client->post(config('sos.consulta_url'), [
                'form_params' => $formData,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Referer'      => config('sos.consulta_url'),
                ],
                'http_errors' => false,
            ]);

            $responseHtml = (string) $response->getBody();
            $statusCode = $response->getStatusCode();

            Log::channel('sos')->info("SOS: Consulta response para {$cedula} - Status: {$statusCode}, Size: " . strlen($responseHtml));
            $this->saveHtmlLog("07_consulta_response_{$cedula}", $responseHtml, [
                'cedula'      => $cedula,
                'status_code' => $statusCode,
            ]);

            // Update cached consulta page and ViewState for next cedula
            // After a successful query, we need to click "Nueva Consulta" to reset the form
            $newViewState = $this->extractViewState($responseHtml);
            if (!empty($newViewState)) {
                $this->viewState = $newViewState;

                // Submit "Nueva Consulta" to get a fresh empty form for the next cedula
                $nuevaConsultaHtml = $this->submitNuevaConsulta($responseHtml);
                if ($nuevaConsultaHtml && $this->isConsultaPage($nuevaConsultaHtml)) {
                    $this->consultaPageHtml = $nuevaConsultaHtml;
                    Log::channel('sos')->info("SOS: Nueva Consulta exitosa, formulario reseteado");
                } else {
                    // Fallback: clear cache so next call re-navigates
                    $this->consultaPageHtml = '';
                    Log::channel('sos')->warning("SOS: Nueva Consulta falló, se re-navegará en la próxima consulta");
                }
            }

            // 3. Parse results
            $result = $this->parseConsultaResponse($responseHtml, $cedula);

            Log::channel('sos')->info("SOS: Resultado parseado para {$cedula}:", $result);

            // Delay between requests
            $delay = config('sos.delay', 1500);
            if ($delay > 0) {
                usleep($delay * 1000);
            }

            return $result;
        } catch (\Exception $e) {
            Log::channel('sos')->error("SOS: Error consultando cédula {$cedula}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ['error' => 'Error en la consulta: ' . $e->getMessage()];
        }
    }

    /**
     * Submit "Nueva Consulta" button to reset the form for the next query.
     * The response page has: <input type="submit" name="afiliadoForm:j_id191" value="Nueva Consulta" class="nueva" />
     */
    private function submitNuevaConsulta(string $responseHtml): ?string
    {
        try {
            // Find the "Nueva Consulta" button name
            $buttonName = 'afiliadoForm:j_id191'; // default
            if (preg_match('/<input[^>]*name="([^"]*)"[^>]*value="Nueva Consulta"[^>]*>/i', $responseHtml, $m)) {
                $buttonName = $m[1];
            } elseif (preg_match('/<input[^>]*value="Nueva Consulta"[^>]*name="([^"]*)"[^>]*>/i', $responseHtml, $m)) {
                $buttonName = $m[1];
            }

            $viewState = $this->extractViewState($responseHtml);
            $formData = [
                'afiliadoForm'          => 'afiliadoForm',
                'javax.faces.ViewState' => $viewState,
                $buttonName             => 'Nueva Consulta',
            ];

            // Add hidden inputs from the response page
            $this->addHiddenInputs($responseHtml, $formData, 'afiliadoForm');

            Log::channel('sos')->info("SOS: POST Nueva Consulta con button: {$buttonName}");

            $response = $this->client->post(config('sos.consulta_url'), [
                'form_params' => $formData,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Referer'      => config('sos.consulta_url'),
                ],
                'http_errors' => false,
            ]);

            $html = (string) $response->getBody();
            Log::channel('sos')->info("SOS: Nueva Consulta response - Status: {$response->getStatusCode()}, Size: " . strlen($html));

            return $html;
        } catch (\Exception $e) {
            Log::channel('sos')->error("SOS: Error en Nueva Consulta: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Navigate to consulta page through JSF menu.
     * Uses multiple strategies to bypass the portal's navigation restriction.
     */
    private function navigateToConsulta(): ?string
    {
        try {
            // Ensure we have the inicio page HTML
            if (empty($this->inicioHtml)) {
                Log::channel('sos')->info('SOS: Obteniendo página de inicio para navegación');
                $response = $this->client->get(config('sos.inicio_url'));
                $this->inicioHtml = (string) $response->getBody();
                $this->saveHtmlLog('nav_00_inicio', $this->inicioHtml, [
                    'status_code' => $response->getStatusCode(),
                ]);
            }

            // Extract navigation parameters from "Consultar Afiliado" link
            $navData = $this->extractConsultaNavParams($this->inicioHtml);
            if (!$navData) {
                Log::channel('sos')->error('SOS: No se encontraron parámetros de navegación');
                return null;
            }

            // === Strategy 1: Regular form POST (like jsfcljs does in the browser) ===
            // The browser's jsfcljs function does a regular form.submit(), NOT an AJAX call
            // This is the standard JSF navigation mechanism
            $formData = $navData['formData'];
            // Remove AJAXREQUEST - we want a regular form submit, not AJAX
            unset($formData['AJAXREQUEST']);

            Log::channel('sos')->info('SOS: Strategy 1 - Regular form POST to inicio.jsf', $formData);

            $response = $this->client->post(config('sos.inicio_url'), [
                'form_params'    => $formData,
                'headers'        => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Referer'      => config('sos.inicio_url'),
                    'Origin'       => 'https://centralaplicaciones.sos.com.co',
                ],
                'allow_redirects' => ['max' => 5, 'track_redirects' => true],
            ]);

            $html = (string) $response->getBody();
            $statusCode = $response->getStatusCode();

            $this->saveHtmlLog('nav_01_form_post', $html, [
                'status_code'      => $statusCode,
                'size'             => strlen($html),
                'redirect_history' => $response->getHeader('X-Guzzle-Redirect-History'),
                'redirect_status'  => $response->getHeader('X-Guzzle-Redirect-Status-History'),
            ]);

            // Check if we got the consulta page (must have numId/tipoId form fields)
            if ($this->isConsultaPage($html)) {
                Log::channel('sos')->info('SOS: Strategy 1 exitosa - Página de consulta obtenida (' . strlen($html) . ' bytes)');
                return $html;
            }

            Log::channel('sos')->warning('SOS: Strategy 1 no obtuvo consulta (got ' . strlen($html) . ' bytes, isConsulta=false). Intentando Strategy 2...');

            // === Strategy 2: A4J AJAX POST + follow the XML redirect with proper Referer ===
            $ajaxFormData = $navData['formData']; // has AJAXREQUEST

            $response = $this->client->post(config('sos.inicio_url'), [
                'form_params' => $ajaxFormData,
                'headers'     => [
                    'Content-Type'     => 'application/x-www-form-urlencoded',
                    'Referer'          => config('sos.inicio_url'),
                    'Faces-Request'    => 'partial/ajax',
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Accept'           => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ],
            ]);

            $ajaxResponse = (string) $response->getBody();
            $this->saveHtmlLog('nav_02_ajax_response', $ajaxResponse, [
                'status_code' => $response->getStatusCode(),
                'size'        => strlen($ajaxResponse),
            ]);

            // Parse the A4J redirect response
            $redirectUrl = null;
            if (preg_match('/content="redirect"/', $ajaxResponse) &&
                preg_match('/name="Location"\s+content="([^"]+)"/', $ajaxResponse, $m)) {
                $redirectUrl = $m[1];
                Log::channel('sos')->info("SOS: A4J redirect parsed: {$redirectUrl}");
            }

            if ($redirectUrl) {
                $fullRedirectUrl = config('sos.base_url') . str_replace('/ValidadorWeb2', '', $redirectUrl);
                if (str_starts_with($redirectUrl, '/')) {
                    $fullRedirectUrl = 'https://centralaplicaciones.sos.com.co' . $redirectUrl;
                }

                // Follow redirect with GET (like browser's window.location.href)
                Log::channel('sos')->info("SOS: Strategy 2 - GET redirect URL: {$fullRedirectUrl}");
                $response = $this->client->get($fullRedirectUrl, [
                    'headers' => [
                        'Referer' => config('sos.inicio_url'),
                        'Accept'  => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                    ],
                    'http_errors' => false,
                ]);

                $html = (string) $response->getBody();
                $this->saveHtmlLog('nav_03_redirect_get', $html, [
                    'status_code' => $response->getStatusCode(),
                    'size'        => strlen($html),
                    'url'         => $fullRedirectUrl,
                ]);

                if ($this->isConsultaPage($html)) {
                    Log::channel('sos')->info('SOS: Strategy 2a exitosa - GET redirect (' . strlen($html) . ' bytes)');
                    return $html;
                }

                // Try POST to the same redirect URL (some filters only allow POST)
                Log::channel('sos')->info("SOS: Strategy 2b - POST redirect URL: {$fullRedirectUrl}");
                $response = $this->client->post($fullRedirectUrl, [
                    'form_params' => [
                        'javax.faces.ViewState' => $this->extractViewState($this->inicioHtml),
                    ],
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Referer'      => config('sos.inicio_url'),
                        'Origin'       => 'https://centralaplicaciones.sos.com.co',
                    ],
                    'http_errors'     => false,
                    'allow_redirects' => ['max' => 5, 'track_redirects' => true],
                ]);

                $html = (string) $response->getBody();
                $this->saveHtmlLog('nav_03b_redirect_post', $html, [
                    'status_code'      => $response->getStatusCode(),
                    'size'             => strlen($html),
                    'url'              => $fullRedirectUrl,
                    'redirect_history' => $response->getHeader('X-Guzzle-Redirect-History'),
                ]);

                if ($this->isConsultaPage($html)) {
                    Log::channel('sos')->info('SOS: Strategy 2b exitosa - POST redirect (' . strlen($html) . ' bytes)');
                    return $html;
                }
            }

            Log::channel('sos')->warning('SOS: Strategy 2 falló. Intentando Strategy 3...');

            // === Strategy 3: Use jsfcljs-style hidden params POST (exactly like the browser JS) ===
            // The jsfcljs function adds hidden params, submits the form, and removes them
            // It also sets j_idcl and _link_hidden_ fields
            $formId = $navData['formId'];
            $linkId = $navData['linkId'] ?? '';

            $jsfFormData = [
                $formId                       => $formId,
                'autoScroll'                  => '',
                "{$formId}:j_idcl"            => $linkId,
                "{$formId}:_link_hidden_"     => '',
                'javax.faces.ViewState'       => $this->extractViewState($this->inicioHtml),
                $linkId                       => $linkId,
                'codigoOpcion'                => '05',
            ];

            Log::channel('sos')->info('SOS: Strategy 3 - jsfcljs form POST', $jsfFormData);

            $response = $this->client->post(config('sos.inicio_url'), [
                'form_params'     => $jsfFormData,
                'headers'         => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Referer'      => config('sos.inicio_url'),
                    'Origin'       => 'https://centralaplicaciones.sos.com.co',
                ],
                'allow_redirects' => ['max' => 5, 'track_redirects' => true],
                'http_errors'     => false,
            ]);

            $html = (string) $response->getBody();
            $this->saveHtmlLog('nav_04_jsfcljs_post', $html, [
                'status_code'      => $response->getStatusCode(),
                'size'             => strlen($html),
                'redirect_history' => $response->getHeader('X-Guzzle-Redirect-History'),
            ]);

            if ($this->isConsultaPage($html)) {
                Log::channel('sos')->info('SOS: Strategy 3 exitosa (' . strlen($html) . ' bytes)');
                return $html;
            }

            // === Strategy 4: POST directly to consultaAfilido.jsf with Referer from inicio ===
            Log::channel('sos')->warning('SOS: Strategy 3 falló. Intentando Strategy 4 - POST directo...');

            $response = $this->client->post(config('sos.consulta_url'), [
                'form_params' => [
                    'javax.faces.ViewState' => $this->extractViewState($this->inicioHtml),
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Referer'      => config('sos.inicio_url'),
                    'Origin'       => 'https://centralaplicaciones.sos.com.co',
                ],
                'allow_redirects' => ['max' => 5, 'track_redirects' => true],
                'http_errors'     => false,
            ]);

            $html = (string) $response->getBody();
            $this->saveHtmlLog('nav_05_direct_post', $html, [
                'status_code' => $response->getStatusCode(),
                'size'        => strlen($html),
            ]);

            if ($this->isConsultaPage($html)) {
                Log::channel('sos')->info('SOS: Strategy 4 exitosa (' . strlen($html) . ' bytes)');
                return $html;
            }

            Log::channel('sos')->error('SOS: Todas las estrategias de navegación fallaron');
            return $html; // Return whatever we got so the error can be logged
        } catch (\Exception $e) {
            Log::channel('sos')->error('SOS: Error navegando a consulta: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Check if the HTML is the actual consulta (affiliate lookup) page,
     * not the inicio menu page or an error page.
     */
    private function isConsultaPage(string $html): bool
    {
        // Must not be an error page
        if (str_contains($html, 'Error de Acceso') || str_contains($html, 'no está permitida')) {
            return false;
        }

        // Must have consulta-specific form elements
        // The consulta page should have: tipo de identificación select, número input, consultar button
        $hasConsultaElements = (
            (stripos($html, 'numId') !== false || stripos($html, 'identificaci') !== false) &&
            (stripos($html, 'tipoId') !== false || stripos($html, 'Tipo') !== false) &&
            strlen($html) > 1000
        );

        // Must NOT be the inicio menu page
        $isInicioPage = (
            str_contains($html, 'Autorizador Atencion De Urgencias') ||
            str_contains($html, 'Autorizador Servicios De Acceso Directo') ||
            (str_contains($html, 'codigoOpcion') && str_contains($html, 'Opciones'))
        );

        return $hasConsultaElements && !$isInicioPage;
    }

    /**
     * Extract navigation parameters from the "Consultar Afiliado" A4J link on the inicio page.
     */
    private function extractConsultaNavParams(string $html): ?array
    {
        // Find the "Consultar Afiliado" link's onclick attribute
        if (!preg_match('/onclick="([^"]*?)"[^>]*>\s*Consultar Afiliado\s*<\/a>/s', $html, $linkMatch)) {
            Log::channel('sos')->warning('SOS: No se encontró el enlace Consultar Afiliado en inicio');
            return null;
        }

        $onclick = html_entity_decode($linkMatch[1]);
        Log::channel('sos')->info('SOS: onclick de Consultar Afiliado: ' . $onclick);

        // Extract form ID from A4J.AJAX.Submit('FORMID', ...)
        if (!preg_match("/A4J\.AJAX\.Submit\('([^']+)'/", $onclick, $m)) {
            Log::channel('sos')->warning('SOS: No se encontró formId en onclick');
            return null;
        }
        $formId = $m[1];

        // Extract similarityGroupingId
        $sgId = '_viewRoot';
        if (preg_match("/'similarityGroupingId'\s*:\s*'([^']+)'/", $onclick, $m)) {
            $sgId = $m[1];
        }

        // Extract parameters {key: value, ...}
        $parameters = [];
        if (preg_match("/'parameters'\s*:\s*\{([^}]+)\}/", $onclick, $m)) {
            preg_match_all("/'([^']+)'\s*:\s*'([^']+)'/", $m[1], $params, PREG_SET_ORDER);
            foreach ($params as $param) {
                $parameters[$param[1]] = $param[2];
            }
        }

        // Extract ViewState from inicio page
        $viewState = $this->extractViewState($html);

        // Build the complete form data for the A4J navigation POST
        $formData = [
            'AJAXREQUEST'           => $sgId,
            $formId                 => $formId,
            'autoScroll'            => '',
            'javax.faces.ViewState' => $viewState,
        ];

        // Add the A4J link parameters (includes the link ID and codigoOpcion)
        foreach ($parameters as $key => $value) {
            $formData[$key] = $value;
        }

        Log::channel('sos')->info('SOS: Parámetros de navegación extraídos', [
            'formId'     => $formId,
            'sgId'       => $sgId,
            'parameters' => $parameters,
            'viewState'  => $viewState,
        ]);

        return [
            'formId'   => $formId,
            'linkId'   => $sgId,
            'formData' => $formData,
        ];
    }

    private function getCookiesArray(): array
    {
        $result = [];
        foreach ($this->cookies as $cookie) {
            $result[$cookie->getName()] = $cookie->getValue();
        }
        return $result;
    }

    private function extractViewState(string $html): string
    {
        // JSF ViewState pattern
        if (preg_match('/name="javax\.faces\.ViewState"\s+(?:id="[^"]*"\s+)?value="([^"]*)"/', $html, $matches)) {
            return $matches[1];
        }
        if (preg_match('/name="javax\.faces\.ViewState"[^>]*value="([^"]*)"/', $html, $matches)) {
            return $matches[1];
        }
        return '';
    }

    private function extractFormId(string $html): string
    {
        // Find the main form ID in the page
        if (preg_match('/<form[^>]+id="([^"]+)"[^>]*>/', $html, $matches)) {
            return $matches[1];
        }
        return 'form';
    }

    private function extractSubmitButtonId(string $html, string $formId): string
    {
        // Find submit button within the form
        if (preg_match('/<[^>]*(?:type="submit"|value="Entrar"|value="Consultar")[^>]*(?:id|name)="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }
        // Try to find by class or text content
        if (preg_match('/name="([^"]*)"[^>]*value="Entrar"/', $html, $matches)) {
            return $matches[1];
        }
        return $formId . ':btnEntrar';
    }

    private function buildLoginFormData(string $html, string $formId, string $buttonId): array
    {
        $data = [
            'javax.faces.ViewState' => $this->viewState,
        ];

        // Try to find the actual input field names from the HTML
        $usernameField = $this->findInputField($html, 'usuario|user|login|username');
        $passwordField = $this->findInputField($html, 'password|clave|pass');

        if ($usernameField) {
            $data[$usernameField] = config('sos.username');
        } else {
            $data[$formId . ':usuario'] = config('sos.username');
        }

        if ($passwordField) {
            $data[$passwordField] = config('sos.password');
        } else {
            $data[$formId . ':password'] = config('sos.password');
        }

        // Add the submit button
        $data[$buttonId] = $buttonId;

        // Add form ID
        $data[$formId] = $formId;

        // Add any hidden inputs
        $this->addHiddenInputs($html, $data, $formId);

        return $data;
    }

    private function buildConsultaFormData(string $html, string $cedula, string $tipoId): array
    {
        $formId = 'afiliadoForm';
        $viewState = $this->extractViewState($html);

        // Map tipo ID text to numeric value used by the portal
        $tipoIdMap = [
            'CC' => '1', 'TI' => '2', 'CE' => '3', 'PA' => '4',
            'RC' => '5', 'MS' => '7', 'AS' => '8', 'NI' => '9',
            'CD' => '11', 'CN' => '12', 'SC' => '13', 'PE' => '14', 'PT' => '15',
        ];
        $tipoIdValue = $tipoIdMap[strtoupper($tipoId)] ?? '1';

        // Build the form data with EXACT field names from the portal's HTML
        $data = [
            'javax.faces.ViewState'                  => $viewState,
            'afiliadoForm'                           => 'afiliadoForm',
            'afiliadoForm:tipoId'                    => $tipoIdValue,
            'afiliadoForm:numeroId'                  => $cedula,
            'afiliadoForm:plan'                      => '1', // 1=POS
            'afiliadoForm:fechaConsultaInputDate'    => now()->format('Y/m/d'),
            'afiliadoForm:consultarAfiliado'         => 'Consultar',
        ];

        // Add all hidden inputs from the page
        $this->addHiddenInputs($html, $data, $formId);

        return $data;
    }

    private function findInputField(string $html, string $pattern): ?string
    {
        if (preg_match('/<input[^>]+(?:id|name)="([^"]*(?:' . $pattern . ')[^"]*)"[^>]*>/i', $html, $matches)) {
            // Return the name attribute value
            $tag = $matches[0];
            if (preg_match('/name="([^"]*)"/', $tag, $nameMatch)) {
                return $nameMatch[1];
            }
            return $matches[1];
        }
        return null;
    }

    private function findSelectField(string $html, string $pattern): ?string
    {
        if (preg_match('/<select[^>]+(?:id|name)="([^"]*(?:' . $pattern . ')[^"]*)"[^>]*>/i', $html, $matches)) {
            $tag = $matches[0];
            if (preg_match('/name="([^"]*)"/', $tag, $nameMatch)) {
                return $nameMatch[1];
            }
            return $matches[1];
        }
        return null;
    }

    private function addHiddenInputs(string $html, array &$data, string $formId): void
    {
        // Extract hidden inputs that belong to the target form
        preg_match_all('/<input[^>]+type="hidden"[^>]*>/i', $html, $matches);
        foreach ($matches[0] as $input) {
            $name = '';
            $value = '';
            if (preg_match('/name="([^"]*)"/', $input, $m)) {
                $name = $m[1];
            }
            if (preg_match('/value="([^"]*)"/', $input, $m)) {
                $value = $m[1];
            }
            // Only add hidden inputs from the target form (matching prefix) or generic ones
            if ($name && !isset($data[$name]) && $name !== 'javax.faces.ViewState') {
                if (str_starts_with($name, $formId . ':') || str_starts_with($name, $formId) || $name === 'autoScroll') {
                    $data[$name] = $value;
                }
            }
        }
    }

    private function parseConsultaResponse(string $html, string $cedula): array
    {
        $result = ['cedula' => $cedula];

        // Check if affiliate was not found
        if (str_contains($html, 'no se encontr') || str_contains($html, 'No se encontr') || str_contains($html, 'sin resultados')) {
            return ['cedula' => $cedula, 'error' => 'Afiliado no encontrado'];
        }

        // Check if we still see the search form without results (no data returned)
        if (!str_contains($html, 'Informaci') && !str_contains($html, 'prNombre')) {
            return ['cedula' => $cedula, 'error' => 'No se obtuvo información del afiliado'];
        }

        // === NAMES: extracted from disabled input fields ===
        // Pattern: <input id="afiliadoForm:prNombre" ... value="MARY" disabled="disabled" />
        $result['primer_nombre']    = $this->extractInputValue($html, 'afiliadoForm:prNombre');
        $result['segundo_nombre']   = $this->extractInputValue($html, 'afiliadoForm:sgNombre');
        $result['primer_apellido']  = $this->extractInputValue($html, 'afiliadoForm:prApellido');
        $result['segundo_apellido'] = $this->extractInputValue($html, 'afiliadoForm:sgApellido');

        // === TABLE DATA: extracted from label → next td cell pattern ===
        // Pattern: <label> Estado</label></td></td> <td><td class="rich-table-cell" ...>RETIRADO</td>
        $result['fecha_nacimiento']    = $this->extractLabelValue($html, 'Fecha Nacimiento');
        $result['genero']              = $this->extractLabelValue($html, 'nero'); // Gé is encoded
        $result['parentesco']          = $this->extractLabelValue($html, 'Parentesco');
        $result['edad_anos']           = $this->extractLabelValueInt($html, 'Edad A');
        $result['edad_meses']          = $this->extractLabelValueInt($html, 'Edad Meses');
        $result['edad_dias']           = $this->extractLabelValueInt($html, 'Edad D');
        $result['rango_salarial']      = $this->extractLabelValue($html, 'Rango Salarial');
        $result['plan']                = $this->extractLabelValue($html, 'Plan Complementario');
        $result['tipo_afiliado']       = $this->extractLabelValue($html, 'Tipo Afiliado');
        $result['inicio_vigencia']     = $this->extractLabelValue($html, 'Inicio vigencia');
        $result['fin_vigencia']        = $this->extractLabelValue($html, 'Fin Vigencia');
        $result['ips_primaria']        = $this->extractLabelValue($html, 'IPS Primaria');
        $result['semanas_pos_sos']     = $this->extractLabelValueInt($html, 'Semanas POS S.O.S');
        $result['semanas_pos_anterior'] = $this->extractLabelValueInt($html, 'Semanas POS Anterior');
        $result['semanas_pac_sos']     = $this->extractLabelValueInt($html, 'Semanas PAC S.O.S');
        $result['semanas_pac_anterior'] = $this->extractLabelValueInt($html, 'Semanas PAC Anterior');
        $result['estado']              = $this->extractLabelValue($html, 'Estado');
        $result['derecho']             = $this->extractLabelValue($html, 'Derecho');

        // === COPAGO/CUOTA: extracted from bold spans ===
        // Pattern: <span style="...font-weight: bold;">Paga Cuota Moderadora</span> SI - texto
        $result['paga_cuota_moderadora'] = $this->extractBoldSpanValue($html, 'Paga Cuota Moderadora');
        $result['paga_copago']           = $this->extractBoldSpanValue($html, 'Paga Copago');

        // Tipo ID from select
        $result['tipo_id'] = $this->extractSelectedOption($html, 'afiliadoForm:tipoId');

        // Empleadores
        $this->parseEmpleadores($html, $result);

        // Convenios
        $result['convenios'] = $this->parseConvenios($html);

        return $result;
    }

    /**
     * Extract value from a disabled input field by its id/name.
     * Pattern: <input id="afiliadoForm:prNombre" ... value="MARY" disabled="disabled" />
     */
    private function extractInputValue(string $html, string $fieldName): ?string
    {
        $escaped = preg_quote($fieldName, '/');
        if (preg_match('/<input[^>]*(?:id|name)="' . $escaped . '"[^>]*value="([^"]*)"[^>]*>/i', $html, $m)) {
            $val = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
            return $val !== '' ? $val : null;
        }
        // Also try value before name (attribute order varies)
        if (preg_match('/<input[^>]*value="([^"]*)"[^>]*(?:id|name)="' . $escaped . '"[^>]*>/i', $html, $m)) {
            $val = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
            return $val !== '' ? $val : null;
        }
        return null;
    }

    /**
     * Extract value from a label → next table cell pattern (RichFaces table).
     * Pattern: <label> Estado</label></td></td> <td><td class="rich-table-cell" ...>RETIRADO</td>
     * The double-td nesting is a RichFaces rendering quirk.
     */
    private function extractLabelValue(string $html, string $labelText): ?string
    {
        // Search for <label> tags containing the label text (avoids matching page title text)
        $escaped = preg_quote($labelText, '/');
        if (!preg_match('/<label>\s*' . $escaped . '\s*<\/label>/i', $html, $labelMatch, PREG_OFFSET_CAPTURE)) {
            // Fallback: looser match
            if (!preg_match('/<label>[^<]*' . $escaped . '[^<]*<\/label>/i', $html, $labelMatch, PREG_OFFSET_CAPTURE)) {
                return null;
            }
        }

        $labelPos = $labelMatch[0][1];
        // Get 600 chars after the label position
        $chunk = substr($html, $labelPos, 600);

        // After the label's closing </td></td>, find the NEXT td cell value
        // Pattern: </label></td></td> <td><td class="rich-table-cell" ...>VALUE</td>
        if (preg_match('/<\/label>(?:\s*<\/td>)*\s*(?:<td>)*\s*<td[^>]*>(.*?)<\/td>/is', $chunk, $m)) {
            // Strip HTML comments (e.g. <!-- value="2021-12-01"></h:outputText> -->)
            $val = preg_replace('/<!--.*?-->/s', '', $m[1]);
            $val = trim(strip_tags($val));
            $val = html_entity_decode($val, ENT_QUOTES, 'UTF-8');
            $val = trim($val);
            return $val !== '' ? $val : null;
        }

        return null;
    }

    /**
     * Extract integer value from a label → table cell pattern.
     */
    private function extractLabelValueInt(string $html, string $labelText): ?int
    {
        $value = $this->extractLabelValue($html, $labelText);
        if ($value !== null && is_numeric(trim($value))) {
            return (int) trim($value);
        }
        return null;
    }

    /**
     * Extract value that follows a bold span label.
     * Pattern: <span style="...font-weight: bold;">Paga Cuota Moderadora</span> SI - texto
     */
    private function extractBoldSpanValue(string $html, string $labelText): ?string
    {
        $escaped = preg_quote($labelText, '/');
        if (preg_match('/' . $escaped . '<\/span>\s*([^<]+)/i', $html, $m)) {
            $val = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
            return $val !== '' ? $val : null;
        }
        return null;
    }

    /**
     * Extract selected option text from a select element by name/id.
     */
    private function extractSelectedOption(string $html, string $fieldName): ?string
    {
        $escaped = preg_quote($fieldName, '/');
        if (preg_match('/<select[^>]*(?:id|name)="' . $escaped . '"[^>]*>(.*?)<\/select>/is', $html, $selectMatch)) {
            if (preg_match('/<option[^>]*selected[^>]*>([^<]+)<\/option>/i', $selectMatch[1], $optMatch)) {
                return trim($optMatch[1]);
            }
        }
        return null;
    }

    private function parseEmpleadores(string $html, array &$result): void
    {
        // The Empleadores table uses RichFaces rich-table with <label> for cell values
        // and <a> for clickable employer names.
        // Structure: <td><label>NI </label></td> <td><label>860090915</label></td> <td><a>ACTIVOS SAS</a></td>
        $empSection = '';
        if (preg_match('/empleadoresAfiliado.*?<tbody[^>]*>(.*?)<\/tbody>/is', $html, $m)) {
            $empSection = $m[1];
        }

        if ($empSection) {
            // Extract rows - each row has label cells and an anchor for razon social
            preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $empSection, $rows);
            if (!empty($rows[1])) {
                $lastRow = end($rows[1]);
                // Extract label values from cells
                preg_match_all('/<label>\s*([^<]+?)\s*<\/label>/i', $lastRow, $labels);
                // Extract anchor text (razon social)
                preg_match('/<a[^>]*>([^<]+)<\/a>/i', $lastRow, $anchor);

                if (!empty($labels[1])) {
                    $result['empleador_tipo_id']   = trim($labels[1][0] ?? '');
                    $result['empleador_numero_id'] = trim($labels[1][1] ?? '');
                }
                if (!empty($anchor[1])) {
                    $result['empleador_razon_social'] = trim($anchor[1]);
                }
            }
        }
    }

    private function parseConvenios(string $html): ?array
    {
        $convenios = [];
        // The Convenios table uses rich-table with <label> for estado and <a> for convenio name
        $convSection = '';
        if (preg_match('/capitacionAfiliado.*?<tbody[^>]*>(.*?)<\/tbody>/is', $html, $m)) {
            $convSection = $m[1];
        }

        if ($convSection) {
            preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $convSection, $rows);
            foreach ($rows[1] as $row) {
                preg_match('/<label>\s*([^<]+?)\s*<\/label>/i', $row, $labelMatch);
                preg_match('/<a[^>]*>([^<]*)<\/a>/i', $row, $anchorMatch);
                $estado = trim($labelMatch[1] ?? '');
                $convenio = trim($anchorMatch[1] ?? '');
                if ($estado) {
                    $convenios[] = [
                        'estado'   => $estado,
                        'convenio' => $convenio,
                    ];
                }
            }
        }

        return !empty($convenios) ? $convenios : null;
    }

    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }
}
