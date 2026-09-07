# PROTOCOLO DE SEGURIDAD Y DESARROLLO MULTI-PROYECTO (V1)
# REGLAS DE AISLAMIENTO, CONTROL DE VERSIONES Y DEPLOYMENT SEGURO

Este documento establece el protocolo obligatorio de desarrollo que debes seguir para todos los proyectos de software del Administrador. No se permite ninguna excepción a estas reglas.

---

## 1. CATÁLOGO DE PROYECTOS ACTIVOS Y AUTORIZADOS

Actualmente trabajamos con los siguientes proyectos, los cuales deben mantenerse completamente independientes y aislados en tu espacio de trabajo local, remoto y de producción:

1.  **JSBolsas Pro** (Sistema de costeo, producción y APK móvil).
2.  **jspos-sales** (Sistema comercial y de ventas, cuenta con repositorio).
3.  **jsserver** (Servidor / backend de soporte, cuenta con repositorio).
4.  **Nuevos Proyectos** (Cualquier sistema nuevo que se inicie de ahora en adelante).

---

## 2. REGLA OBLIGATORIA: "GIT-FIRST" (REPOSITORIO OBLIGATORIO)

Antes de escribir una sola línea de código en cualquier proyecto existente o nuevo, se debe verificar y asegurar la existencia de un repositorio de Git:

*   **Si el proyecto ya tiene un repositorio (ej. JSBolsas Pro, jspos-sales, jsserver):**
    *   Debes trabajar exclusivamente en tu entorno local sincronizado con dicho repositorio.
*   **Si el proyecto NO tiene un repositorio en GitHub:**
    *   **Tu primera y única tarea obligatoria antes de programar es crear el repositorio.**
    *   Debes inicializar Git localmente (`git init`), crear el repositorio en GitHub, enlazar el origen remoto (`git remote add origin`) y realizar el primer commit inicial con la estructura base.
    *   No se permite avanzar con lógica de negocio o desarrollo si el repositorio no está configurado y confirmado por el Administrador.

---

## 3. PROTOCOLO DE SESIÓN (CONFIRMACIÓN DE CONTEXTO)

Al inicio de cada conversación o sesión de trabajo, **debes autoevaluarte** y solicitar confirmación obligatoria al Administrador enviando el siguiente mensaje de control:

> *"Hola. Antes de comenzar a trabajar, necesito que me confirmes los detalles de nuestro contexto actual para evitar cualquier cruce de archivos:*
> 1. **¿En qué proyecto vamos a trabajar hoy?** *(JSBolsas Pro / jspos-sales / jsserver / [Nuevo Proyecto])*
> 2. **Por favor, confírmame las rutas locales de trabajo y el nombre de la base de datos activa para este proyecto.**
> *No realizaré ningún cambio hasta que me confirmes estos datos para asegurar un aislamiento del 100%."*

---

## 4. FLUJO DE TRABAJO LOCAL-FIRST Y DESPLIEGUE SEGURO (PROHIBIDO EDITAR EN VPS)

Queda estrictamente prohibido modificar, subir, arrastrar o editar archivos directamente sobre el servidor VPS de producción. El flujo de desarrollo debe ser estrictamente el siguiente:

1.  **Fase de Desarrollo (Local):** Realizas los cambios, pruebas y correcciones únicamente en el entorno local (computadora de desarrollo).
2.  **Fase de Respaldo y Versión (GitHub):** Subes tus cambios probados al repositorio correspondiente en GitHub mediante comandos limpios (`git add`, `git commit`, `git push`).
3.  **Fase de Lanzamiento (Releases):** Utilizas el sistema de lanzamientos/etiquetas (`git tag` / GitHub Releases) para marcar las versiones estables (ej. `v1.0.1`).
4.  **Fase de Despliegue (VPS):** En el servidor VPS únicamente se descargará el código mediante comandos de Git (`git pull` o `git checkout release`). Ninguna IA ni desarrollador editará archivos directamente en el VPS de producción.

---

## 5. AISLAMIENTO DE BASES DE DATOS

*   Cada proyecto debe utilizar de manera estricta su propia base de datos independiente (ej. `jsbolsas_db` para JSBolsas, y bases de datos separadas para `jspos-sales` y `jsserver`).
*   Bajo ninguna circunstancia debes ejecutar migraciones, scripts SQL o pruebas en una base de datos que no corresponda al proyecto confirmado en la sesión.
