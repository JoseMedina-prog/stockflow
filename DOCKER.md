# StockFlow — Guía Docker

Documentación de la configuración Docker del proyecto, los cambios de rendimiento aplicados y los comandos para usarlo día a día.

---

## Qué hace Docker en este proyecto

Docker levanta **2 contenedores**:

| Contenedor       | Servicio | Acceso desde tu PC        |
|------------------|----------|---------------------------|
| `stockflow_app`  | Laravel (PHP) | http://localhost:8000 |
| `stockflow_db`   | MySQL 8  | `127.0.0.1:3307`          |

Tu código en Windows se monta dentro del contenedor para que puedas editarlo con Cursor/VS Code y ver los cambios sin reinstalar nada en tu máquina.

---

## Cambios realizados (optimización de rendimiento)

Las peticiones iban lentas (3–11 s) principalmente porque Docker en Windows lee miles de archivos PHP desde el disco de Windows a través de un puente lento. Se aplicaron estas mejoras:

### 1. Volúmenes separados para dependencias

En `docker-compose.yml`:

```yaml
volumes:
  - .:/var/www/html                              # Tu código (Windows)
  - vendor_data:/var/www/html/vendor             # Dependencias PHP (Linux, rápido)
  - node_modules_data:/var/www/html/node_modules # Dependencias JS (Linux, rápido)
```

- **Tu código** (`app/`, `routes/`, `resources/`, etc.) sigue en Windows.
- **`vendor/`** y **`node_modules/`** viven en volúmenes de Docker (filesystem Linux), mucho más rápidos para PHP.

### 2. Caché y sesión en archivos (no en MySQL)

En `.env`:

```env
CACHE_STORE=file
SESSION_DRIVER=file
DB_HOST=db
```

Antes usaban `database`, lo que añadía consultas extra a MySQL en cada petición.

### 3. Sin Vite dev en paralelo

Antes el contenedor corría Laravel **y** `npm run dev` a la vez. Ahora solo Laravel con assets ya compilados en `public/build/`. Menos procesos y menos I/O.

### 4. Script de arranque automático

Archivo: `docker/entrypoint.sh`

Al iniciar el contenedor, el script:

1. Ejecuta `composer install` si no existe `vendor/`
2. Ejecuta `npm ci` / `npm install` si no existe `node_modules/`
3. Ejecuta `npm run build` si no existe `public/build/manifest.json`
4. Arranca Laravel con `php artisan serve --host=0.0.0.0 --port=8000 --no-reload`

### 5. Servidor PHP con workers

Con `--no-reload` se activan los 4 workers definidos en `.env`:

```env
PHP_CLI_SERVER_WORKERS=4
```

### 6. Imagen base actualizada

El `Dockerfile` usa `php:8.2-cli` (antes `php:8.2-fpm`), más adecuado para `artisan serve`.

### Resultado aproximado

| Antes              | Después                          |
|--------------------|----------------------------------|
| 3–11 s por ruta    | ~1.7 s primera petición (fría)   |
|                    | ~200 ms peticiones siguientes    |

---

## Requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado y en ejecución
- PowerShell o terminal en la raíz del proyecto

---

## Comandos esenciales

Todos los comandos se ejecutan desde la carpeta del proyecto:

```powershell
cd C:\Users\felip\Downloads\StockFlow
```

### Primera vez (setup completo)

```powershell
docker compose build
docker compose up -d
docker compose exec app php artisan migrate --seed
```

La primera subida puede tardar varios minutos (instala Composer + npm en los volúmenes).

### Arrancar (uso diario)

```powershell
docker compose up -d
```

Abre: **http://localhost:8000**

Credenciales demo:

```
email:    admin@stockflow.test
password: password
```

### Ver si está corriendo

```powershell
docker compose ps
```

### Ver logs

```powershell
# Logs de todos los servicios (seguimiento en tiempo real)
docker compose logs -f

# Solo logs de Laravel
docker compose logs -f app

# Solo logs de MySQL
docker compose logs -f db

# Últimas 50 líneas sin seguimiento
docker compose logs --tail 50 app
```

Salir del seguimiento en tiempo real: `Ctrl+C` (no detiene los contenedores).

### Parar sin borrar datos

```powershell
docker compose stop
```

Volver a arrancar:

```powershell
docker compose start
```

### Parar y quitar contenedores (datos se conservan)

```powershell
docker compose down
```

Los volúmenes (`dbdata`, `vendor_data`, `node_modules_data`) **no** se borran.

---

## Limpiar y resetear

### Reiniciar solo la app

```powershell
docker compose restart app
```

### Limpiar caché de Laravel (tras cambiar `.env`)

```powershell
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
```

### Borrar TODO (incluida la base de datos)

```powershell
docker compose down -v
```

El flag `-v` elimina los volúmenes:

| Volumen              | Qué se pierde                          |
|----------------------|----------------------------------------|
| `dbdata`             | Base de datos MySQL                    |
| `vendor_data`        | Dependencias PHP (se reinstalan solas) |
| `node_modules_data`  | Dependencias JS (se reinstalan solas)  |

Después de un reset completo:

```powershell
docker compose up -d
docker compose exec app php artisan migrate --seed
```

### Reconstruir imagen (si cambias Dockerfile o entrypoint)

```powershell
docker compose down
docker compose build
docker compose up -d
```

---

## Comandos dentro del contenedor

Entrar a una terminal Linux dentro del contenedor:

```powershell
docker compose exec app bash
```

Comandos útiles dentro:

```bash
php artisan migrate
php artisan migrate --seed
php artisan test
npm run build
composer install
exit
```

Sin entrar al contenedor (desde PowerShell):

```powershell
docker compose exec app php artisan migrate --seed
docker compose exec app npm run build
docker compose exec app php artisan test
docker compose exec app php artisan config:show session.driver
```

---

## Frontend (Vue / TypeScript)

Con la configuración normal **no hay hot reload**. Tras editar archivos `.vue` o `.ts`:

```powershell
docker compose exec app npm run build
```

Recarga el navegador.

### Modo desarrollo con hot reload (Vite)

```powershell
docker compose run --service-ports --rm app dev
```

Levanta Laravel + Vite. Para detener: `Ctrl+C`.

---

## Flujo típico del día a día

```powershell
cd C:\Users\felip\Downloads\StockFlow
docker compose up -d
# ... trabajar en el código ...
docker compose stop
```

---

## Estructura de archivos Docker

```
StockFlow/
├── docker-compose.yml      # Define app + db + volúmenes
├── Dockerfile              # Imagen PHP 8.2 + Composer + Node
├── docker/
│   ├── entrypoint.sh       # Arranque automático
│   └── php/
│       └── opcache.ini     # Configuración OPcache
└── .env                    # DB_HOST=db, CACHE/SESSION=file
```

---

## Diagrama

```
Tu PC (Windows)
├── C:\...\StockFlow\          ← Tu código (app/, routes/, resources/)
│
└── Docker
    ├── stockflow_app          ← Laravel :8000
    │   ├── vendor/            ← volumen rápido (Linux)
    │   └── node_modules/      ← volumen rápido (Linux)
    │
    └── stockflow_db           ← MySQL :3307
        └── dbdata             ← volumen con los datos
```

---

## Chuleta rápida

| Acción                    | Comando                                      |
|---------------------------|----------------------------------------------|
| Arrancar                  | `docker compose up -d`                       |
| Parar                     | `docker compose stop`                        |
| Ver logs (tiempo real)    | `docker compose logs -f app`                 |
| Ver estado                | `docker compose ps`                          |
| Entrar al contenedor      | `docker compose exec app bash`               |
| Migrar + seed             | `docker compose exec app php artisan migrate --seed` |
| Compilar frontend         | `docker compose exec app npm run build`      |
| Limpiar caché Laravel     | `docker compose exec app php artisan config:clear` |
| Reset total (borra BD)    | `docker compose down -v`                     |
| Reconstruir imagen        | `docker compose build`                       |
