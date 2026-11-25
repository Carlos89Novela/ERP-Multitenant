# ERP-Multitenant

Plataforma ERP multitenant exclusiva de **CanoDevOps**, organizada por **giro comercial**, **aislamiento de datos sin datos de prueba iniciales**, **gestión de páginas para clientes en renta**, **contabilidad de timbres con comprobantes PDF/XML**, **planes de renta (1, 3, 6, 9 y 12 meses) con facturación a clientes**, **licenciamiento con tolerancia de mes completo** y **monitoreo técnico de servicios**.

## Giros comerciales soportados
- \eparacion_computadoras\ (\giros/reparacion_computadoras\)
- \estaurante_cafeteria\ (\giros/restaurante_cafeteria\)
- \barrotes\ (\giros/abarrotes\)
- \erreteria\ (\giros/ferreteria\)
- \eparacion_celulares\ (\giros/reparacion_celulares\)

## Principios y reglas de trabajo
1. **Separación estricta por giro:** tablas, rutas, vistas y lógica separadas por giro para evitar cruces al modificar código.
2. **Arranque limpio sin datos de prueba:** las migraciones dejan las tablas listas sin empresas ni registros semilla; las empresas de prueba se crean bajo demanda.
3. **Consola exclusiva de CanoDevOps (páginas separadas):**
   - \/plataforma/monitoreo\: supervisión de disponibilidad de páginas, latencia, estado de licencia y saldo/estado de timbres **sin acceso a los datos operativos privados del cliente**.
   - \/plataforma/empresas\: alta de empresas en renta con \giro_comercial\ fijo/bloqueado, plazo de renta (\1, 3, 6, 9, 12\ meses), vigencia/tolerancia de licencia, páginas habilitadas y cuenta inicial de **Propietario**.
   - \/plataforma/timbres\: registro de compras mayoristas con carga de **PDF y XML** del proveedor, contabilización automática en bolsa y asignación a empresas.
   - \/plataforma/facturacion\: emisión/registro de facturas a clientes (renta por plazos o paquetes de timbres), control de pagos y renovaciones de licencia.
   - \/plataforma/apariencia\: personalización en vivo desde BD del template de la consola de CanoDevOps + gestión de operador principal inmutable y usuarios de plataforma.
4. **Política de licenciamiento y tolerancia:** al vencer un periodo de renta se contempla un margen de tolerancia (por defecto 30 días / mes completo) antes de suspender por morosidad, evitando cortes prematuros.
5. **Documentación continua:** todo cambio relevante queda asentado en \README.md\.

## Flujo de ramas Git
- \main\: versión estable y aprobada tras pruebas.
- \develop\: integración y desarrollo activo.
- \	est\: rama de pruebas; recibe de \develop\, se prueban cambios y se regresan ajustes a \develop\ antes de unir a \main\.

## Bitácora de avances
- **Etapa 1:** repositorio limpio en \Carlos89Novela/ERP-Multitenant\ con ramas \main\, \develop\ y \	est\, \.gitignore\ seguro y estructura de 5 giros.
- **Etapa 2:** base Laravel limpia y \README.md\ inicial en \develop\.
- **Etapa 3:** migraciones base en blanco para multitenant, autogestión de páginas/templates, compra/asignación mayorista de timbres, monitoreo de servicios (sin acceso a datos de negocio del cliente) y tablas base para los 5 giros.
- **Etapa 4:** modelos Eloquent base y Consola de Plataforma con selector/editor de template propio en BD, alta de empresas en renta, compra mayorista/asignación de timbres y monitor técnico de servicio.
- **Etapa 5:** ejecución inicial de migraciones limpias en base de datos vacía, generación de \APP_KEY\ y validación de \/plataforma\.
- **Etapa 6:** guardia \operador.plataforma\, registro de cuenta de operador y creación de usuario Propietario al dar de alta una empresa.
- **Etapa 7:** separación de la consola en 4 páginas dedicadas (\/plataforma/monitoreo\, \/plataforma/empresas\, \/plataforma/timbres\ y \/plataforma/apariencia\) compartiendo el template administrable en BD.
- **Etapa 8:** sistema de login/logout por perfil (\/login\, \/logout\) con redirección a \/plataforma/monitoreo\ para operador y \/empresa/portal\ para propietario de empresa.
- **Etapa 9:** cuenta principal inmutable de operador de plataforma (\es_operador_principal\) con acceso total y protección contra borrado o degradación.
- **Etapa 10:** base de datos y modelos para comprobantes PDF/XML en compras mayoristas de timbres, plazos de renta (1, 3, 6, 9 y 12 meses), licenciamiento con tolerancia de mes completo y facturación a clientes.
- **Siguiente etapa:** vistas y acciones en \PlataformaController\ para subir PDF/XML de timbres, gestionar facturación/plazos de clientes y aplicar la regla de tolerancia de licencia.
