<?php

return [

    'resources' => [
        'dashboard' => 'Dashboard',
        'categories' => 'Categorías',
        'products' => 'Productos',
        'customers' => 'Clientes',
        'sales' => 'Ventas',
        'purchases' => 'Compras',
        'suppliers' => 'Proveedores',
        'returns' => 'Devoluciones',
        'payments' => 'Pagos',
        'stock_movements' => 'Movimientos de inventario',
        'leads' => 'Leads',
        'opportunities' => 'Oportunidades',
        'quotes' => 'Cotizaciones',
        'tasks' => 'Tareas',
        'activities' => 'Actividades',
        'reports' => 'Reportes',
        'accounting' => 'Contabilidad',
        'taxes' => 'Impuestos',
        'users' => 'Usuarios',
        'settings' => 'Configuración',
    ],

    'actions' => [
        'view_any',
        'view',
        'create',
        'update',
        'delete',
    ],

    'roles' => [

        'admin' => [
            'label' => 'Administrador',
            'description' => 'Acceso total al sistema, incluyendo configuración y gestión de usuarios.',
            'permissions' => 'all',
        ],

        'gerente' => [
            'label' => 'Gerente',
            'description' => 'Acceso a operaciones y reportes, sin gestión de usuarios ni configuración crítica.',
            'permissions' => [
                'dashboard.view_any',
                'categories.view_any',
                'products.view_any', 'products.view', 'products.create', 'products.update',
                'customers.view_any', 'customers.view', 'customers.create', 'customers.update',
                'sales.view_any', 'sales.view', 'sales.create',
                'purchases.view_any', 'purchases.view', 'purchases.create', 'purchases.update',
                'suppliers.view_any', 'suppliers.view', 'suppliers.create', 'suppliers.update',
                'returns.view_any', 'returns.view', 'returns.create', 'returns.update',
                'payments.view_any', 'payments.view', 'payments.create',
                'stock_movements.view_any', 'stock_movements.view',
                'leads.view_any', 'leads.view', 'leads.create', 'leads.update',
                'opportunities.view_any', 'opportunities.view', 'opportunities.create', 'opportunities.update',
                'quotes.view_any', 'quotes.view', 'quotes.create', 'quotes.update',
                'tasks.view_any', 'tasks.view', 'tasks.create', 'tasks.update',
                'activities.view_any', 'activities.view', 'activities.create',
                'reports.view_any',
                'accounting.view_any', 'accounting.view',
                'taxes.view_any',
            ],
        ],

        'vendedor' => [
            'label' => 'Vendedor',
            'description' => 'Punto de venta, gestión de clientes y seguimiento comercial.',
            'permissions' => [
                'dashboard.view_any',
                'products.view_any', 'products.view',
                'customers.view_any', 'customers.view', 'customers.create', 'customers.update',
                'sales.view_any', 'sales.view', 'sales.create',
                'leads.view_any', 'leads.view', 'leads.create', 'leads.update',
                'opportunities.view_any', 'opportunities.view', 'opportunities.create', 'opportunities.update',
                'quotes.view_any', 'quotes.view', 'quotes.create', 'quotes.update',
                'tasks.view_any', 'tasks.view', 'tasks.create', 'tasks.update',
                'activities.view_any', 'activities.view', 'activities.create',
            ],
        ],

        'comprador' => [
            'label' => 'Comprador',
            'description' => 'Gestión de proveedores, compras y entradas de inventario.',
            'permissions' => [
                'dashboard.view_any',
                'categories.view_any',
                'products.view_any', 'products.view', 'products.update',
                'suppliers.view_any', 'suppliers.view', 'suppliers.create', 'suppliers.update',
                'purchases.view_any', 'purchases.view', 'purchases.create', 'purchases.update',
                'payments.view_any', 'payments.view', 'payments.create',
                'stock_movements.view_any', 'stock_movements.view',
                'tasks.view_any', 'tasks.view', 'tasks.create', 'tasks.update',
            ],
        ],

        'contador' => [
            'label' => 'Contador',
            'description' => 'Acceso de lectura a operaciones y gestión contable.',
            'permissions' => [
                'dashboard.view_any',
                'products.view_any', 'products.view',
                'customers.view_any', 'customers.view',
                'suppliers.view_any', 'suppliers.view',
                'sales.view_any', 'sales.view',
                'purchases.view_any', 'purchases.view',
                'returns.view_any', 'returns.view',
                'payments.view_any', 'payments.view', 'payments.create', 'payments.update',
                'stock_movements.view_any', 'stock_movements.view',
                'reports.view_any',
                'accounting.view_any', 'accounting.view', 'accounting.create', 'accounting.update',
                'taxes.view_any', 'taxes.create', 'taxes.update',
            ],
        ],
    ],
];
