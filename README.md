# ERP-Multitenant

Plataforma ERP multitenant exclusiva de **CanoDevOps**, respaldada por **MariaDB / MySQL** con **bases de datos independientes** para la plataforma y cada uno de los **5 giros comerciales**, arrancando limpias sin datos de prueba iniciales.

## Arquitectura de Bases de Datos y Módulos (MariaDB)
1. \erp_plataforma\ (conexión \mysql\): consola exclusiva de CanoDevOps.
2. \erp_reparacion_computadoras\ (\giros/reparacion_computadoras\): \equipos_taller\, \ordenes_reparacion\, \	ransferencias_taller\, \promociones_combos_taller\, con páginas base (\Dashboard\, \Órdenes\, \Inventario\, \Transferencias\, \Facturación\, \Promociones\, \Autogestión Visual\).
3. \erp_restaurante_cafeteria\ (\giros/restaurante_cafeteria\): \mesas_restaurante\, \ecetas_platillos\, \comandas_restaurante\, \promociones_combos_restaurante\.
4. \erp_abarrotes\ (\giros/abarrotes\): \productos_abarrotes\, \promociones_descuentos_abarrotes\.
5. \erp_ferreteria\ (\giros/ferreteria\): \productos_ferreteria\, \promociones_descuentos_ferreteria\.
6. \erp_reparacion_celulares\ (\giros/reparacion_celulares\): \equipos_celulares\, \ordenes_reparacion_celulares\, \promociones_combos_celulares\.

## Flujo de ramas Git
- \main\: versión estable y aprobada tras pruebas.
- \develop\: integración y desarrollo activo.
- \	est\: rama de pruebas; recibe de \develop\, se prueban cambios y se regresan ajustes a \develop\ antes de unir a \main\.

## Bitácora de avances
- **Etapa 1–20:** repositorio limpio, consola separada en páginas independientes, login/logout por perfil, operador principal inmutable, lotes de timbres con XML/PDF, facturación por plazos (1, 3, 6, 9 y 12 meses), tolerancia de licencia, catálogo dinámico por giro, 6 bases de datos en MariaDB y módulos iniciales de los 5 giros.
- **Etapa 21:** construcción de páginas base para \eparacion_computadoras\ (\Dashboard\, \Órdenes\, \Inventario\, \Transferencias\, \Facturación\, \Promociones\, \Autogestión Visual\) y tabla \	ransferencias_taller\ en \erp_reparacion_computadoras\.
