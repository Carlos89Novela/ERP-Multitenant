# ERP-Multitenant

Plataforma ERP multitenant exclusiva de **CanoDevOps**, respaldada por **MariaDB / MySQL** con **bases de datos independientes** para la plataforma y cada uno de los **5 giros comerciales**, arrancando limpias sin datos de prueba iniciales.

## Autogestión Visual y Página Pública por Empresa
- **Autogestión de identidad y medios (\/empresa/portal\):** cada empresa puede cargar su logo, ícono de respaldo, paleta de colores y gestionar su carrusel principal y elementos destacados con fotos e íconos (agregar, ocultar/mostrar o quitar).
- **Página pública por empresa (\/tienda/{slug}\):** escaparate público con carrusel interactivo y catálogo destacado de platillos/productos/servicios, respetando el estado activo y de licencia de la empresa.

## Flujo de ramas Git
- \main\: versión estable y aprobada tras pruebas.
- \develop\: integración y desarrollo activo.
- \	est\: rama de pruebas; recibe de \develop\, se prueban cambios y se regresan ajustes a \develop\ antes de unir a \main\.

## Bitácora de avances
- **Etapa 1–21:** repositorio limpio, consola separada en páginas independientes, login/logout por perfil, operador principal inmutable, lotes de timbres con XML/PDF, facturación por plazos (1, 3, 6, 9 y 12 meses), tolerancia de licencia, catálogo dinámico por giro, 6 bases de datos en MariaDB y módulos base de los giros.
- **Etapa 22:** página de autogestión visual con carga de logo/íconos/fotos y página pública por empresa (\/tienda/{slug}\) con carrusel y destacados autogestionables.
