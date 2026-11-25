# ERP-Multitenant

Plataforma ERP multitenant organizada por **giro comercial**, **aislamiento de datos sin datos de prueba iniciales** y **gestión dinámica de páginas para clientes en renta**.

## Estructura de giros
- \giros/reparacion_computadoras\
- \giros/restaurante_cafeteria\
- \giros/abarrotes\
- \giros/ferreteria\
- \giros/reparacion_celulares\

## Principios y reglas de trabajo
1. **Separación estricta por giro:** cada giro tiene su espacio dedicado para evitar cruces o daños al modificar código.
2. **Arranque limpio sin datos de prueba:** las bases de datos y migraciones parten vacías/limpias; las empresas de prueba se crean bajo demanda para el giro que se esté probando.
3. **Gestión de páginas para clientes en renta:** capacidad de crear/configurar empresas con su giro fijo, administrar qué páginas/bloques tienen habilitados y controlar la apariencia y delegación del propietario.
4. **Documentación continua:** todo cambio relevante, decisión técnica, comando de prueba y estado de módulos queda asentado aquí en \README.md\.

## Flujo de ramas Git
- \main\: versión estable y aprobada tras pruebas.
- \develop\: integración y desarrollo activo (rama de trabajo actual).
- \	est\: rama de pruebas; recibe de \develop\, se prueban cambios y se regresan ajustes a \develop\ antes de unir a \main\.

## Bitácora de avances
- **Base inicial:** repositorio limpio en \Carlos89Novela/ERP-Multitenant\ con ramas \main\, \develop\ y \	est\, \.gitignore\ público seguro y estructura de carpetas para los 5 giros.
- **Siguiente etapa:** esquema de base de datos limpio (empresas, sucursales, roles/delegación, configuración visual y páginas/bloques) + generador de empresa de prueba por giro.
