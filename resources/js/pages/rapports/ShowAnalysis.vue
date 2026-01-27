<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
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
import { ArrowLeft, Download, Send } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';

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
    respondent_id: number;
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

type Props = {
    response: QuestionnaireResponse;
    responses: Record<string, Response[]>;
    canExport: boolean;
};

const props = defineProps<Props>();

const selectedResponseIds = ref<number[]>([]);
const selectAll = ref(false);
const showReturnForm = ref(false);

const form = useForm({
    correction_reason: '',
    response_ids: [] as number[],
});

const breadcrumbs: BreadcrumbItem[] = [
    { label: 'Dashboard', href: dashboard() },
    { label: 'Rapport', href: rapports.index().url },
    { label: 'Analyser rapport', href: rapports.analysis().url },
    { label: props.response.questionnaire?.title || 'Détails', href: '#' },
];

// Organiser les réponses par row_identifier avec leurs IDs
const organizedResponses = computed(() => {
    const result: Array<{
        rowIdentifier: string;
        responses: Record<number, Response>;
        responseIds: number[];
    }> = [];

    Object.entries(props.responses).forEach(([rowIdentifier, responses]) => {
        const responseMap: Record<number, Response> = {};
        const responseIds: number[] = [];
        responses.forEach((response) => {
            responseMap[response.question_id] = response;
            responseIds.push(response.id);
        });
        result.push({ rowIdentifier, responses: responseMap, responseIds });
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

const getRespondentName = (): string => {
    if (props.response.respondent?.employee) {
        return `${props.response.respondent.employee.first_name} ${props.response.respondent.employee.last_name}`;
    }
    return props.response.respondent?.name || 'N/A';
};

// Toggle sélection d'une ligne
const toggleRowSelection = (rowData: { responseIds: number[] }) => {
    const allSelected = rowData.responseIds.every((id) => selectedResponseIds.value.includes(id));

    if (allSelected) {
        // Désélectionner
        selectedResponseIds.value = selectedResponseIds.value.filter(
            (id) => !rowData.responseIds.includes(id),
        );
    } else {
        // Sélectionner
        rowData.responseIds.forEach((id) => {
            if (!selectedResponseIds.value.includes(id)) {
                selectedResponseIds.value.push(id);
            }
        });
    }
    updateSelectAll();
};

// Toggle sélection de toutes les lignes
const toggleSelectAll = () => {
    selectAll.value = !selectAll.value;
    if (selectAll.value) {
        // Sélectionner toutes les réponses
        organizedResponses.value.forEach((rowData) => {
            rowData.responseIds.forEach((id) => {
                if (!selectedResponseIds.value.includes(id)) {
                    selectedResponseIds.value.push(id);
                }
            });
        });
    } else {
        // Désélectionner toutes
        selectedResponseIds.value = [];
    }
};

// Mettre à jour l'état "sélectionner tout"
const updateSelectAll = () => {
    const allResponseIds: number[] = [];
    organizedResponses.value.forEach((rowData) => {
        allResponseIds.push(...rowData.responseIds);
    });
    selectAll.value =
        allResponseIds.length > 0 &&
        allResponseIds.every((id) => selectedResponseIds.value.includes(id));
};

// Vérifier si une ligne est sélectionnée
const isRowSelected = (rowData: { responseIds: number[] }): boolean => {
    return rowData.responseIds.every((id) => selectedResponseIds.value.includes(id));
};

// Renvoyer pour correction
const returnForCorrection = () => {
    if (selectedResponseIds.value.length === 0) {
        alert('Veuillez sélectionner au moins une ligne à corriger');
        return;
    }

    if (!form.correction_reason.trim()) {
        alert('Veuillez fournir une raison de correction');
        return;
    }

    form.response_ids = selectedResponseIds.value;

    form.post(
        rapports.returnForCorrection({ response: props.response.id }).url,
        {
            onSuccess: () => {
                router.visit(rapports.analysis().url);
            },
        },
    );
};

// Exporter en Excel
const exportData = () => {
    window.location.href = rapports.export().url;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Analyse: ${response.questionnaire?.title || 'Rapport'}`" />

        <div class="container mx-auto py-6 space-y-6">
            <!-- En-tête -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="rapports.analysis().url">
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
                <Button v-if="canExport" variant="outline" @click="exportData">
                    <Download class="mr-2 h-4 w-4" />
                    Exporter en Excel
                </Button>
            </div>

            <!-- Informations du rapport -->
            <Card>
                <CardHeader>
                    <CardTitle>Informations du rapport</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Répondant</p>
                            <p class="mt-1">{{ getRespondentName() }}</p>
                        </div>
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
                    </div>
                </CardContent>
            </Card>

            <!-- Tableau des réponses -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Réponses</CardTitle>
                            <CardDescription>
                                Sélectionnez les lignes à corriger si nécessaire
                            </CardDescription>
                        </div>
                        <div class="flex items-center gap-2">
                            <Checkbox :checked="selectAll" @update:checked="toggleSelectAll" />
                            <Label>Sélectionner tout</Label>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-12">
                                        <Checkbox :checked="selectAll" @update:checked="toggleSelectAll" />
                                    </TableHead>
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
                                    <TableCell>
                                        <Checkbox
                                            :checked="isRowSelected(rowData)"
                                            @update:checked="toggleRowSelection(rowData)"
                                        />
                                    </TableCell>
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

            <!-- Formulaire de renvoi pour correction -->
            <Card v-if="selectedResponseIds.length > 0">
                <CardHeader>
                    <CardTitle>Renvoi pour correction</CardTitle>
                    <CardDescription>
                        {{ selectedResponseIds.length }} ligne(s) sélectionnée(s)
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div>
                        <Label for="correction_reason">Raison de correction *</Label>
                        <Textarea
                            id="correction_reason"
                            v-model="form.correction_reason"
                            placeholder="Expliquez pourquoi ce rapport doit être corrigé..."
                            :rows="4"
                            :required="true"
                        />
                        <InputError :message="form.errors.correction_reason" />
                    </div>
                    <div class="flex justify-end gap-4">
                        <Button variant="outline" @click="selectedResponseIds = []">
                            Annuler
                        </Button>
                        <Button @click="returnForCorrection" :disabled="form.processing">
                            <Send class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Envoi...' : 'Renvoyer pour correction' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Actions -->
            <div class="flex justify-end gap-4">
                <Button variant="outline" as-child>
                    <Link :href="rapports.analysis().url">Retour</Link>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
