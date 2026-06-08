<?php

namespace App\Services;

use App\Enums\LeadStage;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LeadConversionService
{
    /**
     * Convert a lead into a customer.
     * Creates a new Customer with the lead's contact info, marks the lead as won,
     * and links the two. Optionally override name/phone/email/address.
     *
     * @param  array{name?: string, email?: string, phone?: string, address?: string}  $overrides
     */
    public function convert(Lead $lead, User $user, array $overrides = []): Customer
    {
        return DB::transaction(function () use ($lead, $user, $overrides) {
            $locked = Lead::query()->lockForUpdate()->findOrFail($lead->id);

            if ($locked->isConverted()) {
                throw new \DomainException("El lead «{$locked->name}» ya fue convertido.");
            }

            $customer = Customer::create([
                'name' => $overrides['name'] ?? $locked->name,
                'email' => $overrides['email'] ?? $locked->email,
                'phone' => $overrides['phone'] ?? $locked->phone,
                'address' => $overrides['address'] ?? $locked->company,
            ]);

            $locked->update([
                'stage' => LeadStage::Won,
                'converted_at' => now(),
                'converted_to_customer_id' => $customer->id,
            ]);

            return $customer;
        });
    }
}
