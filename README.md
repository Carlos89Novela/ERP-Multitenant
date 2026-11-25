# ERP-Multitenant

Plataforma ERP multitenant exclusiva de **CanoDevOps**, respaldada por **MariaDB / MySQL** con **bases de datos independientes** para la plataforma y cada uno de los **5 giros comerciales**, tabla \usuarios_empresa\ por giro, login ambientado por empresa y reseteo con contraseña temporal y cambio forzoso.

## Flujo de ramas Git
- \main\: versión estable y aprobada tras pruebas.
- \develop\: integración y desarrollo activo.
- \	est\: rama de pruebas; recibe de \develop\, se prueban cambios y se regresan ajustes a \develop\ antes de unir a \main\.

## Bitácora de avances
- **Etapa 1–31:** consola de plataforma, lotes de timbres con XML/PDF, facturación por plazos, tolerancia de licencia, catálogo dinámico por giro, 6 BDs en MariaDB, autogestión visual con fotos/íconos, página pública con carrusel, Dashboard de \eparacion_computadoras\, login ambientado por empresa, migraciones consolidadas y tabla \usuarios_empresa\ por giro con cambio forzoso.
- **Etapa 32:** consolidación explícita de \POST /login\ hacia \AuthController@login\ y verificación de los usuarios de prueba en \erp_reparacion_computadoras.usuarios_empresa\.
