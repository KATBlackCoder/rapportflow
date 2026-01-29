<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import rapports from '@/routes/rapports';
import { BarChart3 } from 'lucide-vue-next';
import type { RecentReport } from './types';
import { formatDate } from './utils';

defineProps<{
    reports: RecentReport[];
}>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <BarChart3 class="h-5 w-5" />
                Activité récente
            </CardTitle>
            <CardDescription>
                Derniers rapports soumis
            </CardDescription>
        </CardHeader>
        <CardContent>
            <ul class="space-y-3">
                <li
                    v-for="report in reports"
                    :key="report.id"
                    class="flex items-center justify-between border-b border-sidebar-border/70 py-2 last:border-0 dark:border-sidebar-border"
                >
                    <div>
                        <p class="font-medium">{{ report.questionnaire_title }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ report.respondent_name }}
                            · {{ formatDate(report.submitted_at) }}
                        </p>
                    </div>
                    <Badge variant="secondary">Soumis</Badge>
                </li>
            </ul>
        </CardContent>
        <CardFooter>
            <Button variant="outline" as-child>
                <Link :href="rapports.analysis().url">Analyser tous les rapports</Link>
            </Button>
        </CardFooter>
    </Card>
</template>
