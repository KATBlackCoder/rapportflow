<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
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
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import rapports from '@/routes/rapports';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft, Copy, Plus, Save, Trash2 } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';

type Question = {
    id: number;
    type: string;
    question: string;
    required: boolean;
    order: number;
    options?: string[] | null;
    conditional_question_id?: number | null;
    conditional_value?: string | null;
    conditionalQuestion?: Question | null;
};

type Questionnaire = {
    id: number;
    title: string;
    description: string | null;
    questions: Question[];
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
    questionnaire?: Questionnaire;
};

type Props = {
    response: QuestionnaireResponse;
    responses: Record<string, Response[]>;
};

const props = defineProps<Props>();

const questionnaire = computed(() => props.response.questionnaire!);

type RowData = {
    rowIdentifier: string;
    responses: Record<number, any>; // question_id => response value
};

const rows = ref<RowData[]>([]);
const pasteText = ref('');
const showPasteHelper = ref(false);

const form = useForm({
    questionnaire_id: props.response.questionnaire_id,
    responses: [] as Array<{
        question_id: number;
        row_identifier: string | null;
        response: any;
    }>,
});

const breadcrumbs: BreadcrumbItem[] = [
    { label: 'Dashboard', href: dashboard() },
    { label: 'Rapport', href: rapports.index().url },
    { label: 'Corriger un rapport', href: rapports.corrections().url },
    { label: questionnaire.value.title, href: '#' },
];

// Questions triées par ordre, sans les conditionnelles
const visibleQuestions = computed(() => {
    return questionnaire.value.questions
        .filter((q) => !q.conditional_question_id)
        .sort((a, b) => a.order - b.order);
});

// Questions conditionnelles groupées par question parente
const conditionalQuestions = computed(() => {
    const map = new Map<number, Question[]>();
    questionnaire.value.questions
        .filter((q) => q.conditional_question_id)
        .forEach((q) => {
            const parentId = q.conditional_question_id!;
            if (!map.has(parentId)) {
                map.set(parentId, []);
            }
            map.get(parentId)!.push(q);
        });
    return map;
});

// Vérifier si une question conditionnelle doit être visible
const isConditionalQuestionVisible = (question: Question, rowIndex: number): boolean => {
    if (!question.conditional_question_id) return true;

    const row = rows.value[rowIndex];
    if (!row) return false;

    const parentResponse = row.responses[question.conditional_question_id];
    if (!parentResponse) return false;

    const parentQuestion = questionnaire.value.questions.find(
        (q) => q.id === question.conditional_question_id,
    );
    if (!parentQuestion) return false;

    // Pour select/radio, comparer avec conditional_value
    if (parentQuestion.type === 'select' || parentQuestion.type === 'radio') {
        return parentResponse === question.conditional_value;
    }

    return false;
};

// Ajouter une nouvelle ligne
const addRow = () => {
    rows.value.push({
        rowIdentifier: `row-${Date.now()}`,
        responses: {},
    });
};

// Supprimer une ligne
const removeRow = (index: number) => {
    rows.value.splice(index, 1);
};

// Obtenir la valeur d'une réponse
const getResponseValue = (rowIndex: number, questionId: number): any => {
    return rows.value[rowIndex]?.responses[questionId] ?? null;
};

// Définir la valeur d'une réponse
const setResponseValue = (rowIndex: number, questionId: number, value: any) => {
    if (!rows.value[rowIndex]) return;
    rows.value[rowIndex].responses[questionId] = value;

    // Si c'est une question parente, vérifier les questions conditionnelles
    const parentQuestion = questionnaire.value.questions.find((q) => q.id === questionId);
    if (parentQuestion) {
        const conditionals = conditionalQuestions.value.get(questionId) || [];
        conditionals.forEach((conditional) => {
            // Si la condition n'est plus remplie, effacer la réponse
            if (!isConditionalQuestionVisible(conditional, rowIndex)) {
                delete rows.value[rowIndex].responses[conditional.id];
            }
        });
    }
};

// Générer le format d'exemple pour le copier-coller
const generatePasteFormat = (): string => {
    const columns = visibleQuestions.value.map((q) => q.question);
    const formatExample = columns.join(',');

    let valuesExample = '';
    visibleQuestions.value.forEach((q, index) => {
        if (index > 0) valuesExample += ',';
        if (q.type === 'text' || q.type === 'textarea' || q.type === 'email') {
            valuesExample += 'valeur_exemple';
        } else if (q.type === 'number') {
            valuesExample += '123';
        } else if (q.type === 'date') {
            valuesExample += '2026-01-27';
        } else if (q.type === 'select' || q.type === 'radio') {
            valuesExample += '1';
        } else if (q.type === 'checkbox' || q.type === 'selectmulti') {
            valuesExample += '(1,2)';
        }
    });

    return `${formatExample}\n\nExemple: ${valuesExample};`;
};

// Parser le texte collé
const parsePastedData = (text: string): string[][] => {
    const lines = text.split(';').filter((line) => line.trim());
    return lines.map((line) => {
        // Parser les valeurs, en gérant les guillemets et les parenthèses
        const values: string[] = [];
        let current = '';
        let inQuotes = false;
        let inParentheses = false;

        for (let i = 0; i < line.length; i++) {
            const char = line[i];

            if (char === '"' && !inParentheses) {
                inQuotes = !inQuotes;
            } else if (char === '(' && !inQuotes) {
                inParentheses = true;
                current += char;
            } else if (char === ')' && !inQuotes) {
                inParentheses = false;
                current += char;
            } else if (char === ',' && !inQuotes && !inParentheses) {
                values.push(current.trim());
                current = '';
            } else {
                current += char;
            }
        }

        if (current.trim()) {
            values.push(current.trim());
        }

        return values;
    });
};

// Appliquer les données collées
const applyPastedData = () => {
    if (!pasteText.value.trim()) return;

    try {
        const parsedLines = parsePastedData(pasteText.value);
        const questions = visibleQuestions.value;

        parsedLines.forEach((lineValues) => {
            if (lineValues.length !== questions.length) {
                throw new Error(
                    `Nombre de colonnes incorrect. Attendu: ${questions.length}, Reçu: ${lineValues.length}`,
                );
            }

            const newRow: RowData = {
                rowIdentifier: `row-${Date.now()}-${Math.random()}`,
                responses: {},
            };

            lineValues.forEach((value, index) => {
                const question = questions[index];
                let processedValue: any = value;

                // Traiter selon le type de question
                if (question.type === 'select' || question.type === 'radio') {
                    // Convertir le numéro en valeur de l'option
                    const optionIndex = parseInt(value) - 1;
                    if (question.options && question.options[optionIndex] !== undefined) {
                        processedValue = question.options[optionIndex];
                    }
                } else if (question.type === 'checkbox' || question.type === 'selectmulti') {
                    // Parser (1,2) en tableau de valeurs
                    const match = value.match(/\(([^)]+)\)/);
                    if (match) {
                        const indices = match[1].split(',').map((i) => parseInt(i.trim()) - 1);
                        processedValue = indices
                            .filter((idx) => question.options && question.options[idx] !== undefined)
                            .map((idx) => question.options![idx]);
                    }
                } else if (question.type === 'number') {
                    processedValue = parseFloat(value);
                }

                newRow.responses[question.id] = processedValue;
            });

            rows.value.push(newRow);
        });

        pasteText.value = '';
        showPasteHelper.value = false;
    } catch (error: any) {
        alert(`Erreur lors du traitement: ${error.message}`);
    }
};

// Copier le format d'exemple
const copyFormatExample = () => {
    const format = generatePasteFormat();
    navigator.clipboard.writeText(format);
    alert('Format copié dans le presse-papiers!');
};

// Soumettre le formulaire
const submit = () => {
    // Construire les réponses
    const responses: Array<{
        question_id: number;
        row_identifier: string | null;
        response: any;
    }> = [];

    rows.value.forEach((row) => {
        questionnaire.value.questions.forEach((question) => {
            const value = row.responses[question.id];
            if (value !== undefined && value !== null && value !== '') {
                // Vérifier si la question conditionnelle est visible
                if (question.conditional_question_id) {
                    // On vérifiera côté serveur aussi
                }

                responses.push({
                    question_id: question.id,
                    row_identifier: row.rowIdentifier,
                    response: value,
                });
            } else if (question.required) {
                // Question obligatoire non remplie
                throw new Error(`La question "${question.question}" est obligatoire`);
            }
        });
    });

    if (responses.length === 0) {
        alert('Veuillez remplir au moins une ligne de données');
        return;
    }

    form.responses = responses;

    form.put(rapports.updateCorrection({ response: props.response.id }).url, {
        onSuccess: () => {
            router.visit(rapports.myReports().url);
        },
    });
};

// Pré-remplir les données existantes
watch(
    () => props.responses,
    (responses) => {
        if (responses && Object.keys(responses).length > 0) {
            rows.value = [];
            Object.entries(responses).forEach(([rowIdentifier, responseList]) => {
                const rowData: RowData = {
                    rowIdentifier,
                    responses: {},
                };
                responseList.forEach((response) => {
                    if (response.question_id) {
                        rowData.responses[response.question_id] = response.response;
                    }
                });
                rows.value.push(rowData);
            });
        } else if (rows.value.length === 0) {
            addRow();
        }
    },
    { immediate: true },
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Corriger: ${questionnaire.title}`" />

        <div class="container mx-auto py-6 space-y-6">
            <!-- En-tête -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="rapports.corrections().url">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold">{{ questionnaire.title }}</h1>
                        <p v-if="questionnaire.description" class="text-sm text-muted-foreground">
                            {{ questionnaire.description }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Raison de correction -->
            <Card v-if="response.correction_reason" class="border-destructive">
                <CardHeader>
                    <CardTitle class="text-destructive">Raison de la correction</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm">{{ response.correction_reason }}</p>
                </CardContent>
            </Card>

            <!-- Instructions et format copier-coller -->
            <Card>
                <CardHeader>
                    <CardTitle>Instructions</CardTitle>
                    <CardDescription>
                        Remplissez le tableau ci-dessous ou utilisez le mode copier-coller
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">
                                Colonnes: {{ visibleQuestions.map((q) => q.question).join(', ') }}
                            </p>
                        </div>
                        <Button variant="outline" size="sm" @click="copyFormatExample">
                            <Copy class="mr-2 h-4 w-4" />
                            Copier le format
                        </Button>
                    </div>

                    <div v-if="showPasteHelper" class="space-y-2">
                        <Label>Coller vos données ici (format: valeur1,valeur2;valeur3,valeur4;)</Label>
                        <Textarea
                            v-model="pasteText"
                            placeholder="77000001,1,25,15000,(1,2),2;77000002,2,50,12000,(2),1;"
                            :rows="4"
                        />
                        <div class="flex gap-2">
                            <Button @click="applyPastedData">Appliquer le collage</Button>
                            <Button variant="outline" @click="showPasteHelper = false">
                                Annuler
                            </Button>
                        </div>
                    </div>
                    <Button v-else variant="outline" @click="showPasteHelper = true">
                        Mode copier-coller
                    </Button>
                </CardContent>
            </Card>

            <!-- Tableau de remplissage -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Données</CardTitle>
                            <CardDescription>Remplissez les informations ligne par ligne</CardDescription>
                        </div>
                        <Button @click="addRow">
                            <Plus class="mr-2 h-4 w-4" />
                            Ajouter une ligne
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-12">#</TableHead>
                                    <TableHead
                                        v-for="question in visibleQuestions"
                                        :key="question.id"
                                    >
                                        {{ question.question }}
                                        <Badge v-if="question.required" variant="destructive" class="ml-2">
                                            *
                                        </Badge>
                                    </TableHead>
                                    <TableHead class="w-20">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(row, rowIndex) in rows" :key="row.rowIdentifier">
                                    <TableCell class="font-medium">{{ rowIndex + 1 }}</TableCell>

                                    <!-- Colonnes pour chaque question -->
                                    <TableCell
                                        v-for="question in visibleQuestions"
                                        :key="question.id"
                                    >
                                        <!-- Input selon le type de question -->
                                        <Input
                                            v-if="
                                                question.type === 'text' ||
                                                question.type === 'email' ||
                                                question.type === 'date'
                                            "
                                            :model-value="getResponseValue(rowIndex, question.id)"
                                            :type="
                                                question.type === 'email'
                                                    ? 'email'
                                                    : question.type === 'date'
                                                      ? 'date'
                                                      : 'text'
                                            "
                                            :required="question.required"
                                            @update:model-value="
                                                setResponseValue(rowIndex, question.id, $event)
                                            "
                                        />

                                        <Textarea
                                            v-else-if="question.type === 'textarea'"
                                            :model-value="getResponseValue(rowIndex, question.id)"
                                            :required="question.required"
                                            :rows="2"
                                            class="min-w-[200px]"
                                            @update:model-value="
                                                setResponseValue(rowIndex, question.id, $event)
                                            "
                                        />

                                        <Input
                                            v-else-if="question.type === 'number'"
                                            :model-value="getResponseValue(rowIndex, question.id)"
                                            type="number"
                                            :required="question.required"
                                            @update:model-value="
                                                setResponseValue(
                                                    rowIndex,
                                                    question.id,
                                                    parseFloat($event) || null,
                                                )
                                            "
                                        />

                                        <Select
                                            v-else-if="
                                                question.type === 'select' || question.type === 'radio'
                                            "
                                            :model-value="getResponseValue(rowIndex, question.id)"
                                            @update:model-value="
                                                setResponseValue(rowIndex, question.id, $event)
                                            "
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Sélectionner..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="(option, optIndex) in question.options"
                                                    :key="optIndex"
                                                    :value="option"
                                                >
                                                    {{ option }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>

                                        <div
                                            v-else-if="
                                                question.type === 'checkbox' ||
                                                question.type === 'selectmulti'
                                            "
                                            class="space-y-2"
                                        >
                                            <div
                                                v-for="(option, optIndex) in question.options"
                                                :key="optIndex"
                                                class="flex items-center space-x-2"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :checked="
                                                        getResponseValue(rowIndex, question.id)?.includes(
                                                            option,
                                                        )
                                                    "
                                                    @change="
                                                        (e) => {
                                                            const current = getResponseValue(
                                                                rowIndex,
                                                                question.id,
                                                            ) || [];
                                                            if (e.target.checked) {
                                                                setResponseValue(rowIndex, question.id, [
                                                                    ...current,
                                                                    option,
                                                                ]);
                                                            } else {
                                                                setResponseValue(
                                                                    rowIndex,
                                                                    question.id,
                                                                    current.filter((v) => v !== option),
                                                                );
                                                            }
                                                        }
                                                    "
                                                />
                                                <Label class="text-sm">{{ option }}</Label>
                                            </div>
                                        </div>

                                        <!-- Questions conditionnelles -->
                                        <div
                                            v-if="
                                                conditionalQuestions.has(question.id) &&
                                                getResponseValue(rowIndex, question.id)
                                            "
                                            class="mt-2 space-y-2"
                                        >
                                            <div
                                                v-for="conditional in conditionalQuestions.get(
                                                    question.id,
                                                )"
                                                :key="conditional.id"
                                            >
                                                <div
                                                    v-if="
                                                        isConditionalQuestionVisible(
                                                            conditional,
                                                            rowIndex,
                                                        )
                                                    "
                                                    class="ml-4 border-l-2 pl-4"
                                                >
                                                    <Label class="text-sm font-medium">
                                                        {{ conditional.question }}
                                                        <Badge
                                                            v-if="conditional.required"
                                                            variant="destructive"
                                                            class="ml-2"
                                                        >
                                                            *
                                                        </Badge>
                                                    </Label>
                                                    <!-- Répéter les mêmes types d'inputs pour les conditionnelles -->
                                                    <Input
                                                        v-if="
                                                            conditional.type === 'text' ||
                                                            conditional.type === 'email' ||
                                                            conditional.type === 'date'
                                                        "
                                                        :model-value="
                                                            getResponseValue(rowIndex, conditional.id)
                                                        "
                                                        :type="
                                                            conditional.type === 'email'
                                                                ? 'email'
                                                                : conditional.type === 'date'
                                                                  ? 'date'
                                                                  : 'text'
                                                        "
                                                        :required="conditional.required"
                                                        class="mt-1"
                                                        @update:model-value="
                                                            setResponseValue(
                                                                rowIndex,
                                                                conditional.id,
                                                                $event,
                                                            )
                                                        "
                                                    />
                                                    <!-- Ajouter les autres types si nécessaire -->
                                                </div>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            @click="removeRow(rowIndex)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div v-if="rows.length === 0" class="py-8 text-center text-muted-foreground">
                        Aucune ligne. Cliquez sur "Ajouter une ligne" pour commencer.
                    </div>
                </CardContent>
            </Card>

            <!-- Actions -->
            <div class="flex justify-end gap-4">
                <Button variant="outline" as-child>
                    <Link :href="rapports.corrections().url">Annuler</Link>
                </Button>
                <Button @click="submit" :disabled="form.processing">
                    <Save class="mr-2 h-4 w-4" />
                    {{ form.processing ? 'Soumission...' : 'Soumettre la correction' }}
                </Button>
            </div>

            <InputError v-if="form.errors.responses" :message="form.errors.responses" />
        </div>
    </AppLayout>
</template>
