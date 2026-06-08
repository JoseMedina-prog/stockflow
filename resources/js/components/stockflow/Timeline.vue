<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDateTime } from '@/composables/useFormat';
import {
    Activity,
    CheckCircle2,
    Clock,
    FileText,
    Mail,
    MessageCircle,
    MessageSquare,
    Phone,
    Trash2,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface ActivityItem {
    id: number;
    type: string;
    type_label: string;
    description: string | null;
    outcome: string | null;
    occurred_at: string;
    duration_minutes: number | null;
    user: { id: number; name: string } | null;
    subject_type: string;
    subject_href: string | null;
    subject_label: string;
}

interface TaskItem {
    id: number;
    title: string;
    priority: string;
    priority_badge: string;
    status: string;
    status_badge: string;
    due_date: string | null;
    is_overdue: boolean;
    is_due_today: boolean;
    is_open: boolean;
    assignee: { id: number; name: string } | null;
    href: string;
}

type TimelineItem =
    | ({ kind: 'activity' } & ActivityItem)
    | ({ kind: 'task' } & TaskItem);

const props = defineProps<{
    items: TimelineItem[];
    canDelete?: boolean;
}>();

const emit = defineEmits<{
    (e: 'delete-activity', id: number): void;
}>();

const sorted = computed(() =>
    [...props.items].sort((a, b) => {
        const dateA = a.kind === 'activity' ? new Date(a.occurred_at).getTime() : (a.due_date ? new Date(a.due_date).getTime() : 0);
        const dateB = b.kind === 'activity' ? new Date(b.occurred_at).getTime() : (b.due_date ? new Date(b.due_date).getTime() : 0);
        return dateB - dateA;
    }),
);

const iconFor = (type: string) => {
    switch (type) {
        case 'call': return Phone;
        case 'email': return Mail;
        case 'meeting': return Users;
        case 'note': return FileText;
        case 'message': return MessageSquare;
        case 'whatsapp': return MessageCircle;
        case 'task': return CheckCircle2;
        default: return Activity;
    }
};

const colorFor = (type: string) => {
    switch (type) {
        case 'call': return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 ring-blue-500/20';
        case 'email': return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 ring-slate-500/20';
        case 'meeting': return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 ring-amber-500/20';
        case 'note': return 'bg-zinc-500/10 text-zinc-600 dark:text-zinc-400 ring-zinc-500/20';
        case 'message': return 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 ring-cyan-500/20';
        case 'whatsapp': return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 ring-emerald-500/20';
        case 'task': return 'bg-violet-500/10 text-violet-600 dark:text-violet-400 ring-violet-500/20';
        default: return 'bg-muted text-muted-foreground';
    }
};

const formatDuration = (mins: number | null): string => {
    if (!mins) return '';
    if (mins < 60) return `${mins} min`;
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return m ? `${h}h ${m}m` : `${h}h`;
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="text-base">Línea de tiempo</CardTitle>
            <CardDescription>{{ sorted.length }} evento(s) en orden cronológico inverso.</CardDescription>
        </CardHeader>
        <CardContent>
            <div v-if="sorted.length === 0" class="rounded-md border border-dashed border-border/60 p-6 text-center text-sm text-muted-foreground">
                Aún no hay eventos. Registra una actividad o tarea para empezar la línea de tiempo.
            </div>
            <ol v-else class="relative space-y-4">
                <li
                    v-for="item in sorted"
                    :key="`${item.kind}-${item.id}`"
                    class="group relative flex gap-3"
                >
                    <div class="flex flex-col items-center">
                        <div
                            class="flex size-9 shrink-0 items-center justify-center rounded-full ring-1"
                            :class="colorFor(item.kind === 'activity' ? item.type : 'task')"
                        >
                            <component :is="iconFor(item.kind === 'activity' ? item.type : 'task')" class="size-4" />
                        </div>
                        <div class="mt-2 h-full w-px bg-border" />
                    </div>

                    <div class="flex-1 pb-2">
                        <div v-if="item.kind === 'activity'">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1">
                                    <p class="text-xs text-muted-foreground">
                                        <span class="font-medium uppercase tracking-wider">{{ item.type_label }}</span>
                                        · {{ item.user?.name ?? '—' }}
                                        · <span class="hidden sm:inline">{{ formatDateTime(item.occurred_at) }}</span>
                                    </p>
                                    <p v-if="item.description" class="mt-1 text-sm">{{ item.description }}</p>
                                    <p v-if="item.outcome" class="mt-1 rounded-md border border-emerald-500/20 bg-emerald-500/5 p-2 text-xs text-emerald-700 dark:text-emerald-300">
                                        <span class="font-semibold">Resultado:</span> {{ item.outcome }}
                                    </p>
                                    <p class="mt-1 text-xs text-muted-foreground">
                                        <span v-if="item.duration_minutes" class="inline-flex items-center gap-1">
                                            <Clock class="size-3" />
                                            {{ formatDuration(item.duration_minutes) }}
                                        </span>
                                    </p>
                                </div>
                                <button
                                    v-if="canDelete"
                                    class="rounded-md p-1 text-muted-foreground opacity-0 transition-opacity hover:bg-destructive/10 hover:text-destructive group-hover:opacity-100"
                                    @click="emit('delete-activity', item.id)"
                                >
                                    <Trash2 class="size-3.5" />
                                </button>
                            </div>
                        </div>

                        <div v-else>
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1">
                                    <p class="text-xs text-muted-foreground">
                                        <span class="font-medium uppercase tracking-wider">Tarea</span>
                                        · {{ item.assignee?.name ?? '—' }}
                                        <span v-if="item.due_date">· <span class="hidden sm:inline">{{ formatDateTime(item.due_date) }}</span></span>
                                    </p>
                                    <p class="mt-1 text-sm font-medium">
                                        <a :href="item.href" class="hover:underline">{{ item.title }}</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            </ol>
        </CardContent>
    </Card>
</template>
