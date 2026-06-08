# StockFlow

ERP/CRM integral para tiendas y negocios pequeños/medianos. Construido como proyecto de portafolio con un stack moderno de Laravel.

![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![Vue 3](https://img.shields.io/badge/Vue-3-42B883?logo=vue.js&logoColor=white)
![Inertia v2](https://img.shields.io/badge/Inertia-v2-9553EE)
![TypeScript](https://img.shields.io/badge/TypeScript-5-3178C6?logo=typescript&logoColor=white)
![Tailwind v3](https://img.shields.io/badge/Tailwind-3-06B6D4?logo=tailwindcss&logoColor=white)
![MySQL/SQLite](https://img.shields.io/badge/MySQL%20%2F%20SQLite-4479A1?logo=mysql&logoColor=white)
![PHP 8.2](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)

---

## Tabla de contenidos

- [Stack](#stack)
- [Funcionalidades](#funcionalidades)
- [Capturas de pantalla](#capturas-de-pantalla)
- [Instalación rápida](#instalación-rápida)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Decisiones técnicas](#decisiones-técnicas)
- [Tests](#tests)
- [Mejoras futuras](#mejoras-futuras)
- [Licencia](#licencia)

---

## Stack

| Capa | Tecnología |
|---|---|
| Backend | Laravel 12, PHP 8.2, Eloquent ORM |
| Frontend | Vue 3, Inertia.js v2, TypeScript, Vite |
| UI | shadcn-vue (reka-ui), Tailwind CSS v3, Lucide icons |
| Base de datos | MySQL 8 (producción) / SQLite (desarrollo) |
| Auth | Laravel Breeze (Inertia) + Spatie Permission |
| Contabilidad | Partidas dobles (custom) — `JournalEntry` + `JournalLine` |
| Tests | PHPUnit 11 |

---

## Funcionalidades

### Plataforma
- **Dashboard** con KPIs (ventas hoy/mes, compras, productos, stock bajo, cuentas por cobrar/pagar, top clientes, top productos, cashflow semanal, mis tareas)
- **Categorías** con contador de productos
- **Productos** con SKU único, precio, stock, stock mínimo, búsqueda y filtro
- **Clientes** con búsqueda, conteo de ventas y total gastado
- **Proveedores** con RFC, datos de contacto, saldos pendientes
- **Settings** con datos fiscales de la empresa

### Operaciones (ERP)
- **Ventas** — Multi-producto, validación de stock, impuestos por item, descuento automático, conversión desde cotización
- **Compras** — Multi-producto, recepción automática, pago a proveedor
- **Devoluciones** — Con nota de crédito o reembolso, reversión de stock, doble validación (pendiente → aprobado/rechazado)
- **Pagos** — Registro contra ventas/compras, saldos parciales
- **Movimientos de inventario** — Auditoría de entradas, salidas, ajustes y transferencias
- **Cotizaciones** — Borrador → enviada → aceptada/rechazada → convertida a venta, con vigencia
- **Notas de crédito** — Generadas automáticamente al aprobar devoluciones

### CRM
- **Leads** — Pipeline new → contacted → qualified → proposal → won/lost, conversión a cliente
- **Oportunidades** — Pipeline prospecting → qualification → proposal → negotiation → won/lost con valor ponderado
- **Actividades** — Llamadas, correos, reuniones, notas, mensajes, WhatsApp polimórficas (Customer/Lead/Opp/Sale)
- **Tareas** — Polimórficas (Customer/Lead/Opp/Sale) con prioridad y fecha de vencimiento
- **Customer 360** — Ficha integral con LTV, ticket promedio, saldo, ventas, devoluciones, oportunidades, leads, tareas y línea de tiempo

### Contabilidad
- **Plan contable** (Activo, Pasivo, Capital, Ingresos, Egresos) con jerarquía padre/hijo
- **Catálogo de impuestos** — IVA Trasladado/Acreditable, IEPS, ISR, Retenciones
- **Partidas dobles automáticas** — Cada venta, compra, pago y devolución genera un asiento contable con IVA separado
- **Libro mayor** — Detalle de movimientos por cuenta, con filtro de fechas y cuenta
- **Balance de prueba** — Saldos de cuentas de balance
- **Estado de resultados** — Ingresos, egresos y utilidad del ejercicio
- **Balance general** — Activo, Pasivo, Capital con validación de cuadre (A = P + C)
- **Reporte de impuestos** — IVA trasladado vs acreditable, IEPS, retenciones

### Reglas de negocio clave
- No se vende más unidades que el stock disponible (transacción + `SELECT ... FOR UPDATE`)
- Stock bajo: `stock <= min_stock` (alerta visual en dashboard y listados)
- Integridad referencial: no se eliminan categorías con productos, productos con ventas, clientes con ventas
- Cliente opcional en ventas (consumidor final)
- Toda venta con cliente sin pago genera cuenta por cobrar
- Cada movimiento contable debe cuadrar (Debe = Haber)
- Impuestos calculados por línea y agregados al total

### UX
- Confirm dialog (shadcn-vue) en eliminaciones
- Filtros en URL con `preserveState`
- Formularios con validación 422 visible y mensajes en español
- Tablas con paginación, badges de estado, formato de moneda MXN
- Flash messages (success / error) en todas las operaciones
- Command palette (`Cmd+K`) con búsqueda global
- Tema claro/oscuro persistente

---

## Instalación rápida

### Requisitos
- PHP 8.2 o superior
- Composer 2
- Node 20 o superior
- (Opcional) MySQL 8 — por defecto usa SQLite

### Pasos

```bash
# 1. Clonar e instalar dependencias PHP
git clone <repo-url> stockflow
cd stockflow
composer install

# 2. Instalar dependencias JS
npm install

# 3. Variables de entorno
cp .env.example .env
php artisan key:generate

# 4. (Opcional) Cambiar a MySQL en .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=stockflow
# DB_USERNAME=root
# DB_PASSWORD=

# 5. Migrar y sembrar datos demo
php artisan migrate --seed

# 6. Compilar assets
npm run build

# 7. Levantar el servidor
php artisan serve
# o en desarrollo con hot reload:
composer run dev
```

Visita [http://localhost:8000](http://localhost:8000) y entra con:

```
email:    admin@stockflow.test
password: password
```

### Usuarios demo incluidos

| Email | Rol | Acceso |
|---|---|---|
| admin@stockflow.test | Administrador | Total, incluyendo configuración |
| vendedor@stockflow.test | Vendedor | Ventas, CRM (leads/oportunidades) |
| comprador@stockflow.test | Comprador | Compras, proveedores, pagos |
| contador@stockflow.test | Contador | Lectura + contabilidad y reportes |

### Datos demo incluidos
- 36 cuentas contables (plan base mexicano) + 7 impuestos (IVA 16%, IVA 8%, IEPS, retenciones)
- 1 usuario administrador + 3 usuarios con roles
- 5 categorías con 8 productos cada una (40 productos)
- 3 productos extra con stock bajo
- 10 clientes
- 6 ventas con sus items, IVA incluido y asientos contables generados

---

## Estructura del proyecto

```
app/
├── Enums/                  ← Enums de dominio (AccountType, QuoteStatus, TaskStatus, ...)
├── Exceptions/
├── Http/
│   ├── Controllers/
│   │   ├── AccountingController.php   ← Reportes contables
│   │   ├── ActivityController.php
│   │   ├── CategoryController.php
│   │   ├── CustomerController.php     ← Incluye Customer 360
│   │   ├── DashboardController.php
│   │   ├── LeadController.php
│   │   ├── OpportunityController.php
│   │   ├── PaymentController.php
│   │   ├── ProductController.php
│   │   ├── PurchaseController.php
│   │   ├── QuoteController.php        ← Cotizaciones
│   │   ├── ReportController.php
│   │   ├── SaleController.php
│   │   ├── SaleReturnController.php
│   │   ├── SearchController.php
│   │   ├── StockMovementController.php
│   │   ├── SupplierController.php
│   │   ├── TaskController.php
│   │   ├── TaxController.php           ← CRUD de impuestos
│   │   └── Settings/...
│   ├── Middleware/HandleInertiaRequests.php
│   └── Requests/
│       ├── Category/  (Store, Update)
│       ├── Customer/  (Store, Update)
│       ├── Lead/      (Store, Update)
│       ├── Opportunity/  (Store, Update, Advance)
│       ├── Payment/   (Store)
│       ├── Product/   (Store, Update)
│       ├── Purchase/  (Store, Update)
│       ├── Quote/     (Store, Update)
│       ├── Sale/      (Store)
│       ├── SaleReturn/  (Store, Approve, Reject)
│       ├── Supplier/  (Store, Update)
│       ├── Task/      (Store, Update)
│       └── Activity/  (Store)
├── Models/                 ← Eloquent (19 modelos)
├── Services/
│   ├── LeadConversionService.php
│   ├── PaymentService.php             ← Genera asientos contables
│   ├── PipelineAdvanceService.php
│   ├── PostingService.php             ← Motor de partidas dobles
│   ├── PurchaseService.php            ← Genera asientos contables
│   ├── ReturnService.php              ← Genera asientos contables
│   ├── SettingService.php
│   ├── StockLedger.php
│   └── TaskCompletionService.php
├── Support/
│   └── FolioGenerator.php
└── Providers/

database/
├── migrations/             (~25 migraciones)
├── factories/               (18 factories)
└── seeders/
    ├── DatabaseSeeder.php
    ├── BusinessSettingsSeeder.php
    ├── ChartOfAccountsSeeder.php      ← Plan contable base
    ├── PurchaseSeeder.php
    ├── RoleAndPermissionSeeder.php
    └── SupplierSeeder.php

resources/js/
├── components/
│   ├── stockflow/          ← Componentes propios (DateRangePicker, StatCard, Timeline, ...)
│   ├── ui/                 ← shadcn-vue local
│   └── AppSidebar.vue
├── composables/
│   ├── useFormat.ts
│   └── usePermissions.ts
├── layouts/AppLayout.vue
└── pages/
    ├── Dashboard.vue
    ├── Categories/  (Index, Create, Edit)
    ├── Products/    (Index, Create, Edit)
    ├── Customers/   (Index, Create, Edit, Show)         ← Customer 360
    ├── Suppliers/   (Index, Create, Edit)
    ├── Sales/       (Index, Create, Show)
    ├── Purchases/   (Index, Create, Edit, Show)
    ├── SaleReturns/ (Index, Create, Show)
    ├── Payments/    (Index)
    ├── StockMovements/ (Index)
    ├── Quotes/      (Index, Create, Edit, Show)         ← Cotizaciones
    ├── Leads/       (Index, Create, Edit, Show)
    ├── Opportunities/ (Index, Create, Edit, Show)
    ├── Tasks/       (Index, Create, Edit, Show)
    ├── Activities/  (Index)
    ├── Reports/     (Index)
    ├── Accounting/  (Index, Ledger, TrialBalance,
    │                  IncomeStatement, BalanceSheet,
    │                  TaxReport, Accounts, Taxes)
    └── settings/

routes/
└── web.php                 ← 135 rutas

tests/
├── Feature/
│   ├── Auth/...
│   ├── Settings/...
│   ├── DashboardTest.php
│   ├── RolePermissionTest.php
│   ├── SaleStoreTest.php
│   ├── StockLedgerTest.php
│   ├── SupplierTest.php
│   └── PurchaseTest.php
└── Unit/
    └── ProductLowStockTest.php
```

---

## Decisiones técnicas

### Backend

- **Transacciones con `lockForUpdate`** en `SaleController@store` y `PurchaseService`: garantiza que dos operaciones simultáneas sobre el mismo producto no produzcan over-selling. El `SELECT ... FOR UPDATE` serializa el acceso a la fila durante la transacción.
- **Validación de stock en el servicio, no en FormRequest**: la regla "no vender más del stock disponible" es estado mutable. Vive dentro de la transacción para que sea atómica con el descuento.
- **`FormRequest` por separado para `Store` y `Update`**: mensajes en español, `unique` con `Rule::ignore($id)`, validaciones específicas de cada contexto.
- **Polimorfismo para tareas/actividades**: un solo modelo `Task` y `Activity` se asocia a Customer, Lead, Opportunity o Sale. Evita duplicar tablas y centraliza el timeline.
- **`PostingService`** para partidas dobles: cada operación de venta/compra/pago/devolución genera automáticamente su asiento contable. El sistema valida Debe = Haber en cada `JournalEntry` y mantiene un plan contable jerárquico (padre/hijo).
- **Impuestos por línea (`sale_items.tax_id`)**: cada item puede tener su propio impuesto, snapshot del rate al momento de la venta, y cuenta contable asociada al impuesto.
- **Casts a `decimal:2`**: monedas siempre con 2 decimales consistentes.
- **FolioGenerator con `lockForUpdate`**: secuencia de folios thread-safe para `C-`, `D-`, `NC-`, `P-`, `COT-`, `POL-`.

### Frontend

- **`Inertia::render` con props tipadas**: cada página declara sus interfaces `defineProps<{}>` con la forma exacta que recibe del backend.
- **`useForm` de Inertia**: para todos los formularios (POST/PUT). Maneja automáticamente `processing`, `errors`, `reset` y los redirects.
- **Filtros en URL** con `router.get(url, params, { preserveScroll, preserveState })`: el back/forward del navegador funciona, los enlaces se pueden compartir.
- **`DateRangePicker` reutilizable** para todos los reportes contables: cambia la URL y refetch automático.
- **shadcn-vue local**: los componentes viven en el repo, se pueden editar, no hay dependencia externa en runtime.
- **Command palette** (`Cmd+K`) con búsqueda fuzzy de clientes, productos, leads, oportunidades, ventas, etc.

### Decisiones de producto

- **No hay edit/destroy de ventas**: integridad histórica. Las devoluciones se manejan con un módulo dedicado.
- **Conversión de cotización a venta**: pasa por el flujo normal, descontando stock atómicamente.
- **CRM-light sin email integration**: las actividades se registran manualmente (preparado para integración futura).
- **Permisos por rol preconfigurados** vía `config/stockflow_permissions.php`: 4 roles listos (admin, gerente, vendedor, comprador, contador).

---

## Tests

```bash
php artisan test
```

Suite actual: **92 tests, 282 assertions**. Cubre:

- `tests/Feature/SaleStoreTest.php` — 4 tests: decrementa stock, stock insuficiente, validación, sin cliente
- `tests/Feature/PurchaseTest.php` — flujo de compra, recepción, cancelación
- `tests/Feature/StockLedgerTest.php` — entradas, salidas, ajustes
- `tests/Feature/SupplierTest.php` — CRUD proveedores
- `tests/Feature/RolePermissionTest.php` — permisos y roles
- `tests/Feature/DashboardTest.php` — métricas
- `tests/Unit/ProductLowStockTest.php` — helper de stock bajo
- Tests de auth y settings del starter kit (sin modificar)

---

## Mejoras futuras

### Funcionalidad
- [ ] Imágenes en productos con `spatie/laravel-medialibrary`
- [ ] Reportes exportables a PDF (DomPDF) y Excel (maatwebsite/excel)
- [ ] Códigos de barras y lector USB
- [ ] Búsqueda asíncrona de productos en formulario de venta
- [ ] Email integration (Mailgun/SES) para cotizaciones y recibos
- [ ] Notificaciones automáticas (stock bajo, cotizaciones vencidas)
- [ ] Importación masiva desde Excel

### Técnico
- [ ] Tests E2E con Playwright
- [ ] API REST con Sanctum para app móvil
- [ ] Búsqueda full-text con Laravel Scout
- [ ] Caché de queries del dashboard (Redis)
- [ ] CI/CD con GitHub Actions (Pint, tests, build)
- [ ] Docker con Laravel Sail

---

## Licencia

MIT — Libre para usar, modificar y distribuir.
