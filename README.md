# 🍷 VinoVault — Sistema de Ventas, Inventario y Kardex de Vinos

Prueba técnica completa implementada con **Laravel** (backend), **Vue 3 + Inertia.js** (frontend), **MySQL** y **Docker (Laravel Sail)**.

El sistema incluye:

- **Gestión de Productos (catálogo puro)**: CRUD con `name`, `brand`, `type`, `presentation`, `sale_price` y `min_stock` (punto de reorden). El producto nace con `current_stock = 0`; **no se ingresa stock al crearlo**.
- **Inventario y Kardex**: historial de movimientos (`entrada`, `venta`, `ajuste`) con stock anterior/nuevo y referencia.
  - **Validación estricta**: no se permiten salidas de stock si la cantidad solicitada supera el stock disponible.
- **Módulo de Ventas**: cabecera + ítems, cálculo automático de subtotales, impuesto y total.
  - **Flujo transaccional obligatorio** en `DB::transaction`: creación de la venta → descuento de stock → registro en kardex → `rollback` total ante cualquier fallo. Se usa `lockForUpdate()` para evitar carreras concurrentes.

---

## Requisitos

- Docker con `docker compose` (plugin v2)
- Git

No es necesario instalar PHP, Composer ni Node en tu máquina; todo corre dentro de los contenedores.

---

## 🚀 Puesta en marcha (paso a paso)

```bash
# 1. Clonar el repositorio
git clone <repo-url> vinos-kardex
cd vinos-kardex

# 2. Crear el archivo de entorno y las variables de Docker
cp .env.example .env

# 3. Instalar dependencias de PHP dentro del contenedor
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$PWD":/var/www/html \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# 4. Generar la clave de la aplicación
docker compose run --rm laravel.test php artisan key:generate

# 5. Levantar los servicios (web + mysql)
docker compose up -d

# 6. Ejecutar migraciones y seeders (datos de ejemplo listos para probar)
docker compose exec laravel.test php artisan migrate:fresh --seed

# 7. Compilar assets de frontend (Vue)
docker compose exec laravel.test npm install
docker compose exec laravel.test npm run build
```

Listo. La aplicación queda disponible en:

| Servicio      | URL                   |
|---------------|-----------------------|
| Aplicación    | http://localhost      |
| Vite (dev)    | http://localhost:5173 |
| MySQL         | localhost:3306        |

> Consejo: para desarrollo con recarga en caliente ejecuta `docker compose exec laravel.test npm run dev`.

### Usuario de prueba (creado por el seeder)

| Campo    | Valor              |
|----------|--------------------|
| Email    | `test@example.com` |
| Password | `password`         |

---

## 🗃️ Estructura de la base de datos

| Tabla                | Descripción                                              |
|----------------------|----------------------------------------------------------|
| `products`           | Vinos (nombre, marca, tipo, presentación, precio y stock) |
| `sales`              | Cabecera de venta con subtotal, impuesto y total          |
| `sale_items`         | Detalle: producto, cantidad, precio unitario y subtotal   |
| `kardex_movements`   | Historial: tipo, cantidad, stock anterior/nuevo, ref.     |

### Relaciones
- `sale_items.sale_id` → `sales.id` (`cascadeOnDelete`)
- `sale_items.product_id` → `products.id` (`restrictOnDelete`)
- `kardex_movements.product_id` → `products.id` (`cascadeOnDelete`)

### Semillas de datos
`ProductSeeder` crea **10 vinos realistas** (Concha y Toro, Santa Rita, Viña Montes, Catena Zapata…) con distintos tipos y presentaciones, y registra su **movimiento inicial de kardex** (`entrada`, referencia `STOCK INICIAL`).

---

## ⚙️ Arquitectura del flujo de venta (integridad estricta)

`App\Http\Controllers\SaleController::store` envuelve **todo** en una única transacción:

```
DB::transaction(function () {
    1. Por cada ítem:
       - SELECT ... FOR UPDATE (bloqueo pesimista del producto)
       - Si stock < cantidad  → lanza excepción de validación (rollback)
       - Descuenta el stock y lo persiste
    2. Crea la venta (subtotal, impuesto y total calculados)
    3. Crea los ítems y el movimiento de kardex ('venta') con stock anterior y nuevo
}, 3);  // 3 reintentos contra deadlocks
---

## 🧪 Tests

Los tests cubren el núcleo del sistema (flujo transaccional, validación estricta y rollback):

```bash
cd vinos-kardex
cp .env.example .env   # en testing se usa la BD "testing" (creada automáticamente por el init de MySQL de Sail)
docker compose exec laravel.test php artisan test
```

Casos incluidos en `tests/Feature/SaleFlowTest.php`:

1. Una venta descuenta stock y registra kardex en una sola transacción (verifica subtotal/total, descuento y `previous_stock`/`new_stock`).
2. Una venta no puede superar el stock disponible → se rechaza con errores y **rollback completo** (0 ventas, 0 ítems, 0 movimientos, stock intacto).
3. Una venta con varios productos calcula totales consistentes y descuenta todos.

Salida esperada:

```
PASS  Tests\Feature\SaleFlowTest
✓ a sale deducts stock and registers kardex in one transaction
✓ a sale cannot exceed available stock
✓ a sale can include multiple products and totals are consistent
Tests:    3 passed (26 assertions)
```

---

## 📂 Rutas principales
|-----------------------------------------------------------------------------------------------|
| GET    | `/dashboard`    | Resumen del inventario (lowStock según `min_stock`)                |
| GET    | `/products`     | Listado de productos (CRUD completo)                               |
| POST   | `/products`     | Crear producto (catálogo puro, stock 0)                            |
| PUT    | `/products/{id}`| Editar ficha + `min_stock`                                         |
| DELETE | `/products/{id}`| Eliminar (sólo si no tiene movimientos de kardex)                  |
| GET    | `/sales`        | Listado de ventas                                                  |
| GET    | `/sales/create` | Punto de venta (agregar ítems + registrar)                         |
| POST   | `/sales`        | Registrar venta (descuenta stock + kardex, transaccional)          |
| GET    | `/sales/{id}`   | Detalle/factura de la venta                                        |
| GET    | `/kardex`       | Historial de movimientos con filtros                               |
| POST   | `/kardex`       | Registrar movimiento `entrada`/`ajuste` (actualiza `current_stock`)|

> El stock físico se abastece/gestiona **exclusivamente** a través de movimientos de kardex (`entrada` / `ajuste`) vía `POST /kardex`, que actualiza `current_stock` y registra el movimiento con stock anterior/nuevo dentro de una transacción con bloqueo pesimista (`lockForUpdate`). Las salidas (`venta`) pasan por `SaleController::store`. Un producto sólo puede eliminarse físicamente si **no tiene movimientos de kardex** registrados.

---

## 🐳 Docker

La dockerización se apoya en **Laravel Sail** (el estándar de Laravel, sin Dockerfiles custom):

- `compose.yaml` → servicios `laravel.test` (PHP + Nginx) y `mysql` (MySQL 8.4), con **volumen persistente** `sail-mysql` y healthcheck.
- El frontend compilado se sirve desde el mismo contenedor web; Vite en `localhost:5173` para desarrollo.

---

## Licencia

Proyecto de prueba técnica. Libre de uso para evaluación.
```

Si **cualquier** paso falla, la transacción revierte por completo: no queda venta creada, el stock vuelve a su valor original y no existe movimiento fantasma en el kardex. Verificado por los tests de feature.