# ERP-Multitenant

Plataforma ERP multitenant exclusiva de **CanoDevOps**, respaldada por **MariaDB / MySQL** con **bases de datos independientes** para la plataforma y cada uno de los **5 giros comerciales**, arrancando limpias sin datos de prueba iniciales y con login ambientado visualmente por empresa.

## Login Ambientado por Empresa
- **Acceso específico por empresa (\/tienda/{slug}/login\ y \/login?empresa={slug}\):** toma el logo/ícono, colores, tipografía, radio de bordes y giro comercial de la empresa desde su configuración visual, e impide que usuarios de otra empresa o de plataforma entren por error al portal de un cliente distinto.
- **Acceso general (\/login\):** permite acceso a la consola de CanoDevOps o seleccionar directamente el portal ambientado de cada empresa activa.

## Flujo de ramas Git
- \main\: versión estable y aprobada tras pruebas.
- \develop\: integración y desarrollo activo.
- \	est\: rama de pruebas; recibe de \develop\, se prueban cambios y se regresan ajustes a \develop\ antes de unir a \main\.

## Bitácora de avances
- **Etapa 1–26:** repositorio limpio, consola separada en páginas independientes, operador principal inmutable, lotes de timbres con XML/PDF, facturación por plazos (1, 3, 6, 9 y 12 meses), tolerancia de licencia, catálogo dinámico por giro, 6 bases de datos en MariaDB, módulos base de los giros, autogestión visual/página pública, Dashboard de \eparacion_computadoras\, roles por empresa y empresa de prueba.
- **Etapa 27:** registro PSR-4/autoload de controladores de giros y **Login ambientado visualmente por empresa** (\/tienda/{slug}/login\) con validación estricta de pertenencia a la empresa.
