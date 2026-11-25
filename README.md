# ERP-Multitenant

Plataforma ERP multitenant exclusiva de **CanoDevOps**, respaldada por **MariaDB / MySQL** con **bases de datos independientes** para la plataforma y cada uno de los **5 giros comerciales**, arrancando limpias sin datos de prueba iniciales.

## Autogestión Visual por Giro (Restaurante / Cafetería, Abarrotes y Ferretería)
- **Restaurante / Cafetería (\/giros/restaurante-cafeteria/autogestion\):** soporte de imagen en recetas/platillos (\imagen_path\ en \erp_restaurante_cafeteria\), logo/íconos y administración de slides y platillos destacados en el carrusel público.
- **Abarrotes (\/giros/abarrotes/autogestion\) y Ferretería (\/giros/ferreteria/autogestion\):** soporte de imagen en productos (\imagen_path\ en sus respectivas BDs de giro), logo/íconos y administración de slides y promociones/productos destacados en la página pública.

## Flujo de ramas Git
- \main\: versión estable y aprobada tras pruebas.
- \develop\: integración y desarrollo activo.
- \	est\: rama de pruebas; recibe de \develop\, se prueban cambios y se regresan ajustes a \develop\ antes de unir a \main\.

## Bitácora de avances
- **Etapa 1–23:** repositorio limpio, consola separada en páginas independientes, login/logout por perfil, operador principal inmutable, lotes de timbres con XML/PDF, facturación por plazos (1, 3, 6, 9 y 12 meses), tolerancia de licencia, catálogo dinámico por giro, 6 bases de datos en MariaDB, módulos base de los giros, autogestión visual/página pública y autogestión para talleres de cómputo y celulares.
- **Etapa 24:** autogestión visual con fotos/íconos y carrusel público primero para \estaurante_cafeteria\ y después para \barrotes\ y \erreteria\, incluyendo columnas \imagen_path\ en sus respectivas bases de datos.
