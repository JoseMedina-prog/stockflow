<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatCurrency } from '@/composables/useFormat';
import { useForm } from '@inertiajs/vue3';
import { CreditCard, Loader2, X } from 'lucide-vue-next';
import { computed } from 'vue';

interface MethodOption {
    value: string;
    label: string;
}

const props = defineProps<{
    open: boolean;
    balance: number;
    methods: MethodOption[];
    postUrl: string;
    description?: string;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const today = new Date().toISOString().slice(0, 16);

const form = useForm({
    method: 'cash',
    amount: 0,
    paid_at: today,
    reference: '',
    notes: '',
});

const close = () => {
    emit('update:open', false);
    form.reset();
    form.clearErrors();
};

const submit = () => {
    form.post(props.postUrl, {
        preserveScroll: true,
        onSuccess: () => close(),
    });
};

const formattedBalance = computed(() => formatCurrency(props.balance));
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="close">
        <div class="w-full max-w-md rounded-lg bg-card p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="flex items-center gap-2 text-lg font-semibold">
                    <CreditCard class="size-5" />
                    Registrar pago
                </h2>
                <button type="button" class="rounded-md p-1 text-muted-foreground hover:bg-accent" @click="close">
                    <X class="size-4" />
                </button>
            </div>

            <div v-if="description" class="mb-4 rounded-md border border-border/60 bg-muted/40 px-3 py-2 text-sm">
                {{ description }}
            </div>

            <div class="mb-4 flex items-center justify-between rounded-md border border-border/60 bg-muted/40 px-3 py-2">
                <span class="text-sm text-muted-foreground">Saldo pendiente</span>
                <span class="font-semibold tabular-nums">{{ formattedBalance }}</span>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="amount">Monto</Label>
                    <Input id="amount" v-model="form.amount" type="number" step="0.01" min="0.01" :max="balance" required />
                    <p v-if="form.errors.amount" class="text-sm text-destructive">{{ form.errors.amount }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="method">Método de pago</Label>
                    <select id="method" v-model="form.method" required class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm">
                        <option v-for="m in methods" :key="m.value" :value="m.value">{{ m.label }}</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <Label for="paid_at">Fecha y hora</Label>
                    <Input id="paid_at" v-model="form.paid_at" type="datetime-local" required />
                </div>

                <div class="space-y-2">
                    <Label for="reference">Referencia</Label>
                    <Input
                        id="reference"
                        v-model="form.reference"
                        type="text"
                        maxlength="100"
                        placeholder="Ej: últimos 4 dígitos, num. transferencia"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="notes">Notas</Label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="2"
                        class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                    />
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <Button type="button" variant="outline" @click="close">Cancelar</Button>
                    <Button type="submit" :disabled="form.processing || form.amount <= 0">
                        <Loader2 v-if="form.processing" class="mr-1 animate-spin" />
                        Registrar pago
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
