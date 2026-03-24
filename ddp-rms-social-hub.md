# Documento de Definición de Proyecto: RMS Social Hub

## 1. Visión General

**RMS Social Hub** es una plataforma centralizada de gestión de redes sociales diseñada para **Raven Marketing Services**. El objetivo primario es la orquestación, programación y publicación de contenido multimedia (Post, Reels, Stories) en múltiples Fan Pages de Facebook, integrando posteriormente analítica avanzada de Ads e Insights.

----------

## 2. Requerimientos y Funcionalidades (Ampliadas)

### A. Gestión de Conectividad (Meta API)

-   **Conexión via Business Manager:** Integración con el portafolio de negocios para obtener acceso a múltiples cuentas desde un solo flujo.
    
-   **Gestión de Tokens de Larga Duración:** Sistema automático de refresco de tokens para evitar la desconexión de páginas (Long-lived User Tokens & Page Tokens).
    
-   **Selector de Páginas:** Interfaz para que el Administrador vincule o desvincule páginas específicas del inventario de la App.
    

### B. Módulo de Publicación y Programación

-   **Editor de Contenido Multiformato:**
    
    -   Soporte para imágenes (JPEG, PNG) y videos (MP4, MOV).
        
    -   Validación automática de _aspect ratio_ para Reels (9:16) y Stories.
        
-   **Motor de Programación (Scheduling Engine):**
    
    -   Calendario visual para gestión de posts.
        
    -   **Sistema de Estados:** `Borrador`, `Programado`, `Enviando`, `Publicado`, `Fallido`.
        
    -   Lógica de reintentos (_Retry Logic_) ante errores de servidor de Meta.
        
-   **Previsualización Dinámica:** Renderizado en tiempo real de cómo se verá el post en móvil y desktop (Vue components).
    

### C. Sistema de Usuarios y Permisos (RBAC)

-   **Super Admin:** Control total de la infraestructura y llaves de API (App Secret/ID).
    
-   **Administrador:** Gestión de clientes, páginas y asignación de editores.
    
-   **Editor:** Operación diaria (creación y programación) limitada a las páginas asignadas por el Admin.
    

### D. Infraestructura de Notificaciones

-   **Alertas de Error:** Notificación inmediata vía App/Email si un post programado falla.
    
-   **Confirmación de Publicación:** Notificación de éxito con enlace directo al post generado.
    

----------

## 3. Stack Técnico Detallado

| Capa           | Tecnología                       | Justificación                                                              |
| -------------- | -------------------------------- | -------------------------------------------------------------------------- |
| Backend        | Laravel 11                       | Robustez en el manejo de Queues, Jobs y Programación de tareas.            |
| Frontend       | Vue 3 + Inertia.js               | SPA feel con la simplicidad de rutas de Laravel.                           |
| Estilos        | Tailwind CSS                     | Desarrollo rápido de UI modular y responsiva.                              |
| Base de Datos  | PostgreSQL / MySQL               | Relacional para manejar la jerarquía BM -> Portafolio -> Páginas -> Posts. |
| Colas (Queues) | Redis                            | Crítico para el manejo asíncrono de subida de videos pesados (Reels).      |
| Almacenamiento | Almacenamiento local             | Persistencia de medios antes de ser enviados a Facebook.                   |
| Validación     | Laravel Request Validation + Zod | Doble capa de validación para asegurar integridad de datos.                |

----------

## 4. Scope Rules y Arquitectura de Software

### A. Modularidad y Estructura (Vertical Slicing)

Siguiendo la **Scope Rule**, el código se organizará por módulos funcionales. Si un componente o lógica pertenece exclusivamente a un módulo, no debe salir de su carpeta.

-   `app/Modules/Facebook/` (API Wrapper, DTOs de Meta).
    
-   `app/Modules/Publisher/` (Lógica de creación, scheduling y estados).
    
-   `resources/js/Pages/Publisher/Partials/` (Componentes exclusivos de edición).
    

### B. Metodología TDD (Test Driven Development)

-   **Pruebas de Contrato:** Mocking estricto de la Meta Graph API para asegurar que los cambios en la API externa no rompan el sistema sin previo aviso.
    
-   **Ciclo Rojo-Verde-Refactor:** Ninguna funcionalidad de publicación se considera terminada sin su correspondiente _Feature Test_ que valide el flujo desde la base de datos hasta la respuesta (falsificada) de la API.
    

### C. Seguridad

-   **Encripción de Tokens:** Todos los Access Tokens de clientes deben almacenarse encriptados en la base de datos (`Crypt::encryptString`).
    
-   **Auditoría:** Registro (Log) de qué usuario publicó qué contenido y en qué página.