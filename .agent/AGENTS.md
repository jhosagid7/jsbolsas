# Reglas del Proyecto (JSBolsas Pro)

## Aislamiento Estricto de Proyectos (REGLA CRÍTICA)
- **Identidad Exclusiva:** Este directorio (C:\laragon\www\jsbolsas) pertenece ÚNICA Y EXCLUSIVAMENTE a **JSBolsas Pro** (Sistema Industrial de Fábrica de Bolsas, Báscula, Costos de Resina, Fórmulas de Mezcla, Máquinas y App Móvil de Operarios).
- **Prohibición de Mezcla:** NUNCA se debe aplicar lógica comercial de Punto de Venta de **JSPOS Sales** que altere el flujo industrial de este sistema.
- **Control de Contexto:** El agente NUNCA debe cambiar de proyecto por iniciativa propia. Solo el usuario indicará explícitamente cuándo cambiar de proyecto.

## Pruebas Obligatorias (TDD y Verificación)
- **Ejecución y Creación de Pruebas Obligatoria:** En cada modificación de código, corrección de errores o nueva funcionalidad, el agente **DEBE** escribir y ejecutar pruebas unitarias, de integración o de regresión en Laravel (php artisan test) para validar empíricamente que los cambios funcionan correctamente y no rompen ninguna regla del negocio.
- **Validación Final:** Ninguna tarea se considerará finalizada sin haber ejecutado las pruebas correspondientes y mostrado los resultados limpios al usuario.
