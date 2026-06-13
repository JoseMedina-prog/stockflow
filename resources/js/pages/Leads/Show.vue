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
import { ArrowLeft, Check, Mail, Pencil, Phone, Plus, UserCheck, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface LeadData {
    id: number;
    name: string;
    company: string | null;
    email: string | null;
    phone: string | null;
    source: string;
    source_label: string;
    stage: string;
    stage_label: string;
    stage_badge: string;
    estimated_value: number;
    score: number | null;
    weighted_value: number;
    notes: string | null;
    lost_reason: string | null;
    is_converted: boolean;
    is_final: boolean;
    can_edit: boolean;
    can_convert: boolean;
    converted_at: string | null;
    owner: { id: number; name: string } | null;
    customer: {
        id: number;
        name: string;
        email: string | null;
        phone: string | null;
    } | null;
    created_at: string;
    updated_at: string;
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
    lead: LeadData;
    timeline: TimelineItem[];
    activity_types: Array<{ value: string; label: string; badge: string; has_duration: boolean }>;
}>();

const { can } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Ventas', href: '/sales' },
    { title: 'Prospectos', href: '/leads' },
    { title: props.lead.name, href: `/leads/${props.lead.id}` },
];

const convertOpen = ref(false);
const lostOpen = ref(false);
const activityOpen = ref(false);
const processing = ref(false);

const convertForm = useForm({
    name: props.lead.name,
    email: props.lead.email ?? '',
    phone: props.lead.phone ?? '',
    address: props.lead.company ?? '',
});

const lostForm = useForm({
    lost_reason: '',
});

const handleConvert = () => {
    processing.value = true;
    convertForm.post(route('leads.convert', props.lead.id), {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            convertOpen.value = false;
        },
    });
};

const handleMarkLost = () => {
    processing.value = true;
    lostForm.post(route('leads.mark-lost', props.lead.id), {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            lostOpen.value = false;
        },
    });
};

const handleDeleteActivity = (id: number) => {
    if (!confirm('¿Eliminar esta actividad?')) return;
    router.delete(route('activities.destroy', id), { preserveScroll: true });
};
</script>

<template>
    <Head :title="`Prospecto · ${lead.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <PageHeader :title="lead.name" :description="`Prospecto creado el ${formatDateTime(lead.created_at)}.`">
                    <template #actions>
                        <Button variant="outline" as-child>
                            <Link :href="route('leads.index')">
                                <ArrowLeft class="mr-1" />
                                Volver
                            </Link>
                        </Button>
                        <Button v-if="can('activities.create')" variant="outline" @click="activityOpen = true">
                            <Plus class="mr-1" />
                            Actividad
                        </Button>
                        <Button v-if="lead.can_edit" variant="outline" as-child>
                            <Link :href="route('leads.edit', lead.id)">
                                <Pencil class="mr-1" />
                                Editar
                            </Link>
                        </Button>
                        <Button v-if="lead.can_convert" @click="convertOpen = true">
                            <UserCheck class="mr-1" />
                            Convertir a cliente
                        </Button>
                        <Button v-if="lead.can_convert" variant="outline" class="text-destructive" @click="lostOpen = true">
                            <X class="mr-1" />
                            Marcar perdido
                        </Button>
                    </template>
                </PageHeader>
            </div>

            <div
                v-if="lead.is_converted"
                class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-200"
            >
                <p class="flex items-center gap-2 font-medium">
                    <Check class="size-4" />
                    Prospecto convertido a cliente
                </p>
                <p class="mt-1 text-xs">
                    <Link :href="route('customers.index')" class="font-semibold hover:underline">
                        {{ lead.customer?.name }}
                    </Link>
                    · convertido el {{ formatDateTime(lead.converted_at ?? undefined) }}
                </p>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="text-base">Información</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs text-muted-foreground">Etapa</p>
                            <Badge :variant="lead.stage_badge as any" class="mt-1">{{ lead.stage_label }}</Badge>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Origen</p>
                            <p class="font-medium">{{ lead.source_label }}</p>
                        </div>
                        <Separator />
                        <div v-if="lead.email">
                            <p class="text-xs text-muted-foreground">Correo</p>
                            <p class="flex items-center gap-1.5 font-medium">
                                <Mail class="size-3.5 text-muted-foreground" />
                                <a :href="`mailto:${lead.email}`" class="hover:underline">{{ lead.email }}</a>
                            </p>
                        </div>
                        <div v-if="lead.phone">
                            <p class="text-xs text-muted-foreground">Teléfono</p>
                            <p class="flex items-center gap-1.5 font-medium">
                                <Phone class="size-3.5 text-muted-foreground" />
                                <a :href="`tel:${lead.phone}`" class="hover:underline">{{ lead.phone }}</a>
                            </p>
                        </div>
                        <div v-if="lead.company">
                            <p class="text-xs text-muted-foreground">Empresa</p>
                            <p class="font-medium">{{ lead.company }}</p>
                        </div>
                        <Separator />
                        <div>
                            <p class="text-xs text-muted-foreground">Responsable</p>
                            <p class="font-medium">{{ lead.owner?.name ?? 'Sin asignar' }}</p>
                        </div>
                        <div v-if="lead.lost_reason">
                            <Separator />
                            <p class="text-xs text-muted-foreground">Motivo de pérdida</p>
                            <p class="text-sm">{{ lead.lost_reason }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="text-base">Valor y notas</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4 text-sm">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <p class="text-xs text-muted-foreground">Valor estimado</p>
                                <p class="text-2xl font-semibold tabular-nums">{{ formatCurrency(lead.estimated_value) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Probabilidad</p>
                                <p class="text-2xl font-semibold tabular-nums">
                                    {{ lead.score !== null ? lead.score + '%' : '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Valor ponderado</p>
                                <p class="text-2xl font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">
                                    {{ formatCurrency(lead.weighted_value) }}
                                </p>
                            </div>
                        </div>
                        <Separator />
                        <div>
                            <p class="text-xs text-muted-foreground">Notas</p>
                            <p v-if="lead.notes" class="mt-1 whitespace-pre-line text-sm">{{ lead.notes }}</p>
                            <p v-else class="mt-1 text-sm text-muted-foreground">Sin notas.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Timeline :items="timeline" :can-delete="can('activities.update')" @delete-activity="handleDeleteActivity" />
        </div>

        <div v-if="convertOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="convertOpen = false">
            <div class="w-full max-w-md rounded-lg bg-card p-6 shadow-xl">
                <h2 class="text-lg font-semibold">Convertir a cliente</h2>
                <p class="mt-1 text-sm text-muted-foreground">Se creará un cliente con estos datos. El prospecto cambiará a etapa «Ganado».</p>
                <form @submit.prevent="handleConvert" class="mt-4 space-y-4">
                    <div class="space-y-2">
                        <Label for="convert_name">Nombre</Label>
                        <Input id="convert_name" v-model="convertForm.name" type="text" required />
                    </div>
                    <div class="space-y-2">
                        <Label for="convert_email">Correo</Label>
                        <Input id="convert_email" v-model="convertForm.email" type="email" />
                    </div>
                    <div class="space-y-2">
                        <Label for="convert_phone">Teléfono</Label>
                        <Input id="convert_phone" v-model="convertForm.phone" type="tel" />
                    </div>
                    <div class="space-y-2">
                        <Label for="convert_address">Dirección</Label>
                        <Input id="convert_address" v-model="convertForm.address" type="text" placeholder="La empresa se usará como dirección" />
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <Button type="button" variant="outline" @click="convertOpen = false">Cancelar</Button>
                        <Button type="submit" :disabled="processing">
                            <UserCheck class="mr-1" />
                            Convertir
                        </Button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="lostOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="lostOpen = false">
            <div class="w-full max-w-md rounded-lg bg-card p-6 shadow-xl">
                <h2 class="text-lg font-semibold">Marcar como perdido</h2>
                <p class="mt-1 text-sm text-muted-foreground">El prospecto cambiará a etapa «Perdido». Indica el motivo para futuras referencias.</p>
                <form @submit.prevent="handleMarkLost" class="mt-4 space-y-4">
                    <div class="space-y-2">
                        <Label for="rejection_reason">Motivo</Label>
                        <textarea
                            id="rejection_reason"
                            v-model="lostForm.lost_reason"
                            rows="3"
                            required
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                            placeholder="Ej: Eligió a la competencia por precio"
                        />
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <Button type="button" variant="outline" @click="lostOpen = false">Cancelar</Button>
                        <Button type="submit" variant="destructive" :disabled="processing">
                            <X class="mr-1" />
                            Marcar perdido
                        </Button>
                    </div>
                </form>
            </div>
        </div>

        <ActivityDialog
            v-model:open="activityOpen"
            :types="activity_types"
            :post-url="route('leads.activities.store', lead.id)"
            :description="`Actividad sobre el prospecto «${lead.name}».`"
        />
    </AppLayout>
</template>
