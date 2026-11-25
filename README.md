# ERP-Multitenant

Plataforma ERP multitenant exclusiva de **CanoDevOps**, respaldada por **MariaDB / MySQL** con **bases de datos independientes** para la plataforma y cada uno de los **5 giros comerciales**, arrancando limpias sin datos de prueba iniciales y con login ambientado visualmente por empresa.

## Flujo de ramas Git
- \main\: versión estable y aprobada tras pruebas.
- \develop\: integración y desarrollo activo.
- \	est\: rama de pruebas; recibe de \develop\, se prueban cambios y se regresan ajustes a \develop\ antes de unir a \main\.

## Bitácora de avances
- **Etapa 1–27:** repositorio limpio, consola separada en páginas independientes, operador principal inmutable, lotes de timbres con XML/PDF, facturación por plazos (1, 3, 6, 9 y 12 meses), tolerancia de licencia, catálogo dinámico por giro, 6 bases de datos en MariaDB, módulos base de los giros, autogestión visual/página pública, Dashboard de \eparacion_computadoras\, roles por empresa, empresa de prueba y login ambientado por empresa.
- **Etapa 28:** corrección del nombre de ruta \login.submit\ en \outes/web.php\ para el formulario de inicio de sesión ambientado.
