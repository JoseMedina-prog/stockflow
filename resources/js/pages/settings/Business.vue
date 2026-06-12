<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';

interface FieldConfig {
    type: string;
    group: string;
    label: string;
    description?: string;
}

interface BusinessForm {
    name: string;
    legal_name: string;
    tax_id: string;
    email: string;
    phone: string;
    address: string;
    website: string;
    logo_path: string;
    currency: string;
    timezone: string;
    invoice_prefix: string;
    return_policy_days: number;
    notes: string;
}

const props = defineProps<{
    business: BusinessForm;
    fields: Record<keyof BusinessForm, FieldConfig>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Empresa', href: '/settings/business' }];

const form = useForm<BusinessForm>({ ...props.business });

const submit = () => {
    form.patch(route('settings.business.update'), { preserveScroll: true });
};

const groups = ['business', 'invoicing', 'policies'] as const;
const groupLabels: Record<(typeof groups)[number], { title: string; description: string }> = {
    business: { title: 'Datos fiscales y contacto', description: 'Información que aparece en facturas y documentos.' },
    invoicing: { title: 'Facturación', description: 'Prefijos, moneda y notas predeterminadas.' },
    policies: { title: 'Políticas', description: 'Reglas que aplican al sistema.' },
};
</script>

<template>
    <Head title="Datos de la empresa" />

    <SettingsLayout>
        <Head title="Datos de la empresa" />

        <div class="space-y-6">
            <Heading title="Datos de la empresa" description="Información fiscal y de contacto que aparece en facturas, reportes y documentos." />

            <form @submit.prevent="submit" class="space-y-6">
                <Card v-for="group in groups" :key="group">
                    <CardHeader>
                        <CardTitle class="text-base">{{ groupLabels[group].title }}</CardTitle>
                        <CardDescription>{{ groupLabels[group].description }}</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-5 sm:grid-cols-2">
                        <template
                            v-for="(config, field) in Object.fromEntries(Object.entries(fields).filter(([, c]) => c.group === group))"
                            :key="field"
                        >
                            <div
                                class="space-y-2"
                                :class="{
                                    'sm:col-span-2': field === 'address' || field === 'notes',
                                }"
                            >
                                <Label :for="String(field)">
                                    {{ config.label }}
                                </Label>
                                <Input
                                    v-if="field !== 'address' && field !== 'notes' && field !== 'return_policy_days'"
                                    :id="String(field)"
                                    v-model="form[field as keyof BusinessForm]"
                                    :type="
                                        field === 'email' ? 'email' : field === 'website' ? 'url' : field === 'return_policy_days' ? 'number' : 'text'
                                    "
                                />
                                <textarea
                                    v-else-if="field === 'address' || field === 'notes'"
                                    :id="String(field)"
                                    v-model="form[field as keyof BusinessForm]"
                                    rows="2"
                                    class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                />
                                <Input
                                    v-else
                                    :id="String(field)"
                                    v-model.number="form[field as keyof BusinessForm]"
                                    type="number"
                                    min="0"
                                    max="365"
                                />
                                <p v-if="config.description" class="text-xs text-muted-foreground">
                                    {{ config.description }}
                                </p>
                                <InputError v-if="form.errors[field as keyof BusinessForm]" :message="form.errors[field as keyof BusinessForm]" />
                            </div>
                        </template>
                    </CardContent>
                </Card>

                <div class="flex items-center justify-end gap-2">
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-1 animate-spin" />
                        Guardar cambios
                    </Button>
                </div>
            </form>
        </div>
    </SettingsLayout>
</template>
