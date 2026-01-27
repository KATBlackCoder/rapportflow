<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import rapports from '@/routes/rapports';
import { type BreadcrumbItem } from '@/types';
import {
    ClipboardList,
    Eye,
    FileCheck,
    FileText,
    TrendingUp,
} from 'lucide-vue-next';

type Option = {
    value: string;
    label: string;
    description: string;
};

type Props = {
    options: Option[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { label: 'Dashboard', href: dashboard() },
    { label: 'Rapport', href: rapports.index().url },
];

const handleAction = (value: string) => {
    switch (value) {
        case 'create':
            router.visit(rapports.create().url);
            break;
        case 'my-reports':
            router.visit(rapports.myReports().url);
            break;
        case 'corrections':
            router.visit(rapports.corrections().url);
            break;
        case 'analysis':
            router.visit(rapports.analysis().url);
            break;
        default:
            break;
    }
};

const getIcon = (value: string) => {
    const icons: Record<string, any> = {
        create: ClipboardList,
        'my-reports': Eye,
        corrections: FileCheck,
        analysis: TrendingUp,
    };
    return icons[value] || FileText;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Rapport" />

        <div class="container mx-auto py-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold">Rapport</h1>
                <p class="text-muted-foreground">
                    Sélectionnez une action pour accéder aux fonctionnalités de rapport
                </p>
            </div>

            <div v-if="options.length === 0" class="text-center py-12">
                <p class="text-muted-foreground">Aucune option disponible pour votre rôle.</p>
            </div>

            <div
                v-else
                class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
            >
                <Card
                    v-for="option in options"
                    :key="option.value"
                    class="hover:shadow-lg transition-shadow cursor-pointer"
                    @click="handleAction(option.value)"
                >
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10"
                            >
                                <component
                                    :is="getIcon(option.value)"
                                    class="h-6 w-6 text-primary"
                                />
                            </div>
                            <CardTitle class="text-lg">{{ option.label }}</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <CardDescription class="mb-4">
                            {{ option.description }}
                        </CardDescription>
                        <Button class="w-full" @click.stop="handleAction(option.value)">
                            Accéder
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
