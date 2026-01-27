<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { ArrowLeft, Download, Eye, FileText } from 'lucide-vue-next';

type Report = {
    questionnaire_id: number;
    respondent_id: number;
    row_identifier: string | null;
    status: string;
    submitted_at: string | null;
    questionnaire?: {
        id: number;
        title: string;
    };
    respondent?: {
        id: number;
        name: string;
        employee?: {
            id: number;
            first_name: string;
            last_name: string;
        };
    };
};

type Questionnaire = {
    id: number;
    title: string;
};

type User = {
    id: number;
    name: string;
};

type Props = {
    reports: PaginatedData<Report>;
    questionnaires: Questionnaire[];
    respondents: User[];
    filters: {
        questionnaire_id?: number;
        respondent_id?: number;
        status?: string;
        date_from?: string;
        date_to?: string;
    };
    canExport: boolean;
};

const props = defineProps<Props>();

const questionnaireFilter = ref<string>(
    props.filters.questionnaire_id?.toString() || 'all',
);
const respondentFilter = ref<string>(props.filters.respondent_id?.toString() || 'all');
const statusFilter = ref<string>(props.filters.status || 'all');
const dateFromFilter = ref<string>(props.filters.date_from || '');
const dateToFilter = ref<string>(props.filters.date_to || '');

const breadcrumbs: BreadcrumbItem[] = [
    { label: 'Dashboard', href: dashboard() },
    { label: 'Rapport', href: rapports.index().url },
    { label: 'Analyser rapport', href: rapports.analysis().url },
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

const getRespondentName = (report: Report): string => {
    if (report.respondent?.employee) {
        return `${report.respondent.employee.first_name} ${report.respondent.employee.last_name}`;
    }
    return report.respondent?.name || 'N/A';
};

const applyFilters = () => {
    router.get(
        rapports.analysis().url,
        {
            questionnaire_id:
                questionnaireFilter.value === 'all' ? undefined : questionnaireFilter.value,
            respondent_id: respondentFilter.value === 'all' ? undefined : respondentFilter.value,
            status: statusFilter.value === 'all' ? undefined : statusFilter.value,
            date_from: dateFromFilter.value || undefined,
            date_to: dateToFilter.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const exportData = () => {
    const params = new URLSearchParams();
    if (questionnaireFilter.value !== 'all') {
        params.append('questionnaire_id', questionnaireFilter.value);
    }
    if (respondentFilter.value !== 'all') {
        params.append('respondent_id', respondentFilter.value);
    }
    if (statusFilter.value !== 'all') {
        params.append('status', statusFilter.value);
    }
    if (dateFromFilter.value) {
        params.append('date_from', dateFromFilter.value);
    }
    if (dateToFilter.value) {
        params.append('date_to', dateToFilter.value);
    }

    window.location.href = `${rapports.export().url}?${params.toString()}`;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Analyser rapport" />

        <div class="container mx-auto py-6">
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="rapports.index().url">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold">Analyser rapport</h1>
                        <p class="text-sm text-muted-foreground">
                            Consultez et analysez les rapports soumis
                        </p>
                    </div>
                </div>
                <Button v-if="canExport" @click="exportData">
                    <Download class="mr-2 h-4 w-4" />
                    Exporter en Excel
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="h-5 w-5" />
                        Rapports à analyser
                    </CardTitle>
                    <CardDescription>
                        Liste des rapports soumis. Vous pouvez les consulter et les renvoyer pour
                        correction si nécessaire.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <!-- Filtres -->
                    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-5">
                        <div>
                            <Label>Questionnaire</Label>
                            <Select v-model="questionnaireFilter" @update:model-value="applyFilters">
                                <SelectTrigger>
                                    <SelectValue placeholder="Tous" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous</SelectItem>
                                    <SelectItem
                                        v-for="questionnaire in questionnaires"
                                        :key="questionnaire.id"
                                        :value="questionnaire.id.toString()"
                                    >
                                        {{ questionnaire.title }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label>Répondant</Label>
                            <Select v-model="respondentFilter" @update:model-value="applyFilters">
                                <SelectTrigger>
                                    <SelectValue placeholder="Tous" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous</SelectItem>
                                    <SelectItem
                                        v-for="respondent in respondents"
                                        :key="respondent.id"
                                        :value="respondent.id.toString()"
                                    >
                                        {{ respondent.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label>Statut</Label>
                            <Select v-model="statusFilter" @update:model-value="applyFilters">
                                <SelectTrigger>
                                    <SelectValue placeholder="Tous" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous</SelectItem>
                                    <SelectItem value="submitted">Soumis</SelectItem>
                                    <SelectItem value="returned_for_correction">
                                        Renvoyé pour correction
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label>Date de début</Label>
                            <Input
                                v-model="dateFromFilter"
                                type="date"
                                @change="applyFilters"
                            />
                        </div>

                        <div>
                            <Label>Date de fin</Label>
                            <Input v-model="dateToFilter" type="date" @change="applyFilters" />
                        </div>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Questionnaire</TableHead>
                                <TableHead>Répondant</TableHead>
                                <TableHead>Identifiant ligne</TableHead>
                                <TableHead>Statut</TableHead>
                                <TableHead>Date de soumission</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableEmpty
                                v-if="reports.data.length === 0"
                                :colspan="6"
                                message="Aucun rapport trouvé"
                            />
                            <TableRow v-for="(report, index) in reports.data" :key="index">
                                <TableCell class="font-medium">
                                    {{ report.questionnaire?.title || 'N/A' }}
                                </TableCell>
                                <TableCell>{{ getRespondentName(report) }}</TableCell>
                                <TableCell>{{ report.row_identifier || 'N/A' }}</TableCell>
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
                                    <Button variant="outline" size="sm" as-child>
                                        <Link
                                            :href="
                                                rapports.showAnalysis({
                                                    response: report.questionnaire_id,
                                                }).url
                                            "
                                        >
                                            <Eye class="mr-2 h-4 w-4" />
                                            Voir
                                        </Link>
                                    </Button>
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
