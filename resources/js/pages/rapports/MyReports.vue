<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
import { ArrowLeft, Eye, FileText } from 'lucide-vue-next';

type Report = {
    questionnaire_id: number;
    row_identifier: string | null;
    status: string;
    submitted_at: string | null;
    correction_reason: string | null;
    questionnaire?: {
        id: number;
        title: string;
    };
};

type Props = {
    reports: PaginatedData<Report>;
    filters: {
        questionnaire_id?: number;
        status?: string;
        date_from?: string;
        date_to?: string;
    };
};

const props = defineProps<Props>();

const searchQuery = ref('');
const statusFilter = ref<string>(props.filters.status || 'all');
const questionnaireFilter = ref<string>(props.filters.questionnaire_id?.toString() || 'all');

const breadcrumbs: BreadcrumbItem[] = [
    { label: 'Dashboard', href: dashboard() },
    { label: 'Rapport', href: rapports.index().url },
    { label: 'Mes rapports', href: rapports.myReports().url },
];

const getStatusBadgeVariant = (
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        submitted: 'default',
        returned_for_correction: 'destructive',
    };

    return variants[status] || 'secondary';
};

const getStatusLabel = (status: string): string => {
    const labels: Record<string, string> = {
        submitted: 'Soumis',
        returned_for_correction: 'Renvoyé pour correction',
    };

    return labels[status] || status;
};

const applyFilters = () => {
    router.get(
        rapports.myReports().url,
        {
            status: statusFilter.value === 'all' ? undefined : statusFilter.value,
            questionnaire_id:
                questionnaireFilter.value === 'all' ? undefined : questionnaireFilter.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Mes rapports" />

        <div class="container mx-auto py-6">
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="rapports.index().url">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold">Mes rapports</h1>
                        <p class="text-sm text-muted-foreground">
                            Consultez vos rapports soumis (lecture seule)
                        </p>
                    </div>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="h-5 w-5" />
                        Rapports soumis
                    </CardTitle>
                    <CardDescription>
                        Liste de tous vos rapports soumis. Aucune modification n'est possible ici.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="mb-4 flex gap-4">
                        <div class="flex-1">
                            <Select v-model="statusFilter" @update:model-value="applyFilters">
                                <SelectTrigger>
                                    <SelectValue placeholder="Filtrer par statut" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous les statuts</SelectItem>
                                    <SelectItem value="submitted">Soumis</SelectItem>
                                    <SelectItem value="returned_for_correction">
                                        Renvoyé pour correction
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Questionnaire</TableHead>
                                <TableHead>Identifiant ligne</TableHead>
                                <TableHead>Statut</TableHead>
                                <TableHead>Date de soumission</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableEmpty
                                v-if="reports.data.length === 0"
                                :colspan="5"
                                message="Aucun rapport soumis"
                            />
                            <TableRow v-for="(report, index) in reports.data" :key="index">
                                <TableCell class="font-medium">
                                    {{ report.questionnaire?.title || 'N/A' }}
                                </TableCell>
                                <TableCell>
                                    {{ report.row_identifier || 'N/A' }}
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="getStatusBadgeVariant(report.status)">
                                        {{ getStatusLabel(report.status) }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    {{
                                        report.submitted_at
                                            ? new Date(report.submitted_at).toLocaleDateString(
                                                  'fr-FR',
                                              )
                                            : 'N/A'
                                    }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button variant="outline" size="sm" as-child>
                                            <Link
                                                :href="
                                                    rapports.showMyReport({
                                                        response: report.questionnaire_id,
                                                    }).url
                                                "
                                            >
                                                <Eye class="mr-2 h-4 w-4" />
                                                Voir
                                            </Link>
                                        </Button>
                                        <Button
                                            v-if="report.status === 'returned_for_correction'"
                                            variant="default"
                                            size="sm"
                                            as-child
                                        >
                                            <Link :href="rapports.corrections().url">
                                                Corriger
                                            </Link>
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Pagination -->
                    <div
                        v-if="reports.links && reports.links.length > 3"
                        class="mt-4 flex items-center justify-between"
                    >
                        <div class="text-sm text-muted-foreground">
                            Affichage de {{ reports.from }} à {{ reports.to }} sur
                            {{ reports.total }} résultats
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-for="link in reports.links"
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
