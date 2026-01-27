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

type Props = {
    questionnaire: Questionnaire;
};

const props = defineProps<Props>();

type RowData = {
    rowIdentifier: string;
    responses: Record<number, any>; // question_id => response value
};

const rows = ref<RowData[]>([]);
const pasteText = ref('');
const showPasteHelper = ref(false);

const form = useForm({
    questionnaire_id: props.questionnaire.id,
    responses: [] as Array<{
        question_id: number;
        row_identifier: string | null;
        response: any;
    }>,
});

const breadcrumbs: BreadcrumbItem[] = [
    { label: 'Dashboard', href: dashboard() },
    { label: 'Rapport', href: rapports.index().url },
    { label: 'Faire un rapport', href: rapports.create().url },
    { label: props.questionnaire.title, href: '#' },
];

// Questions triées par ordre, sans les conditionnelles
const visibleQuestions = computed(() => {
    return props.questionnaire.questions
        .filter((q) => !q.conditional_question_id)
        .sort((a, b) => a.order - b.order);
});

// Questions conditionnelles groupées par question parente
const conditionalQuestions = computed(() => {
    const map = new Map<number, Question[]>();
    props.questionnaire.questions
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

    const parentQuestion = props.questionnaire.questions.find(
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
    const parentQuestion = props.questionnaire.questions.find((q) => q.id === questionId);
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

    // Générer plusieurs exemples réalistes selon le type de question
    const generateExampleValue = (question: Question, exampleIndex: number): string => {
        if (question.type === 'text') {
            const examples = ['Jean Dupont', 'Marie Martin', 'Pierre Durand'];
            return examples[exampleIndex % examples.length];
        } else if (question.type === 'email') {
            const examples = ['jean.dupont@example.com', 'marie.martin@example.com', 'pierre.durand@example.com'];
            return examples[exampleIndex % examples.length];
        } else if (question.type === 'textarea') {
            const examples = ['Description détaillée de la situation', 'Commentaire important à noter', 'Remarque pertinente'];
            return `"${examples[exampleIndex % examples.length]}"`;
        } else if (question.type === 'number') {
            const examples = ['150', '2500', '99.5'];
            return examples[exampleIndex % examples.length];
        } else if (question.type === 'date') {
            const examples = ['2026-01-15', '2026-02-20', '2026-03-10'];
            return examples[exampleIndex % examples.length];
        } else if (question.type === 'select' || question.type === 'radio') {
            // Utiliser le numéro de l'option (1, 2, 3, etc.)
            return String((exampleIndex % (question.options?.length || 3)) + 1);
        } else if (question.type === 'checkbox' || question.type === 'selectmulti') {
            // Générer des combinaisons différentes
            const combinations = ['(1,2)', '(2,3)', '(1,3)'];
            return combinations[exampleIndex % combinations.length];
        }
        return 'valeur';
    };

    // Générer 3 lignes d'exemple
    let examples = '';
    for (let i = 0; i < 3; i++) {
        if (i > 0) examples += '\n';
        visibleQuestions.value.forEach((q, index) => {
            if (index > 0) examples += ',';
            examples += generateExampleValue(q, i);
        });
        examples += ';';
    }

    return `Format attendu (séparer les lignes par un point-virgule ";"):\n${formatExample}\n\nExemples de données:\n${examples}\n\nInstructions:\n- Chaque ligne représente une entrée de données\n- Les valeurs sont séparées par des virgules\n- Les lignes sont séparées par des points-virgules\n- Pour les sélections multiples, utilisez le format (1,2,3)\n- Pour les textes longs, vous pouvez utiliser des guillemets "texte"\n- Les dates doivent être au format AAAA-MM-JJ`;
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
        props.questionnaire.questions.forEach((question) => {
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

    form.post(rapports.store().url, {
        onSuccess: () => {
            router.visit(rapports.myReports().url);
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Remplir: ${questionnaire.title}`" />

        <div class="container mx-auto py-6 space-y-6">
            <!-- En-tête -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="rapports.create().url">
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

            <!-- Instructions et format copier-coller -->
            <Card>
                <CardHeader>
                    <CardTitle>Instructions</CardTitle>
                    <CardDescription>
                        Remplissez le tableau ci-dessous ligne par ligne, ou utilisez le mode copier-coller pour importer plusieurs lignes à la fois depuis Excel ou un autre tableur
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="rounded-lg bg-muted/50 p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium mb-1">Colonnes attendues (dans cet ordre) :</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ visibleQuestions.map((q, index) => `${index + 1}. ${q.question}${q.required ? ' *' : ''}`).join(' • ') }}
                                </p>
                            </div>
                            <Button variant="outline" size="sm" @click="copyFormatExample">
                                <Copy class="mr-2 h-4 w-4" />
                                Copier le format
                            </Button>
                        </div>
                    </div>

                    <div v-if="showPasteHelper" class="space-y-3">
                        <div>
                            <Label class="text-sm font-medium">Coller vos données ici</Label>
                            <p class="text-xs text-muted-foreground mt-1 mb-2">
                                Format : chaque ligne = une entrée, séparée par un point-virgule (;). Les valeurs sont séparées par des virgules (,).
                            </p>
                            <Textarea
                                v-model="pasteText"
                                placeholder="Exemple: valeur1,valeur2,valeur3;valeur4,valeur5,valeur6;"
                                :rows="6"
                                class="font-mono text-sm"
                            />
                        </div>
                        <div class="rounded-lg border border-dashed p-3 bg-muted/30">
                            <p class="text-xs font-medium mb-1">💡 Conseils :</p>
                            <ul class="text-xs text-muted-foreground space-y-1 list-disc list-inside">
                                <li>Pour les sélections multiples, utilisez le format <code class="px-1 py-0.5 bg-background rounded">(1,2,3)</code></li>
                                <li>Pour les textes avec virgules, utilisez des guillemets <code class="px-1 py-0.5 bg-background rounded">"texte, avec virgule"</code></li>
                                <li>Les dates doivent être au format <code class="px-1 py-0.5 bg-background rounded">AAAA-MM-JJ</code> (ex: 2026-01-27)</li>
                                <li>Pour les listes déroulantes, utilisez le numéro de l'option (1, 2, 3...)</li>
                            </ul>
                        </div>
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
                    <Link :href="rapports.create().url">Annuler</Link>
                </Button>
                <Button @click="submit" :disabled="form.processing">
                    <Save class="mr-2 h-4 w-4" />
                    {{ form.processing ? 'Soumission...' : 'Soumettre le rapport' }}
                </Button>
            </div>

            <InputError v-if="form.errors.responses" :message="form.errors.responses" />
        </div>
    </AppLayout>
</template>
