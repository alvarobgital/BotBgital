# BotBgital CRM & Automatización de WhatsApp

Un sistema integral de facturación, CRM y bot conversacional para WhatsApp, diseñado para ofrecer una experiencia escalable, intuitiva y profesional. Gestiona clientes, automatiza respuestas, proporciona soporte técnico y procesa prospectos de venta con una interfaz moderna y fluida.

---

## 🚀 Características Principales

### 🤖 Bot Conversacional Engine
- **Motor Dinámico (BotEngineService):** Respuestas automatizadas fluidas con validaciones completas, sin interrupciones ni bloqueos de sesión. Ejecución de flujos lógicos con soporte multimedial.
- **Flujos Visuales (FlowEditor):** Creación y edición de flujos transaccionales mediante un editor visual en el Panel de Administración (ej: Bienvenida, Soporte, Validación de Cliente).
- **Atajos Globales:** Control conversacional 24/7 con atajos como `menú`, `inicio`, `reiniciar`, `nueva conversacion`, permitiendo al usuario volver al estado inicial en cualquier momento.
- **Soporte Multimedia:** Envío de imágenes y documentos (PDF) de forma integrada dentro de cada paso del flujo, ideal para guías y soporte técnico.
- **Seguridad UTF-8:** Compatibilidad absoluta con emojis y caracteres especiales mexicanos dentro de listas e interfaces interactivas de WhatsApp (Meta API).

### 👥 CRM y Gestión de Clientes
- **Módulo de Coberturas:** Verificación automática de la disponibilidad del servicio por Código Postal (CP), detectando al instante zonas válidas.
- **Gestión de Leads (Prospectos):** Almacenamiento ágil de usuarios interesados, organizados por estado (Pendiente, Contactado, Descartado, etc.), incluyendo interés por categorías.
- **Administración de Clientes y Servicios:** Asignación de servicios a clientes mediante números de cuenta fijos y validación estricta desde el Bot.
- **Planes Dinámicos:** Mantenimiento de catálogo de planes (Hogar, Negocio, Dedicado) visualizados como menús desplegables directo en el celular del cliente.
- **Archivado e Historial de Conversaciones:** Módulo de chat en vivo y posibilidad de eliminar definitivamente conversaciones del historial.

### 🔔 Notificaciones y Configuraciones
- **Integración Telegram:** Escalamiento en tiempo real. Cuando un usuario requiere un humano (ej., soporte urgente o interés de venta), el Bot alerta de inmediato al grupo de asesores asignado en Telegram.
- **Ajustes de Interfaz Blanca (White Label):** Configuración visual que incluye la posibilidad de cargar logotipos personalizados en la plataforma y visualizarlos en el panel lateral.

---

## 💻 Stack Tecnológico y Versiones

Este producto fue construido bajo las mejores prácticas y estándares de la industria, asegurando su fiabilidad en despliegues en la nube.

*   **Backend Framework:** Laravel 11.x
*   **Lenguaje:** PHP 8.2+
*   **Base de Datos:** MySQL / MariaDB (Optimizado con Eloquent ORM)
*   **Frontend y Vistas:** React 18 con Inertia.js (sin recargas de página)
*   **Estilizado (CSS):** Tailwind CSS / Vanilla CSS moderno y adaptable (Glassmorphism, animaciones fluidas).
*   **Iconografía:** Lucide React
*   **Integraciones API:**
    *   **WhatsApp Cloud API (Meta API v22.0):** Emisión y recepción asíncrona mediante Webhooks seguros.
    *   **Telegram Bot API:** Transmisión ultrarrápida de Leads y Escalamientos para staff humano.
*   **Gestor de Colas:** Laravel Queue (Database Driver) para procesamiento en segundo plano.

---

## 🛠 Instalación y Despliegue

### Requisitos Previos

- Servidor web habilitado con PHP 8.2+
- Composer & Node.js (v18+)
- Cuenta Meta Developers activada en modo Producción.
- Bot de Telegram activo y en grupo administrador.

### Pasos Iniciales

1.  **Clonar este repositorio** en su servidor o entorno de pruebas (ej: Laravel Herd / Hostinger).
2.  **Instalar dependencias:**
    ```bash
    composer install --optimize-autoloader --no-dev
    npm install
    npm run build
    ```
3.  **Configurar Variables de Entorno (`.env`):**
    Copie el archivo de ejemplo y defina las variables críticas, especialmente de Meta y Base de Datos:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    *(Asegúrese de definir correctamente `WHATSAPP_ACCESS_TOKEN`, `WHATSAPP_WEBHOOK_VERIFY_TOKEN` y url del sistema en `APP_URL` y variables `SANCTUM`)*.
4.  **Ejecutar Migraciones y Seeders:**
    ```bash
    php artisan migrate --force
    php artisan db:seed
    ```
5.  **Iniciar Trabajadores de Cola (Queues):**
    Para manejar los Webhooks sin retardo, se debe mantener activo el worker:
    ```bash
    php artisan queue:work --timeout=90 --tries=3
    ```

---

## 🔒 Arquitectura de Seguridad

*   Tokens de validación Webhook estrictos blindando inyecciones externas.
*   Sanitización multibyte (`mb_substr`) en el manejo masivo de carga útil para prevenir rupturas en los esquemas JSON de WhatsApp API.
*   Rutas protegidas por Auth y middleware personalizado para garantizar permisos granulares de administrador.
*   Normalización automática de lada mexicana (`+521` a `+52`) para acatar las reglas técnicas del Graph API de Meta.

<br><br>

> *Desarrollado y optimizado por **Alvarobgital**.*
