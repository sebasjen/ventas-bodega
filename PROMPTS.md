prompt
 1 
Actúa como arquitecto de software y analista de sistemas. Basándote en el archivo D0.md del minimarket «La Esquina» que creamos antes, genera dos archivos obligatorios:
1. requirements.md: Criterios de aceptación detallados escritos estrictamente en formato EARS ('CUANDO [evento] EL SISTEMA DEBERÁ [respuesta]'), identificados claramente como RF1 a RF4 y RNF, asegurando que cada regla de negocio (RN1 a RN5) esté trazada.
2. design.md: La arquitectura organizada en 5 capas estrictas (Model → Repository → Service → Controller → views), explicando detalladamente qué hace y qué no hace cada capa. Incluye además el diseño de las tablas producto y venta con sus campos, tipos de datos, llaves primarias/foráneas, restricciones UNIQUE y CHECK (precio ≥ 0, stock ≥ 0, cantidad > 0). Finalmente, describe los contratos de clases con firmas tipadas, el manejo de transacciones para la venta y el descuento de stock atómico, y el manejo de excepciones.


