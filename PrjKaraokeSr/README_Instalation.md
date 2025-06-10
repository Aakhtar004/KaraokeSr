# 🎤 Sistema de Karaoke - Guía de Instalación Completa

## 📋 Prerequisitos

### Software Requerido:
- **PHP 8.2+** ([Descargar](https://windows.php.net/download/))
- **Composer** ([Descargar](https://getcomposer.org/download/))
- **Node.js 18+** ([Descargar](https://nodejs.org/))
- **MySQL 8.0+** (XAMPP/WAMP recomendado)
- **Git** (para clonar el repositorio)

### Extensiones PHP Requeridas:
- php-mysql
- php-xml
- php-curl
- php-mbstring
- php-zip

---

## 🚀 Instalación Paso a Paso

### Paso 1: Configurar el Entorno Local

#### 1.1 Verificar Instalaciones
```bash
# Verificar PHP
php --version

# Verificar Composer
composer --version

# Verificar Node.js
node --version

# Verificar npm
npm --version
```

#### 1.2 Iniciar Servicios
1. Abrir XAMPP Control Panel
2. Iniciar Apache y MySQL
3. Verificar que estén corriendo en: http://localhost/

### Paso 2: Descargar el Proyecto

```bash
# Clonar el repositorio
git clone https://github.com/tu-usuario/PrjKaraokeSr.git

# O descargar y extraer el ZIP del proyecto
# Ubicar en: c:\xampp\htdocs\PrjKaraokeSr
# Asegúrate de que tienes el archivo karaokedb.sql en el directorio raíz

# Navegar al directorio del proyecto
cd c:\xampp\htdocs\PrjKaraokeSr
```

### Paso 3: Configurar Base de Datos

#### 3.1 Crear Base de Datos
1. Abrir **HeidiSQL**
2. Conectar a tu servidor MySQL local
3. Crear nueva base de datos:
   - **Nombre:** `karaokedb`
   - **Cotejamiento:** `utf8mb4_unicode_ci`

#### 3.2 Importar Base de Datos (OBLIGATORIO)
**El archivo `karaokedb.sql` contiene toda la estructura y datos necesarios:**

1. En HeidiSQL, seleccionar la base de datos `karaokedb`
2. Ir al menú **"Archivo"** → **"Cargar archivo SQL"**
3. Buscar y seleccionar el archivo `karaokedb.sql` del directorio raíz del proyecto
4. Ejecutar el script SQL
5. Esperar a que termine la importación (puede tomar unos minutos)

**✅ La importación debe completarse sin errores**

### Paso 4: Configurar Laravel

#### 4.1 Instalar Dependencias y modificacion de archivo

```bash
#Ingresar a la siguiente ruta
cd C:\xampp\php\php.ini
```
Buscar ;extension=zip, quitar la (;), guardar el archivo y reiniciar xampp


```bash
# Instalar dependencias de PHP
composer install

# Instalar dependencias de Node.js
npm install
```

#### 4.2 Configurar Variables de Entorno
```bash
# Copiar archivo de configuración (ya está configurado correctamente)
copy .env.example .env

# Generar clave de aplicación
php artisan key:generate
```

**IMPORTANTE:** El archivo `.env.example` ya está configurado correctamente para usar la base de datos `karaokedb` con MySQL local.

### Paso 5: Configurar Base de Datos

#### 5.1 Verificar Importación de Base de Datos
```bash
# Verificar que las tablas se crearon correctamente
php artisan tinker
```

En el shell de Tinker, ejecuta:
```php
# Verificar conexión a la base de datos
DB::connection()->getPdo();

# Contar tablas importadas
collect(DB::select('SHOW TABLES'))->count();

# Verificar usuarios del sistema
DB::table('gusers')->count();

# Salir de Tinker
exit
```

**NO ejecutar migraciones** ya que la base de datos viene completa con el archivo SQL.

### Paso 6: Compilar Assets

```bash
# Compilar assets para desarrollo
npm run dev

# O compilar para producción
npm run build

# Para desarrollo con hot reload
npm run watch
```

### Paso 7: Configurar Permisos (Solo Linux/Mac)

```bash
# Dar permisos a carpetas de almacenamiento
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Cambiar propietario
sudo chown -R www-data:www-data storage
sudo chown -R www-data:www-data bootstrap/cache
```

### Paso 8: Iniciar el Servidor

Con XAMPP ya tienes Apache corriendo, por lo que el sistema estará disponible directamente:

**El sistema estará disponible en:** http://localhost/PrjKaraokeSr/public

**Opcional - Servidor Laravel:**
```bash
# Si prefieres usar el servidor de desarrollo de Laravel
php artisan serve

# Esto lo hará disponible en: http://localhost:8000
```

---

## 🧪 Verificar Instalación

## 🔧 Solución de Problemas Comunes

### Generar clave de aplicación
```bash
php artisan key:generate
```

### Verificar base de datos
- Confirmar que `karaokedb` existe en heidi
- Revisar configuración en archivo `.env`
- Verificar que MySQL esté funcionando

### Reimportar base de datos
- Eliminar base de datos `karaokedb` si existe
- Crear nueva base de datos `karaokedb`
- Importar nuevamente el archivo `karaokedb.sql`

### Compilar assets
```bash
npm install
npm run dev
```

### Cambiar puerto del servidor
```bash
php artisan serve --port=8001
```

### Limpiar cache
```bash
php artisan cache:clear
php artisan config:clear
```

---

## 🔄 Comandos Útiles para Desarrollo

```bash
# Ver rutas disponibles
php artisan route:list

# Crear nuevo controlador
php artisan make:controller NombreController

# Crear nuevo modelo con migración
php artisan make:model NombreModelo -m

# Ejecutar tests
php artisan test

# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Verificar estado de la base de datos
php artisan tinker
```

**NOTA:** No usar `php artisan migrate` ni `php artisan db:seed` ya que la base de datos se importa completa desde el archivo SQL.

---

## 📱 Funcionalidades del Sistema

### 🔐 Sistema de Autenticación
- Login personalizado con guard `gusers`
- Roles diferenciados (Admin, Mesero, Cocinero, Bartender)
- Middleware de protección por rol

### 🍽️ Gestión de Pedidos
- **Meseros**: Tomar pedidos por mesa
- **Cocina**: Preparar productos de cocina
- **Bar**: Preparar bebidas y cocteles
- **Estados**: SOLICITADO → LISTO_PARA_ENTREGA

### 💰 Sistema de Facturación
- Emisión de Boletas y Facturas
- Métodos de pago múltiples
- Cálculo automático de IGV
- Vista previa de comprobantes

### 📊 Gestión de Inventario
- Control de stock en tiempo real
- Productos por categorías
- Asignación automática a cocina/bar

### 🏢 Administración
- CRUD completo de usuarios
- Gestión de productos y categorías
- Reportes y estadísticas

---

## 🌐 URLs Principales

| Función | URL | Acceso |
|---------|-----|--------|
| Login | `/login` | Público |
| Dashboard Admin | `/view_admin/admin_user_menu` | Administrador |
| Historial Mesero | `/view_mozo/mozo_historial` | Mesero |
| Cocina | `/view_cocina/cocina_historial` | Cocinero |
| Bar | `/view_barra/barra_historial` | Bartender |

---

## 📁 Estructura del Proyecto

```
PrjKaraokeSr/
├── karaokedb.sql          # ← Archivo de base de datos completa
├── README.md              # ← Documentación principal
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   └── Middleware/
├── database/
│   └── migrations/        # ← Solo para referencia, no usar
├── public/
├── resources/
│   ├── views/
│   ├── js/
│   └── css/
├── routes/
├── storage/
└── vendor/
```

**IMPORTANTE:** El archivo `karaokedb.sql` contiene:
- Estructura completa de tablas
- Datos de usuarios del sistema
- Productos y categorías de ejemplo
- Configuración de mesas
- Todos los datos necesarios para el funcionamiento

---

## 🔒 Seguridad y Buenas Prácticas

### Autenticación
- Contraseñas hasheadas con bcrypt
- Sesiones seguras con CSRF protection
- Middleware de autorización por roles

### Base de Datos
- Migraciones versionadas
- Relaciones con foreign keys
- Validación de datos en controladores

### Frontend
- Componentes Blade reutilizables
- Bootstrap 5 para UI responsiva
- Validación JavaScript en tiempo real

---

## 🚀 Siguientes Pasos de Desarrollo

### Prioridad Alta
- **Sistema de Reportes**: Implementar dashboard de analytics
- **Notificaciones**: Sistema en tiempo real para pedidos
- **Backup Automático**: Implementar respaldos programados

### Prioridad Media
- **API REST**: Para integración con app móvil
- **Módulo de Reservas**: Sistema de reserva de mesas
- **Integración SUNAT**: Facturación electrónica real

### Prioridad Baja
- **Módulo Karaoke**: Funcionalidad de karaoke completa
- **App Móvil**: Aplicación para meseros
- **Sistema de Loyalty**: Programa de puntos

---

## 📞 Contacto y Soporte

Si encuentras algún problema durante la instalación:

1. Revisa esta guía paso a paso
2. Verifica prerequisitos (PHP, MySQL, etc.)
3. Consulta los logs de Laravel en `storage/logs/`
4. Revisa la consola del navegador para errores JavaScript

### Logs Importantes
```bash
# Ver logs de Laravel
tail -f storage/logs/laravel.log

# Ver logs de Apache (XAMPP)
tail -f C:\xampp\apache\logs\error.log

# Ver logs de MySQL (XAMPP)
tail -f C:\xampp\mysql\data\mysql_error.log
```

---

## ¡Instalación Completada! 🎉

El Sistema de Karaoke está listo para usar. Accede con las credenciales proporcionadas y comienza a explorar todas las funcionalidades.

**URL de Acceso:** http://localhost/PrjKaraokeSr/public  
**Usuario Admin:** admin / admin123
