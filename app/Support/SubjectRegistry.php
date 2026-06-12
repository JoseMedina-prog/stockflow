<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleReturn;

class SubjectRegistry
{
    public const TYPE_CUSTOMER = 'customer';

    public const TYPE_LEAD = 'lead';

    public const TYPE_OPPORTUNITY = 'opportunity';

    public const TYPE_SALE = 'sale';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_RETURN = 'return';

    public const TYPE_PAYMENT = 'payment';

    /**
     * @var array<class-string, array{type: string, label: string, route: string, name_attr: string, short_label: string}>
     */
    private const MAP = [
        Customer::class => [
            'type' => self::TYPE_CUSTOMER,
            'label' => 'Cliente',
            'short_label' => 'C',
            'route' => 'customers.index',
            'name_attr' => 'name',
        ],
        Lead::class => [
            'type' => self::TYPE_LEAD,
            'label' => 'Lead',
            'short_label' => 'L',
            'route' => 'leads.show',
            'name_attr' => 'name',
        ],
        Opportunity::class => [
            'type' => self::TYPE_OPPORTUNITY,
            'label' => 'Oportunidad',
            'short_label' => 'O',
            'route' => 'opportunities.show',
            'name_attr' => 'name',
        ],
        Sale::class => [
            'type' => self::TYPE_SALE,
            'label' => 'Venta',
            'short_label' => 'V',
            'route' => 'sales.show',
            'name_attr' => 'id',
        ],
        Purchase::class => [
            'type' => self::TYPE_PURCHASE,
            'label' => 'Compra',
            'short_label' => 'P',
            'route' => 'purchases.index',
            'name_attr' => 'folio',
        ],
        SaleReturn::class => [
            'type' => self::TYPE_RETURN,
            'label' => 'Devolución',
            'short_label' => 'D',
            'route' => 'returns.show',
            'name_attr' => 'folio',
        ],
        Payment::class => [
            'type' => self::TYPE_PAYMENT,
            'label' => 'Pago',
            'short_label' => 'P',
            'route' => 'payments.index',
            'name_attr' => 'folio',
        ],
    ];

    public static function label(string $morphClass): ?string
    {
        return self::resolve($morphClass)['label'] ?? null;
    }

    public static function type(string $morphClass): ?string
    {
        return self::resolve($morphClass)['type'] ?? null;
    }

    public static function href(string $morphClass, int|string|null $id): ?string
    {
        $info = self::resolve($morphClass);

        if ($info === null || empty($id)) {
            return null;
        }

        return route($info['route'], $id);
    }

    public static function labelForInstance(mixed $model): ?string
    {
        if (! is_object($model)) {
            return null;
        }

        $info = self::resolve($model::class);
        if ($info === null) {
            return null;
        }

        if ($model instanceof Sale) {
            return 'V-'.str_pad((string) $model->id, 6, '0', STR_PAD_LEFT);
        }

        return $model->{$info['name_attr']} ?? '#'.$model->id;
    }

    /**
     * @return array<int, array{value: string, label: string, route: string, class: class-string}>
     */
    public static function optionsFor(string|array $only = []): array
    {
        $entries = $only === [] ? self::MAP : array_intersect_key(self::MAP, array_flip((array) $only));

        return array_map(
            fn (string $class, array $info) => [
                'value' => $info['type'],
                'label' => $info['label'],
                'route' => $info['route'],
                'class' => $class,
            ],
            array_keys($entries),
            $entries,
        );
    }

    /**
     * @return array{type: string, label: string, short_label: string, route: string, name_attr: string}|null
     */
    private static function resolve(string $morphClass): ?array
    {
        if (isset(self::MAP[$morphClass])) {
            return self::MAP[$morphClass];
        }

        foreach (self::MAP as $class => $info) {
            if (is_a($morphClass, $class, true)) {
                return $info;
            }
        }

        return null;
    }
}
