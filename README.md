# ERP-Multitenant

Plataforma ERP multitenant exclusiva de **CanoDevOps**, organizada por **giro comercial**, **aislamiento de datos sin datos de prueba iniciales**, **gestión dinámica de páginas según giro comercial**, **contabilidad de timbres con comprobantes PDF/XML**, **planes de renta (1, 3, 6, 9 y 12 meses) con facturación a clientes**, **licenciamiento con tolerancia de mes completo** y **monitoreo técnico de servicios**.

## Giros comerciales soportados y páginas por giro
- \eparacion_computadoras\ (\giros/reparacion_computadoras\): incluye **Órdenes de Reparación** (sin comandero).
- \eparacion_celulares\ (\giros/reparacion_celulares\): incluye **Órdenes de Reparación** (sin comandero).
- \estaurante_cafeteria\ (\giros/restaurante_cafeteria\): incluye **Comandero, Mesas y KDS** (sin órdenes de reparación).
- \barrotes\ (\giros/abarrotes\): módulos comerciales base (sin comandero ni órdenes de reparación).
- \erreteria\ (\giros/ferreteria\): módulos comerciales base (sin comandero ni órdenes de reparación).

## Principios y reglas de trabajo
1. **Separación estricta por giro y catálogo dinámico de páginas:** al dar de alta una empresa en \/plataforma/empresas\, tanto el formulario como el servidor filtran automáticamente las páginas disponibles según el \giro_comercial\ elegido.
2. **Arranque limpio sin datos de prueba:** las migraciones dejan las tablas listas sin empresas ni registros semilla; las empresas de prueba se crean bajo demanda.
3. **Consola exclusiva de CanoDevOps (páginas separadas):**
   - \/plataforma/monitoreo\: supervisión de disponibilidad de páginas, latencia, estado de licencia y saldo/estado de timbres **sin acceso a los datos operativos privados del cliente**.
   - \/plataforma/empresas\: alta de empresas en renta con \giro_comercial\ fijo/bloqueado, plazo de renta (\1, 3, 6, 9, 12\ meses), vigencia/tolerancia de licencia, páginas dinámicas según giro y cuenta inicial de **Propietario**.
   - \/plataforma/facturacion\: emisión/registro de facturas a clientes por renta o paquetes de timbres, marcación de pagos y extensión automática de vigencia/timbres.
   - \/plataforma/timbres\: registro de compras mayoristas con carga y descarga segura de **PDF y XML** del proveedor, contabilización automática en bolsa y asignación a empresas.
   - \/plataforma/apariencia\: personalización en vivo desde BD del template de la consola de CanoDevOps + gestión de operador principal inmutable y usuarios de plataforma.
4. **Política de licenciamiento y tolerancia:** al vencer un periodo de renta se contempla un margen de tolerancia (por defecto 30 días / mes completo) en estado de gracia antes de suspender por morosidad.
5. **Documentación continua:** todo cambio relevante queda asentado en \README.md\.

## Flujo de ramas Git
- \main\: versión estable y aprobada tras pruebas.
- \develop\: integración y desarrollo activo.
- \	est\: rama de pruebas; recibe de \develop\, se prueban cambios y se regresan ajustes a \develop\ antes de unir a \main\.

## Bitácora de avances
- **Etapa 1–13:** repositorio limpio, migraciones base sin datos de prueba, consola separada en páginas independientes, login/logout por perfil, operador principal inmutable, lotes de timbres con XML/PDF, facturación por plazos (1, 3, 6, 9 y 12 meses), tolerancia de licencia y módulo inicial de \eparacion_computadoras\.
- **Etapa 14:** filtrado dinámico de páginas habilitables según el \giro_comercial\ elegido (ocultando comandero en talleres/abarrotes/ferreterías y órdenes de taller en restaurantes, tanto en vista como en controlador).
