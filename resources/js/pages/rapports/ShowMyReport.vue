<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import rapports from '@/routes/rapports';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft, Edit } from 'lucide-vue-next';

type Question = {
    id: number;
    type: string;
    question: string;
    required: boolean;
    order: number;
    options?: string[] | null;
};

type Response = {
    id: number;
    question_id: number;
    response: any;
    question?: Question;
};

type QuestionnaireResponse = {
    id: number;
    questionnaire_id: number;
    row_identifier: string | null;
    status: string;
    submitted_at: string | null;
    correction_reason: string | null;
    questionnaire?: {
        id: number;
        title: string;
        description: string | null;
        questions: Question[];
    };
};

type Props = {
    response: QuestionnaireResponse;
    responses: Record<string, Response[]>;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { label: 'Dashboard', href: dashboard() },
    { label: 'Rapport', href: rapports.index().url },
    { label: 'Mes rapports', href: rapports.myReports().url },
    { label: props.response.questionnaire?.title || 'Détails', href: '#' },
];

// Organiser les réponses par row_identifier
const organizedResponses = computed(() => {
    const result: Array<{ rowIdentifier: string; responses: Record<number, Response> }> = [];

    Object.entries(props.responses).forEach(([rowIdentifier, responses]) => {
        const responseMap: Record<number, Response> = {};
        responses.forEach((response) => {
            responseMap[response.question_id] = response;
        });
        result.push({ rowIdentifier, responses: responseMap });
    });

    return result;
});

// Questions triées par ordre
const questions = computed(() => {
    return props.response.questionnaire?.questions
        .filter((q) => !q.conditional_question_id)
        .sort((a, b) => a.order - b.order) || [];
});

// Obtenir la valeur formatée d'une réponse
const getFormattedResponse = (response: Response | undefined): string => {
    if (!response) return 'N/A';

    const value = response.response;

    if (Array.isArray(value)) {
        return value.join(', ');
    }

    if (typeof value === 'object' && value !== null) {
        return JSON.stringify(value);
    }

    return String(value || 'N/A');
};

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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Détails: ${response.questionnaire?.title || 'Rapport'}`" />

        <div class="container mx-auto py-6 space-y-6">
            <!-- En-tête -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="rapports.myReports().url">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold">
                            {{ response.questionnaire?.title || 'Rapport' }}
                        </h1>
                        <p v-if="response.questionnaire?.description" class="text-sm text-muted-foreground">
                            {{ response.questionnaire.description }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Informations du rapport -->
            <Card>
                <CardHeader>
                    <CardTitle>Informations du rapport</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Statut</p>
                            <Badge :variant="getStatusBadgeVariant(response.status)" class="mt-1">
                                {{ getStatusLabel(response.status) }}
                            </Badge>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Date de soumission</p>
                            <p class="mt-1">
                                {{
                                    response.submitted_at
                                        ? new Date(response.submitted_at).toLocaleString('fr-FR')
                                        : 'N/A'
                                }}
                            </p>
                        </div>
                        <div v-if="response.row_identifier">
                            <p class="text-sm font-medium text-muted-foreground">Identifiant de ligne</p>
                            <p class="mt-1">{{ response.row_identifier }}</p>
                        </div>
                        <div v-if="response.correction_reason">
                            <p class="text-sm font-medium text-muted-foreground">Raison de correction</p>
                            <p class="mt-1 text-destructive">{{ response.correction_reason }}</p>
                        </div>
                    </div>

                    <div v-if="response.status === 'returned_for_correction'" class="mt-4">
                        <Button as-child>
                            <Link :href="rapports.corrections().url">
                                <Edit class="mr-2 h-4 w-4" />
                                Aller corriger
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Tableau des réponses -->
            <Card>
                <CardHeader>
                    <CardTitle>Réponses</CardTitle>
                    <CardDescription>
                        Affichage en lecture seule - Aucune modification possible
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-12">#</TableHead>
                                    <TableHead v-for="question in questions" :key="question.id">
                                        {{ question.question }}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="(rowData, rowIndex) in organizedResponses"
                                    :key="rowData.rowIdentifier"
                                >
                                    <TableCell class="font-medium">{{ rowIndex + 1 }}</TableCell>
                                    <TableCell
                                        v-for="question in questions"
                                        :key="question.id"
                                    >
                                        {{ getFormattedResponse(rowData.responses[question.id]) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <!-- Actions -->
            <div class="flex justify-end gap-4">
                <Button variant="outline" as-child>
                    <Link :href="rapports.myReports().url">Retour</Link>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
