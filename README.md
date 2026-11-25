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
3. **Consola del proveedor de la plataforma (\/plataforma\):**
   - **Template propio administrable:** personalización en vivo desde BD de colores, tipografías, tamaños, bordes, fondos y presets visuales para las páginas del operador de plataforma.
   - **Alta de empresas en renta:** asignación de \giro_comercial\ fijo/bloqueado, sucursal matriz, páginas habilitadas y cuenta inicial de **Propietario**.
   - **Bolsa mayorista y asignación de timbres:** registro de lotes comprados al por mayor (\olsa_timbres_lotes\) y asignación con descuento automático hacia cada empresa (\signaciones_timbres\ + \empresas.saldo_timbres\).
   - **Monitoreo de servicios (\monitoreo_servicios_empresa\):** supervisión de disponibilidad de páginas, latencia y saldo/estado de timbres **sin acceso a los datos operativos privados del cliente**.
4. **Documentación continua:** todo cambio relevante queda asentado en \README.md\.

## Flujo de ramas Git
- \main\: versión estable y aprobada tras pruebas.
- \develop\: integración y desarrollo activo.
- \	est\: rama de pruebas; recibe de \develop\, se prueban cambios y se regresan ajustes a \develop\ antes de unir a \main\.

## Bitácora de avances
- **Etapa 1:** repositorio limpio en \Carlos89Novela/ERP-Multitenant\ con ramas \main\, \develop\ y \	est\, \.gitignore\ seguro y estructura de 5 giros.
- **Etapa 2:** base Laravel limpia y \README.md\ inicial en \develop\.
- **Etapa 3:** migraciones base en blanco para multitenant, autogestión de páginas/templates, compra/asignación mayorista de timbres, monitoreo de servicios (sin acceso a datos de negocio del cliente) y tablas base para los 5 giros.
- **Etapa 4:** modelos Eloquent base y Consola de Plataforma (\/plataforma\) con selector/editor de template propio en BD, alta de empresas en renta, compra mayorista/asignación de timbres y monitor técnico de servicio.
- **Etapa 5:** ejecución inicial de migraciones limpias en base de datos vacía, generación de \APP_KEY\ y validación de \/plataforma\.
- **Etapa 6:** guardia \operador.plataforma\, registro de cuenta de operador y creación de usuario Propietario al dar de alta una empresa.
- **Siguiente etapa:** módulos y vistas iniciales por giro comercial (\eparacion_computadoras\, \estaurante_cafeteria\, \barrotes\, \erreteria\, \eparacion_celulares\) y paso a la rama \	est\ para pruebas.
