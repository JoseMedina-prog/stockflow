<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { Loader2, X } from 'lucide-vue-next';
import { computed } from 'vue';

interface TypeOption {
    value: string;
    label: string;
    badge: string;
    has_duration: boolean;
}

const props = defineProps<{
    open: boolean;
    types: TypeOption[];
    postUrl: string;
    description?: string;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const form = useForm({
    type: 'note',
    description: '',
    occurred_at: new Date().toISOString().slice(0, 16),
    duration_minutes: '' as string | number,
    outcome: '',
});

const close = () => {
    emit('update:open', false);
    form.reset();
    form.clearErrors();
};

const selectedType = computed(() => props.types.find((t) => t.value === form.type));

const showDuration = computed(() => selectedType.value?.has_duration ?? false);

const submit = () => {
    if (!showDuration.value) {
        form.duration_minutes = '';
    }
    form.post(props.postUrl, {
        preserveScroll: true,
        onSuccess: () => close(),
    });
};
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="close"
    >
        <div class="w-full max-w-md rounded-lg bg-card p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Registrar actividad</h2>
                <button
                    type="button"
                    class="rounded-md p-1 text-muted-foreground hover:bg-accent"
                    @click="close"
                >
                    <X class="size-4" />
                </button>
            </div>

            <div v-if="description" class="mb-4 rounded-md border border-border/60 bg-muted/40 px-3 py-2 text-sm">
                {{ description }}
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="activity_type">Tipo</Label>
                    <select
                        id="activity_type"
                        v-model="form.type"
                        required
                        class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm"
                    >
                        <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <Label for="activity_description">Descripción</Label>
                    <textarea
                        id="activity_description"
                        v-model="form.description"
                        rows="2"
                        class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                        placeholder="Resumen de la actividad..."
                    />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="activity_occurred_at">Cuándo</Label>
                        <Input
                            id="activity_occurred_at"
                            v-model="form.occurred_at"
                            type="datetime-local"
                            required
                        />
                    </div>
                    <div v-if="showDuration" class="space-y-2">
                        <Label for="activity_duration">Duración (min)</Label>
                        <Input
                            id="activity_duration"
                            v-model="form.duration_minutes"
                            type="number"
                            min="1"
                            max="1440"
                        />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="activity_outcome">Resultado (opcional)</Label>
                    <textarea
                        id="activity_outcome"
                        v-model="form.outcome"
                        rows="2"
                        class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                        placeholder="Conclusión, próximo paso, acuerdo..."
                    />
                </div>

                <div class="flex items-center justify-end gap-2">
                    <Button type="button" variant="outline" @click="close">Cancelar</Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-1 animate-spin" />
                        Registrar
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
