<?php

namespace App\Services;

use App\Enums\OpportunityStage;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PipelineAdvanceService
{
    /**
     * Advance an opportunity to a new stage. Validates that the transition
     * is allowed by OpportunityStage::isValidTransition().
     */
    public function advance(
        Opportunity $opportunity,
        OpportunityStage $targetStage,
        User $user,
        ?string $lostReason = null,
        ?int $probability = null,
    ): Opportunity {
        return DB::transaction(function () use ($opportunity, $targetStage, $user, $lostReason, $probability) {
            $locked = Opportunity::query()->lockForUpdate()->findOrFail($opportunity->id);

            if ($locked->stage->isFinal()) {
                throw new \DomainException("La oportunidad «{$locked->name}» ya está cerrada.");
            }

            if (! $locked->stage->isValidTransition($targetStage)) {
                throw new \DomainException("No se puede pasar de «{$locked->stage->label()}» a «{$targetStage->label()}».");
            }

            $update = ['stage' => $targetStage];

            if ($targetStage->isFinal()) {
                $update['closed_at'] = now();
                if ($targetStage->isWon()) {
                    $update['probability'] = 100;
                }
                if ($targetStage->isLost() && $lostReason) {
                    $update['lost_reason'] = $lostReason;
                }
            }

            if ($probability !== null && $targetStage->isOpen()) {
                $update['probability'] = max(0, min(100, $probability));
            }

            $locked->update($update);

            return $locked->fresh();
        });
    }
}
