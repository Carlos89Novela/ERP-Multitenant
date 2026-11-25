# ERP-Multitenant

Plataforma ERP multitenant organizada por **giro comercial**, **aislamiento de datos sin datos de prueba iniciales**, **gestión de páginas para clientes en renta**, **bolsa mayorista y asignación de timbres**, y **monitoreo técnico de servicios**.

## Giros comerciales soportados
- \eparacion_computadoras\ (\giros/reparacion_computadoras\)
- \estaurante_cafeteria\ (\giros/restaurante_cafeteria\)
- \barrotes\ (\giros/abarrotes\)
- \erreteria\ (\giros/ferreteria\)
- \eparacion_celulares\ (\giros/reparacion_celulares\)

## Principios y reglas de trabajo
1. **Separación estricta por giro:** tablas, rutas, vistas y lógica separadas por giro para evitar cruces al modificar código.
2. **Arranque limpio sin datos de prueba:** las migraciones dejan las tablas listas sin empresas ni registros semilla; las empresas de prueba se crean bajo demanda.
3. **Consola del proveedor de la plataforma (tú):**
   - Alta de empresas con \giro_comercial\ fijo y selección de páginas habilitadas para el cliente en renta.
   - Compra de timbres al por mayor (\olsa_timbres_lotes\) y asignación de timbres a empresas (\signaciones_timbres\ + \empresas.saldo_timbres\).
   - Página de monitoreo (\monitoreo_servicios_empresa\) enfocada en disponibilidad de páginas, estado/saldo de timbres y señales del servicio **sin acceder a los datos operativos privados del cliente**.
4. **Documentación continua:** todo cambio relevante queda asentado en \README.md\.

## Flujo de ramas Git
- \main\: versión estable y aprobada tras pruebas.
- \develop\: integración y desarrollo activo.
- \	est\: rama de pruebas; recibe de \develop\, se prueban cambios y se regresan ajustes a \develop\ antes de unir a \main\.

## Bitácora de avances
- **Etapa 1:** repositorio limpio en \Carlos89Novela/ERP-Multitenant\ con ramas \main\, \develop\ y \	est\, \.gitignore\ seguro y estructura de 5 giros.
- **Etapa 2:** base Laravel limpia y \README.md\ inicial en \develop\.
- **Etapa 3:** migraciones base en blanco para multitenant, autogestión de páginas/templates, compra/asignación mayorista de timbres, monitoreo de servicios (sin acceso a datos de negocio del cliente) y tablas base para los 5 giros.
- **Siguiente etapa:** modelos Eloquent, consola de alta de empresas + timbres + monitoreo de servicios, y generador limpio de empresa de prueba por giro.
