# ERP-Multitenant

Plataforma ERP multitenant exclusiva de **CanoDevOps**, respaldada por **MariaDB / MySQL** con **bases de datos independientes** para la plataforma y cada uno de los **5 giros comerciales**, arrancando limpias sin datos de prueba iniciales.

## Autogestión Visual para Talleres (Computadoras y Celulares)
- Rutas dedicadas en \/giros/reparacion-computadoras/autogestion\ y \/giros/reparacion-celulares/autogestion\, respaldadas por \AutogestionTallerController\.
- Permite personalizar logo/ícono de marca, colores, tipografía y radio de bordes, además de agregar, ocultar/mostrar o quitar slides del carrusel y servicios/combos destacados con fotos e íconos reflejados en \/tienda/{slug}\.

## Flujo de ramas Git
- \main\: versión estable y aprobada tras pruebas.
- \develop\: integración y desarrollo activo.
- \	est\: rama de pruebas; recibe de \develop\, se prueban cambios y se regresan ajustes a \develop\ antes de unir a \main\.

## Bitácora de avances
- **Etapa 1–22:** repositorio limpio, consola separada en páginas independientes, login/logout por perfil, operador principal inmutable, lotes de timbres con XML/PDF, facturación por plazos (1, 3, 6, 9 y 12 meses), tolerancia de licencia, catálogo dinámico por giro, 6 bases de datos en MariaDB, módulos base de los giros y autogestión visual/página pública inicial.
- **Etapa 23:** página dedicada de autogestión visual para \eparacion_computadoras\ y \eparacion_celulares\ con carga de logo/íconos/fotos y administración de slides y combos/servicios destacados.
