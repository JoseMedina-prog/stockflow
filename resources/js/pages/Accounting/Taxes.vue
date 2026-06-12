<script setup lang="ts">
import ConfirmDialog from '@/components/stockflow/ConfirmDialog.vue';
import EmptyState from '@/components/stockflow/EmptyState.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ClipboardList, Loader2, Pencil, Plus, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface TaxData {
    id: number;
    code: string;
    name: string;
    type: string;
    type_label: string;
    rate: number;
    percent: number;
    is_active: boolean;
    is_inclusive: boolean;
    account: { code: string; name: string } | null;
    description: string | null;
}

interface TypeOption {
    value: string;
    label: string;
}

defineProps<{
    taxes: TaxData[];
    types: TypeOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Impuestos', href: '/taxes' },
];

const editing = ref<TaxData | null>(null);
const creating = ref(false);
const confirmDeleteOpen = ref(false);
const target = ref<TaxData | null>(null);
const processing = ref(false);

const form = useForm({
    code: '',
    name: '',
    type: 'iva_trasladado',
    rate: 0.16,
    account_id: null as number | null,
    is_active: true,
    is_inclusive: false,
    description: '',
});

const startCreate = () => {
    form.reset();
    form.code = '';
    form.name = '';
    form.type = 'iva_trasladado';
    form.rate = 0.16;
    form.is_active = true;
    form.is_inclusive = false;
    form.description = '';
    form.account_id = null;
    creating.value = true;
    editing.value = null;
};

const startEdit = (t: TaxData) => {
    form.code = t.code;
    form.name = t.name;
    form.type = t.type;
    form.rate = t.rate;
    form.is_active = t.is_active;
    form.is_inclusive = t.is_inclusive;
    form.description = t.description ?? '';
    form.account_id = null;
    editing.value = t;
    creating.value = false;
};

const closeForm = () => {
    creating.value = false;
    editing.value = null;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (editing.value) {
        form.put(route('taxes.update', editing.value.id), { preserveScroll: true, onSuccess: closeForm });
    } else {
        form.post(route('taxes.store'), { preserveScroll: true, onSuccess: closeForm });
    }
};

const askDelete = (t: TaxData) => {
    target.value = t;
    confirmDeleteOpen.value = true;
};

const handleDelete = () => {
    if (!target.value) return;
    processing.value = true;
    router.delete(route('taxes.destroy', target.value.id), {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            confirmDeleteOpen.value = false;
            target.value = null;
        },
    });
};
</script>

<template>
    <Head title="Impuestos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader title="Impuestos" description="Configura los impuestos que se aplican a tus ventas y compras.">
                <template #actions>
                    <Button @click="startCreate">
                        <Plus class="mr-1" />
                        Nuevo impuesto
                    </Button>
                </template>
            </PageHeader>

            <Card>
                <CardContent class="p-0">
                    <Table v-if="taxes.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Código</TableHead>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead class="text-right">Tasa</TableHead>
                                <TableHead>Cuenta contable</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead class="w-24 text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="t in taxes" :key="t.id">
                                <TableCell class="font-mono text-xs">{{ t.code }}</TableCell>
                                <TableCell>
                                    <div class="font-medium">{{ t.name }}</div>
                                    <p v-if="t.description" class="text-xs text-muted-foreground">{{ t.description }}</p>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary">{{ t.type_label }}</Badge>
                                </TableCell>
                                <TableCell class="text-right tabular-nums">{{ t.percent }}%</TableCell>
                                <TableCell class="text-xs">
                                    <span v-if="t.account">{{ t.account.code }} — {{ t.account.name }}</span>
                                    <span v-else class="text-muted-foreground">Sin asignar</span>
                                </TableCell>
                                <TableCell>
                                    <Badge v-if="t.is_active" variant="success">Activo</Badge>
                                    <Badge v-else variant="secondary">Inactivo</Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="ghost" size="icon" @click="startEdit(t)">
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive" @click="askDelete(t)">
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <EmptyState
                        v-else
                        :icon="ClipboardList"
                        title="Sin impuestos"
                        description="Crea tu primer impuesto para empezar a registrar operaciones gravadas."
                        :action="{ label: 'Nuevo impuesto', href: '#' }"
                    />
                </CardContent>
            </Card>
        </div>

        <div v-if="creating || editing" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closeForm">
            <div class="w-full max-w-lg rounded-lg bg-card p-6 shadow-xl">
                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">
                            {{ editing ? `Editar ${editing.code}` : 'Nuevo impuesto' }}
                        </h2>
                        <p class="mt-0.5 text-sm text-muted-foreground">Configura el código, tipo y tasa.</p>
                    </div>
                    <Button variant="ghost" size="icon" @click="closeForm">
                        <X />
                    </Button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="code">Código</Label>
                            <Input id="code" v-model="form.code" placeholder="IVAT-16" required />
                            <p v-if="form.errors.code" class="text-sm text-destructive">{{ form.errors.code }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="type">Tipo</Label>
                            <select
                                id="type"
                                v-model="form.type"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-2 text-sm"
                                required
                            >
                                <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                            <p v-if="form.errors.type" class="text-sm text-destructive">{{ form.errors.type }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="name">Nombre</Label>
                        <Input id="name" v-model="form.name" placeholder="IVA 16% (Trasladado)" required />
                        <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="rate">Tasa (decimal)</Label>
                            <Input id="rate" v-model.number="form.rate" type="number" min="0" max="1" step="0.0001" required />
                            <p class="text-xs text-muted-foreground">0.16 = 16%, 0.08 = 8%, 0 = exento.</p>
                            <p v-if="form.errors.rate" class="text-sm text-destructive">{{ form.errors.rate }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label>Estado</Label>
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="size-4 rounded border-input text-primary focus:ring-1 focus:ring-ring"
                                />
                                Impuesto activo
                            </label>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="description">Descripción</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="2"
                            class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <Button type="button" variant="outline" @click="closeForm">Cancelar</Button>
                        <Button type="submit" :disabled="form.processing">
                            <Loader2 v-if="form.processing" class="mr-1 animate-spin" />
                            {{ editing ? 'Guardar cambios' : 'Crear impuesto' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>

        <ConfirmDialog
            v-model:open="confirmDeleteOpen"
            title="Eliminar impuesto"
            :description="`¿Estás seguro de eliminar el impuesto «${target?.code} — ${target?.name}»?`"
            :processing="processing"
            @confirm="handleDelete"
        />
    </AppLayout>
</template>
