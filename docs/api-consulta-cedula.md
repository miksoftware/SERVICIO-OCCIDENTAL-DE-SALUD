# API Servicio Occidental de Salud — Consulta de Afiliado por Cédula

## Descripción

Retorna el **historial completo** de consultas realizadas a Servicio Occidental de Salud (SOS) para un número de cédula específico, ordenado del registro **más reciente al más antiguo**. Solo se incluyen consultas exitosas (con estado registrado y sin errores).

---

## Endpoint

```
GET /api/consulta/cedula/{cedula}
```

### Parámetros de ruta

| Parámetro | Tipo   | Requerido | Descripción                     |
|-----------|--------|-----------|---------------------------------|
| `cedula`  | string | Sí        | Número de cédula (solo dígitos) |

### Autenticación

Requiere token **Bearer** de Sanctum en el header de la petición.

```
Authorization: Bearer <token>
```

---

## Ejemplo de petición

```http
GET /api/consulta/cedula/1234567890
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Accept: application/json
```

---

## Respuestas

### 200 — Consulta exitosa

```json
{
  "success": true,
  "message": "Consulta exitosa.",
  "total": 2,
  "data": [
    {
      "cedula": "1234567890",
      "tipo_id": "CC",
      "primer_nombre": "ANDRÉS",
      "segundo_nombre": "FELIPE",
      "primer_apellido": "CASTILLO",
      "segundo_apellido": "MORA",
      "nombre_completo": "ANDRÉS FELIPE CASTILLO MORA",
      "fecha_nacimiento": "1992-07-18",
      "genero": "M",
      "parentesco": "COTIZANTE",
      "edad_anos": 33,
      "edad_meses": 9,
      "edad_dias": 9,
      "rango_salarial": "1-2 SMLV",
      "tipo_afiliado": "COTIZANTE",
      "plan": "POS",
      "estado": "ACTIVO",
      "derecho": "CON DERECHO",
      "inicio_vigencia": "2024-01-01",
      "fin_vigencia": null,
      "ips_primaria": "IPS SOS CALI NORTE",
      "semanas_pos_sos": 52,
      "semanas_pos_anterior": 104,
      "semanas_pac_sos": 52,
      "semanas_pac_anterior": 104,
      "paga_cuota_moderadora": true,
      "paga_copago": false,
      "empleador": {
        "tipo_id": "NIT",
        "numero_id": "900123456",
        "razon_social": "EMPRESA DEMO S.A.S."
      },
      "informacion_adicional": {
        "estado_civil": "SOLTERO",
        "telefono": "3187654321",
        "direccion": "CLL 10 # 5-30",
        "barrio": "SAN FERNANDO",
        "ciudad_residencia": "CALI",
        "departamento": "VALLE DEL CAUCA",
        "semanas_cotizadas": 260,
        "afp": "PORVENIR"
      },
      "consultado_en": "2026-04-27T10:30:00+00:00"
    },
    {
      "cedula": "1234567890",
      "tipo_id": "CC",
      "primer_nombre": "ANDRÉS",
      "segundo_nombre": "FELIPE",
      "primer_apellido": "CASTILLO",
      "segundo_apellido": "MORA",
      "nombre_completo": "ANDRÉS FELIPE CASTILLO MORA",
      "fecha_nacimiento": "1992-07-18",
      "genero": "M",
      "parentesco": "COTIZANTE",
      "edad_anos": 33,
      "edad_meses": 8,
      "edad_dias": 5,
      "rango_salarial": "1-2 SMLV",
      "tipo_afiliado": "COTIZANTE",
      "plan": "POS",
      "estado": "ACTIVO",
      "derecho": "CON DERECHO",
      "inicio_vigencia": "2024-01-01",
      "fin_vigencia": null,
      "ips_primaria": "IPS SOS CALI NORTE",
      "semanas_pos_sos": 52,
      "semanas_pos_anterior": 104,
      "semanas_pac_sos": 52,
      "semanas_pac_anterior": 104,
      "paga_cuota_moderadora": true,
      "paga_copago": false,
      "empleador": {
        "tipo_id": "NIT",
        "numero_id": "900123456",
        "razon_social": "EMPRESA DEMO S.A.S."
      },
      "informacion_adicional": {
        "estado_civil": "SOLTERO",
        "telefono": "3187654321",
        "direccion": "CLL 10 # 5-30",
        "barrio": "SAN FERNANDO",
        "ciudad_residencia": "CALI",
        "departamento": "VALLE DEL CAUCA",
        "semanas_cotizadas": 256,
        "afp": "PORVENIR"
      },
      "consultado_en": "2026-03-23T09:00:00+00:00"
    }
  ]
}
```

### 404 — Sin resultados

```json
{
  "success": false,
  "message": "No se encontraron resultados para la cédula proporcionada.",
  "data": null
}
```

---

## Descripción de campos del JSON de respuesta

### Nivel raíz

| Campo     | Tipo    | Descripción                                                    |
|-----------|---------|----------------------------------------------------------------|
| `success` | boolean | `true` si la operación fue exitosa, `false` en caso contrario |
| `message` | string  | Mensaje descriptivo del resultado                              |
| `total`   | integer | Cantidad total de registros retornados                         |
| `data`    | array   | Arreglo de objetos con el historial de consultas               |

### Objeto dentro de `data[]` — Campos principales

| Campo                   | Tipo            | Descripción                                                                     |
|-------------------------|-----------------|---------------------------------------------------------------------------------|
| `cedula`                | string          | Número de documento del afiliado                                                |
| `tipo_id`               | string / null   | Tipo de documento (ej. `CC`, `TI`, `CE`, `PA`)                                 |
| `primer_nombre`         | string / null   | Primer nombre del afiliado                                                      |
| `segundo_nombre`        | string / null   | Segundo nombre del afiliado. Puede ser `null`                                   |
| `primer_apellido`       | string / null   | Primer apellido del afiliado                                                    |
| `segundo_apellido`      | string / null   | Segundo apellido del afiliado. Puede ser `null`                                 |
| `nombre_completo`       | string / null   | Nombre completo concatenado                                                     |
| `fecha_nacimiento`      | string / null   | Fecha de nacimiento en formato `YYYY-MM-DD`                                     |
| `genero`                | string / null   | Género del afiliado (`M` = Masculino, `F` = Femenino)                           |
| `parentesco`            | string / null   | Relación con el cotizante (ej. `COTIZANTE`, `BENEFICIARIO`)                     |
| `edad_anos`             | integer / null  | Años de edad al momento de la consulta                                          |
| `edad_meses`            | integer / null  | Meses complementarios de la edad                                                |
| `edad_dias`             | integer / null  | Días complementarios de la edad                                                 |
| `rango_salarial`        | string / null   | Rango salarial del cotizante (ej. `1-2 SMLV`)                                  |
| `tipo_afiliado`         | string / null   | Tipo de afiliado (ej. `COTIZANTE`, `BENEFICIARIO`)                              |
| `plan`                  | string / null   | Plan de salud asignado (ej. `POS`)                                              |
| `estado`                | string / null   | Estado de afiliación (ej. `ACTIVO`, `RETIRADO`, `SUSPENDIDO`)                  |
| `derecho`               | string / null   | Indicador de derecho a servicios (ej. `CON DERECHO`, `SIN DERECHO`)             |
| `inicio_vigencia`       | string / null   | Fecha de inicio de vigencia de la afiliación (`YYYY-MM-DD`)                    |
| `fin_vigencia`          | string / null   | Fecha de fin de vigencia. `null` si la afiliación está activa                  |
| `ips_primaria`          | string / null   | IPS primaria asignada al afiliado                                               |
| `semanas_pos_sos`       | integer / null  | Semanas cotizadas en SOS para el POS                                            |
| `semanas_pos_anterior`  | integer / null  | Semanas cotizadas en EPS anterior para el POS                                   |
| `semanas_pac_sos`       | integer / null  | Semanas cotizadas en SOS para el PAC                                            |
| `semanas_pac_anterior`  | integer / null  | Semanas cotizadas en EPS anterior para el PAC                                   |
| `paga_cuota_moderadora` | boolean / null  | Indica si el afiliado aplica pago de cuota moderadora                           |
| `paga_copago`           | boolean / null  | Indica si el afiliado aplica pago de copago                                     |
| `consultado_en`         | string ISO 8601 | Fecha y hora en que se realizó la consulta (UTC)                                |

### Objeto `empleador`

| Campo          | Tipo          | Descripción                                        |
|----------------|---------------|----------------------------------------------------|
| `tipo_id`      | string / null | Tipo de identificación del empleador (ej. `NIT`)  |
| `numero_id`    | string / null | Número de identificación del empleador             |
| `razon_social` | string / null | Razón social o nombre del empleador                |

### Objeto `informacion_adicional`

| Campo               | Tipo           | Descripción                                                     |
|---------------------|----------------|-----------------------------------------------------------------|
| `estado_civil`      | string / null  | Estado civil del afiliado (ej. `SOLTERO`, `CASADO`)            |
| `telefono`          | string / null  | Teléfono de contacto registrado                                 |
| `direccion`         | string / null  | Dirección de residencia                                         |
| `barrio`            | string / null  | Barrio de residencia                                            |
| `ciudad_residencia` | string / null  | Ciudad de residencia                                            |
| `departamento`      | string / null  | Departamento de residencia                                      |
| `semanas_cotizadas` | integer / null | Total de semanas cotizadas al sistema de salud                  |
| `afp`               | string / null  | Fondo de pensiones al que está afiliado (AFP)                   |

---

## Notas

- Los registros se ordenan de **más reciente a más antiguo** según el campo `consultado_en`.
- Solo se retornan consultas con `estado` registrado y sin errores (`error = null`).
- Si la cédula no tiene registros válidos en la base de datos, se retorna HTTP `404`.
- El campo `cedula` en la URL solo acepta dígitos numéricos; cualquier otro carácter retorna `404` automáticamente.
