# ERP-Multitenant

Plataforma ERP multitenant exclusiva de **CanoDevOps**, respaldada por **MariaDB / MySQL** con **bases de datos independientes** para la plataforma y cada uno de los **5 giros comerciales**, arrancando limpias sin datos de prueba iniciales.

## Arquitectura de Bases de Datos (MariaDB)
1. \erp_plataforma\ (conexión \mysql\): exclusiva para gestión de CanoDevOps (\empresas\, \sucursales\ administrativas, \users\ con \es_operador_principal\, \	emplate_configuraciones\, \pagina_bloques\, \olsa_timbres_lotes\ con XML/PDF, \signaciones_timbres\, \acturas_clientes\ con plazos 1/3/6/9/12 meses y \monitoreo_servicios_empresa\). **No contiene tablas operativas de los giros.**
2. \erp_reparacion_computadoras\ (conexión \giro_reparacion_computadoras\): \equipos_taller\, \ordenes_reparacion\, \promociones_combos_taller\.
3. \erp_restaurante_cafeteria\ (conexión \giro_restaurante_cafeteria\): \mesas_restaurante\, \ecetas_platillos\, \comandas_restaurante\, \promociones_combos_restaurante\.
4. \erp_abarrotes\ (conexión \giro_abarrotes\): \productos_abarrotes\, \promociones_descuentos_abarrotes\.
5. \erp_ferreteria\ (conexión \giro_ferreteria\): \productos_ferreteria\, \promociones_descuentos_ferreteria\.
6. \erp_reparacion_celulares\ (conexión \giro_reparacion_celulares\): \equipos_celulares\, \ordenes_reparacion_celulares\, \promociones_combos_celulares\.

## Flujo de ramas Git
- \main\: versión estable y aprobada tras pruebas.
- \develop\: integración y desarrollo activo.
- \	est\: rama de pruebas; recibe de \develop\, se prueban cambios y se regresan ajustes a \develop\ antes de unir a \main\.

## Bitácora de avances
- **Etapa 1–17:** repositorio limpio, consola separada en páginas independientes, login/logout por perfil, operador principal inmutable, lotes de timbres con XML/PDF, facturación por plazos (1, 3, 6, 9 y 12 meses), tolerancia de licencia, catálogo dinámico por giro y configuración de MariaDB con 6 bases de datos.
- **Etapa 18:** depuración de \erp_plataforma\ para retirar las tablas operativas de los giros y dejarlas exclusivamente en la base de datos de cada giro comercial.
