<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import rapports from '@/routes/rapports';
import { type BreadcrumbItem, type PaginatedData } from '@/types';
import { ArrowLeft, ClipboardList, FileText } from 'lucide-vue-next';

type Questionnaire = {
    id: number;
    title: string;
    description: string | null;
    status: string;
    target_type: string;
    created_at: string;
    questions_count?: number;
};

type Props = {
    questionnaires: PaginatedData<Questionnaire>;
};

const props = defineProps<Props>();

const searchQuery = ref('');

const breadcrumbs: BreadcrumbItem[] = [
    { label: 'Dashboard', href: dashboard() },
    { label: 'Rapport', href: rapports.index().url },
    { label: 'Faire un rapport', href: rapports.create().url },
];

const search = () => {
    router.get(
        rapports.create().url,
        {
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const getTargetTypeLabel = (targetType: string): string => {
    const labels: Record<string, string> = {
        employees: 'Employés',
        supervisors: 'Superviseurs',
    };

    return labels[targetType] || targetType;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Faire un rapport" />

        <div class="container mx-auto py-6">
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="rapports.index().url">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold">Faire un rapport</h1>
                        <p class="text-sm text-muted-foreground">
                            Sélectionnez un questionnaire à remplir
                        </p>
                    </div>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <ClipboardList class="h-5 w-5" />
                                Questionnaires disponibles
                            </CardTitle>
                            <CardDescription>
                                Liste des questionnaires que vous pouvez remplir
                            </CardDescription>
                        </div>
                        <div class="flex items-center gap-2">
                            <Input
                                v-model="searchQuery"
                                placeholder="Rechercher..."
                                class="w-64"
                                @keyup.enter="search"
                            />
                            <Button @click="search">Rechercher</Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Titre</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead>Cible</TableHead>
                                <TableHead>Questions</TableHead>
                                <TableHead>Date de création</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableEmpty
                                v-if="questionnaires.data.length === 0"
                                :colspan="6"
                                message="Aucun questionnaire disponible"
                            />
                            <TableRow
                                v-for="questionnaire in questionnaires.data"
                                :key="questionnaire.id"
                            >
                                <TableCell class="font-medium">
                                    {{ questionnaire.title }}
                                </TableCell>
                                <TableCell>
                                    <span class="line-clamp-2">
                                        {{ questionnaire.description || 'Aucune description' }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">
                                        {{ getTargetTypeLabel(questionnaire.target_type) }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    {{ questionnaire.questions_count || 0 }} question(s)
                                </TableCell>
                                <TableCell>
                                    {{ new Date(questionnaire.created_at).toLocaleDateString('fr-FR') }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button as-child>
                                        <Link :href="rapports.show({ questionnaire: questionnaire.id }).url">
                                            <FileText class="mr-2 h-4 w-4" />
                                            Remplir
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Pagination -->
                    <div
                        v-if="questionnaires.links && questionnaires.links.length > 3"
                        class="mt-4 flex items-center justify-between"
                    >
                        <div class="text-sm text-muted-foreground">
                            Affichage de {{ questionnaires.from }} à {{ questionnaires.to }} sur
                            {{ questionnaires.total }} résultats
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-for="link in questionnaires.links"
                                :key="link.label"
                                variant="outline"
                                size="sm"
                                :disabled="!link.url"
                                @click="link.url && router.visit(link.url)"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
