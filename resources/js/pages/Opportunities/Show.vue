<script setup lang="ts">
import ActivityDialog from '@/components/stockflow/ActivityDialog.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import Timeline from '@/components/stockflow/Timeline.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatCurrency, formatDateTime } from '@/composables/useFormat';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Check, FileText, Pencil, Plus, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface NextStage {
    value: string;
    label: string;
    badge: string;
}

interface OppData {
    id: number;
    name: string;
    stage: string;
    stage_label: string;
    stage_badge: string;
    amount: number;
    weighted_amount: number;
    probability: number;
    expected_close_date: string | null;
    is_overdue: boolean;
    is_final: boolean;
    is_won: boolean;
    is_lost: boolean;
    can_edit: boolean;
    can_advance: boolean;
    closed_at: string | null;
    lost_reason: string | null;
    notes: string | null;
    next_stages: NextStage[];
    customer: { id: number; name: string; email: string | null; phone: string | null } | null;
    lead: { id: number; name: string } | null;
    owner: { id: number; name: string } | null;
    created_at: string;
}

type TimelineItem = {
    id: number;
    kind: 'activity' | 'task';
    type: string;
    type_label: string;
    type_badge: string;
    description: string | null;
    outcome?: string | null;
    occurred_at?: string;
    duration_minutes?: number | null;
    user?: { id: number; name: string } | null;
    title?: string;
    priority?: string;
    priority_badge?: string;
    status?: string;
    status_badge?: string;
    due_date?: string | null;
    is_overdue?: boolean;
    is_due_today?: boolean;
    is_open?: boolean;
    assignee?: { id: number; name: string } | null;
    href?: string;
    subject_type?: string;
    subject_href?: string | null;
    subject_label?: string;
};

const props = defineProps<{
    opportunity: OppData;
    timeline: TimelineItem[];
    activity_types: Array<{ value: string; label: string; badge: string; has_duration: boolean }>;
}>();

const { can } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Ventas', href: '/sales' },
    { title: 'Oportunidades', href: '/opportunities' },
    { title: props.opportunity.name, href: `/opportunities/${props.opportunity.id}` },
];

const advanceOpen = ref<null | string>(null);
const activityOpen = ref(false);
const processing = ref(false);

const advanceForm = useForm({
    target_stage: '',
    lost_reason: '',
    probability: props.opportunity.probability,
});

const handleAdvance = () => {
    if (!advanceOpen.value) return;
    processing.value = true;
    advanceForm.target_stage = advanceOpen.value;
    advanceForm.post(route('opportunities.advance', props.opportunity.id), {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            advanceOpen.value = null;
        },
    });
};

const handleDeleteActivity = (id: number) => {
    if (!confirm('¿Eliminar esta actividad?')) return;
    router.delete(route('activities.destroy', id), { preserveScroll: true });
};
</script>

<template>
    <Head :title="`Oportunidad · ${opportunity.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <PageHeader :title="opportunity.name" :description="`Creada el ${formatDateTime(opportunity.created_at)}.`">
                    <template #actions>
                        <Button variant="outline" as-child>
                            <Link :href="route('opportunities.index')">
                                <ArrowLeft class="mr-1" />
                                Volver
                            </Link>
                        </Button>
                        <Button v-if="can('activities.create')" variant="outline" @click="activityOpen = true">
                            <Plus class="mr-1" />
                            Actividad
                        </Button>
                        <Button v-if="opportunity.can_edit" variant="outline" as-child>
                            <Link :href="route('opportunities.edit', opportunity.id)">
                                <Pencil class="mr-1" />
                                Editar
                            </Link>
                        </Button>
                        <Button v-if="can('quotes.create') && opportunity.customer" variant="outline" as-child>
                            <Link :href="route('quotes.create', { opportunity_id: opportunity.id, customer_id: opportunity.customer.id })">
                                <FileText class="mr-1" />
                                Crear cotización
                            </Link>
                        </Button>
                    </template>
                </PageHeader>
            </div>

            <div
                v-if="opportunity.is_won"
                class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-200"
            >
                <p class="flex items-center gap-2 font-medium"><Check class="size-4" /> Oportunidad ganada</p>
                <p class="mt-1 text-xs">Cerrada el {{ formatDateTime(opportunity.closed_at ?? undefined) }}.</p>
            </div>
            <div
                v-else-if="opportunity.is_lost"
                class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-200"
            >
                <p class="flex items-center gap-2 font-medium"><X class="size-4" /> Oportunidad perdida</p>
                <p v-if="opportunity.lost_reason" class="mt-1 text-xs">Motivo: {{ opportunity.lost_reason }}</p>
            </div>

            <div v-if="opportunity.can_advance" class="rounded-lg border border-border/60 bg-card p-4">
                <p class="mb-3 text-sm font-medium">Avanzar etapa</p>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="ns in opportunity.next_stages"
                        :key="ns.value"
                        size="sm"
                        :variant="ns.value === 'closed_won' ? 'default' : ns.value === 'closed_lost' ? 'outline' : 'secondary'"
                        :class="ns.value === 'closed_lost' ? 'text-destructive' : ''"
                        @click="advanceOpen = ns.value"
                    >
                        <template v-if="ns.value === 'closed_won'"><Check class="mr-1" />Marcar ganada</template>
                        <template v-else-if="ns.value === 'closed_lost'"><X class="mr-1" />Marcar perdida</template>
                        <template v-else>
                            Pasar a {{ ns.label }}
                            <ArrowRight class="ml-1" />
                        </template>
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="text-base">Información</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs text-muted-foreground">Etapa</p>
                            <Badge :variant="opportunity.stage_badge as any" class="mt-1">{{ opportunity.stage_label }}</Badge>
                        </div>
                        <Separator />
                        <div v-if="opportunity.customer">
                            <p class="text-xs text-muted-foreground">Cliente</p>
                            <Link :href="route('customers.index')" class="font-medium hover:underline">
                                {{ opportunity.customer.name }}
                            </Link>
                            <p v-if="opportunity.customer.email" class="text-xs text-muted-foreground">{{ opportunity.customer.email }}</p>
                            <p v-if="opportunity.customer.phone" class="text-xs text-muted-foreground">{{ opportunity.customer.phone }}</p>
                        </div>
                        <div v-if="opportunity.lead">
                            <Separator />
                            <p class="text-xs text-muted-foreground">Prospecto de origen</p>
                            <Link :href="route('leads.show', opportunity.lead.id)" class="font-medium hover:underline">
                                {{ opportunity.lead.name }}
                            </Link>
                        </div>
                        <Separator />
                        <div>
                            <p class="text-xs text-muted-foreground">Responsable</p>
                            <p class="font-medium">{{ opportunity.owner?.name ?? 'Sin asignar' }}</p>
                        </div>
                        <div v-if="opportunity.expected_close_date">
                            <p class="text-xs text-muted-foreground">Cierre estimado</p>
                            <p class="font-medium" :class="opportunity.is_overdue ? 'text-destructive' : ''">
                                {{ opportunity.expected_close_date }}
                                <span v-if="opportunity.is_overdue" class="text-xs">(vencida)</span>
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="text-base">Detalles financieros</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4 text-sm">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <p class="text-xs text-muted-foreground">Monto</p>
                                <p class="text-2xl font-semibold tabular-nums">{{ formatCurrency(opportunity.amount) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Probabilidad</p>
                                <p class="text-2xl font-semibold tabular-nums">{{ opportunity.probability }}%</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Valor ponderado</p>
                                <p class="text-2xl font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">
                                    {{ formatCurrency(opportunity.weighted_amount) }}
                                </p>
                            </div>
                        </div>
                        <Separator />
                        <div>
                            <p class="text-xs text-muted-foreground">Notas</p>
                            <p v-if="opportunity.notes" class="mt-1 whitespace-pre-line text-sm">{{ opportunity.notes }}</p>
                            <p v-else class="mt-1 text-sm text-muted-foreground">Sin notas.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Timeline :items="timeline" :can-delete="can('activities.update')" @delete-activity="handleDeleteActivity" />
        </div>

        <div
            v-if="advanceOpen === 'closed_lost'"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="advanceOpen = null"
        >
            <div class="w-full max-w-md rounded-lg bg-card p-6 shadow-xl">
                <h2 class="text-lg font-semibold">Marcar oportunidad como perdida</h2>
                <p class="mt-1 text-sm text-muted-foreground">Indica el motivo para futuras referencias.</p>
                <form @submit.prevent="handleAdvance" class="mt-4 space-y-4">
                    <div class="space-y-2">
                        <Label for="lost_reason">Motivo de pérdida</Label>
                        <textarea
                            id="lost_reason"
                            v-model="advanceForm.lost_reason"
                            rows="3"
                            required
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                            placeholder="Ej: Eligió a la competencia por precio"
                        />
                        <p v-if="advanceForm.errors.lost_reason" class="text-sm text-destructive">{{ advanceForm.errors.lost_reason }}</p>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <Button type="button" variant="outline" @click="advanceOpen = null">Cancelar</Button>
                        <Button type="submit" variant="destructive" :disabled="processing"> <X class="mr-1" /> Marcar perdida </Button>
                    </div>
                </form>
            </div>
        </div>

        <div v-else-if="advanceOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="advanceOpen = null">
            <div class="w-full max-w-md rounded-lg bg-card p-6 shadow-xl">
                <h2 class="text-lg font-semibold">Confirmar avance de etapa</h2>
                <p class="mt-1 text-sm text-muted-foreground">La oportunidad pasará a la etapa seleccionada.</p>
                <form @submit.prevent="handleAdvance" class="mt-4 space-y-4">
                    <div class="space-y-2">
                        <Label>Nueva etapa</Label>
                        <div class="rounded-md border border-border/60 bg-muted/40 px-3 py-2 text-sm font-medium">
                            {{ opportunity.next_stages.find((s) => s.value === advanceOpen)?.label }}
                        </div>
                    </div>
                    <div v-if="advanceOpen !== 'closed_won' && advanceOpen !== 'closed_lost'" class="space-y-2">
                        <Label for="probability">Probabilidad %</Label>
                        <Input id="probability" v-model.number="advanceForm.probability" type="number" min="0" max="100" />
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <Button type="button" variant="outline" @click="advanceOpen = null">Cancelar</Button>
                        <Button type="submit" :disabled="processing"> <ArrowRight class="mr-1" /> Avanzar </Button>
                    </div>
                </form>
            </div>
        </div>

        <ActivityDialog
            v-model:open="activityOpen"
            :types="activity_types"
            :post-url="route('opportunities.activities.store', opportunity.id)"
            :description="`Actividad sobre la oportunidad «${opportunity.name}».`"
        />
    </AppLayout>
</template>
