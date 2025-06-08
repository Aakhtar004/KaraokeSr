# 🎤 PrjKaraokeSr - Sistema de Gestión de Karaoke

Sistema integral de gestión para locales de karaoke desarrollado con Laravel. Incluye administración de productos, gestión de usuarios por roles, control de mesas, pedidos y facturación.

## 📋 Tabla de Contenidos

- [✨ Características](#caracteristicas)
- [🖥️ Requisitos del Sistema](#requisitos-del-sistema)
- [🚀 Instalación](#instalacion)
- [⚙️ Configuración](#configuracion)
- [🎯 Uso](#uso)
- [📁 Estructura del Proyecto](#estructura-del-proyecto)
- [👥 Roles de Usuario](#roles-de-usuario)
- [🛣️ Rutas API](#rutas-api)
- [🗄️ Base de Datos](#base-de-datos)
- [🔧 Desarrollo](#desarrollo)
- [🤝 Contribución](#contribucion)
- [📄 Licencia](#licencia)

<a name="caracteristicas"></a>
## ✨ Características

### 🔐 Gestión de Usuarios
- Sistema de autenticación personalizado
- Roles diferenciados (Administrador, Mesero, Cocina, Barra, Facturación)
- Middleware de protección de rutas por tipo de usuario
- Prevención de navegación hacia atrás después del logout

### 👨‍💼 Panel de Administración
- Gestión completa de productos y categorías
- Control de precios y stock
- Administración de usuarios del sistema
- Visualización de historial de ventas
- Gestión de compras

### 🍽️ Gestión de Meseros
- Control de mesas y estados
- Creación y gestión de pedidos
- Historial de pedidos por mesa
- Procesamiento y confirmación de órdenes

### 🏪 Módulos Especializados
- **Cocina**: Gestión de órdenes de comida
- **Barra**: Control de bebidas y cócteles
- **Facturación**: Procesamiento de pagos y facturas

<a name="requisitos-del-sistema"></a>
## 🖥️ Requisitos del Sistema

- **PHP**: ^8.2
- **Laravel Framework**: ^12.0
- **MySQL**: 5.7+ o 8.0+
- **Composer**: 2.0+
- **Node.js**: 16.0+ (para assets)
- **NPM**: 8.0+

<a name="instalacion"></a>
## 🚀 Instalación

1. **Clonar el repositorio**
```bash
git clone <url-del-repositorio>
cd PrjKaraokeSr
```

2. **Instalar dependencias de PHP**
```bash
composer install
```

3. **Instalar dependencias de Node.js**
```bash
npm install
```

4. **Configurar el archivo de entorno**
```bash
cp .env.example .env
```

5. **Generar la clave de aplicación**
```bash
php artisan key:generate
```

6. **Configurar la base de datos** (ver sección [Configuración](#configuracion))

7. **Ejecutar las migraciones**
```bash
php artisan migrate
```

8. **Ejecutar los seeders** (opcional)
```bash
php artisan db:seed
```

<a name="configuracion"></a>
## ⚙️ Configuración

### Base de Datos MySQL

Edita el archivo [.env](.env) con tus credenciales de MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=karaoke_db
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### Configuración de Sesiones

El sistema utiliza sesiones en base de datos por defecto:

```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
```

### Configuración de Cache

Configurado para usar base de datos como driver de cache:

```env
CACHE_STORE=database
```

<a name="uso"></a>
## 🎯 Uso

### Desarrollo

Para iniciar el servidor de desarrollo con todas las herramientas:

```bash
composer run dev
```

Este comando ejecuta:
- Servidor PHP (`php artisan serve`)
- Worker de colas (`php artisan queue:listen`)
- Logs en tiempo real (`php artisan pail`)
- Compilación de assets (`npm run dev`)

### Producción

```bash
# Compilar assets para producción
npm run build

# Iniciar servidor
php artisan serve --host=0.0.0.0 --port=8000
```

<a name="estructura-del-proyecto"></a>
## 📁 Estructura del Proyecto

```
PrjKaraokeSr/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # Controladores del sistema
│   │   └── Middleware/           # Middleware personalizado
│   ├── Models/                   # Modelos Eloquent
│   └── Providers/               # Service Providers
├── config/                      # Archivos de configuración
├── database/
│   ├── migrations/              # Migraciones de BD
│   ├── seeders/                # Seeders de datos
│   └── database.sqlite         # BD SQLite (desarrollo)
├── resources/                   # Vistas y assets
├── routes/
│   └── web.php                 # Rutas web principales
└── storage/                    # Archivos de almacenamiento
```

<a name="roles-de-usuario"></a>
## 👥 Roles de Usuario

El sistema maneja los siguientes roles de usuario definidos en [`CheckTypeUser`](app/Http/Middleware/CheckTypeUser.php):

- **Administrador**: Acceso completo al sistema
- **Mesero**: Gestión de mesas y pedidos
- **Cocina**: Visualización y procesamiento de órdenes de comida
- **Barra**: Gestión de bebidas y cócteles
- **Facturación**: Procesamiento de pagos y facturas

<a name="rutas-api"></a>
## 🛣️ Rutas API

### Rutas de Administrador
```php
GET  /view_admin/admin_modificar_categoria
GET  /view_admin/admin_modificar_producto/{categoria}
PATCH /view_admin/admin_producto/{producto}
GET  /view_admin/admin_historial
GET  /view_admin/admin_compras
GET  /view_admin/admin_gestion_usuarios
POST /view_admin/admin_usuarios
PUT  /view_admin/admin_usuarios/{usuario}
```

### Rutas de Mesero
```php
GET  /view_mozo/mozo_mesa
GET  /view_mozo/mozo_pedido/mesa/{mesa}
GET  /view_mozo/mozo_pedido/historial
POST /view_mozo/mozo_pedido/procesar
POST /view_mozo/mozo_pedido/confirmar
GET  /pedidos/{pedido}
```

Para ver todas las rutas disponibles:
```bash
php artisan route:list
```

<a name="base-de-datos"></a>
## 🗄️ Base de Datos

### Configuración

El proyecto está configurado para usar múltiples drivers de base de datos según el archivo [`database.php`](config/database.php):

- **SQLite** (desarrollo)
- **MySQL** (producción recomendada)
- **PostgreSQL** (alternativa)
- **MariaDB** (compatible)

### Migraciones

```bash
# Ejecutar migraciones
php artisan migrate

# Rollback de migraciones
php artisan migrate:rollback

# Refrescar migraciones
php artisan migrate:refresh
```

<a name="desarrollo"></a>
## 🔧 Desarrollo

### Testing

```bash
# Ejecutar todas las pruebas
composer run test

# Ejecutar pruebas con cobertura
php artisan test --coverage
```

### Linting y Formato

```bash
# Formato de código con Laravel Pint
./vendor/bin/pint

# Análisis estático
./vendor/bin/phpstan analyse
```

### Comandos Artisan Útiles

```bash
# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Ver logs en tiempo real
php artisan pail

# Gestión de colas
php artisan queue:work
php artisan queue:listen
```

<a name="contribucion"></a>
## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

### Estándares de Código

- Seguir PSR-12 para PHP
- Usar Laravel coding standards
- Comentar código complejo
- Escribir tests para nuevas funcionalidades

<a name="licencia"></a>
## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo [LICENSE](LICENSE) para más detalles.

## 📞 Soporte

Para soporte técnico o consultas:

- Crear un issue en GitHub
- Revisar la [documentación de Laravel](https://laravel.com/docs)
- Consultar [Laravel Bootcamp](https://bootcamp.laravel.com) para guías

---

**Desarrollado con ❤️ usando Laravel Framework**
