<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
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
// Textarea component will be added via shadcn-vue
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft, Plus, Trash2 } from 'lucide-vue-next';

type Question = {
    type: string;
    question: string;
    required: boolean;
    order: number;
    options?: string[] | null;
    conditional_question_id?: number | null;
    conditional_value?: string | null;
};

const props = defineProps();

const questions = ref<Question[]>([]);
const questionOptionsText = ref<Record<number, string>>({});

const form = useForm({
    title: '',
    description: '',
    status: 'published',
    target_type: 'employees',
    questions: [] as Question[],
});

const addQuestion = () => {
    const index = questions.value.length;
    questions.value.push({
        type: 'text',
        question: '',
        required: false,
        order: index,
        options: [] as string[],
        conditional_question_id: null,
        conditional_value: null,
    });
    questionOptionsText.value[index] = '';
};

const removeQuestion = (index: number) => {
    questions.value.splice(index, 1);
    delete questionOptionsText.value[index];
    // Réindexer les options text
    const newOptionsText: Record<number, string> = {};
    questions.value.forEach((q, i) => {
        q.order = i;
        if (questionOptionsText.value[i] !== undefined) {
            newOptionsText[i] = questionOptionsText.value[i];
        }
    });
    questionOptionsText.value = newOptionsText;
    questions.value.forEach((q, i) => {
        q.order = i;
    });
};

const moveQuestion = (index: number, direction: 'up' | 'down') => {
    if (direction === 'up' && index > 0) {
        [questions.value[index], questions.value[index - 1]] = [
            questions.value[index - 1],
            questions.value[index],
        ];
        questions.value.forEach((q, i) => {
            q.order = i;
        });
    } else if (direction === 'down' && index < questions.value.length - 1) {
        [questions.value[index], questions.value[index + 1]] = [
            questions.value[index + 1],
            questions.value[index],
        ];
        questions.value.forEach((q, i) => {
            q.order = i;
        });
    }
};

const submit = () => {
    form.questions = questions.value.map((q) => ({
        type: q.type,
        question: q.question,
        required: q.required,
        order: q.order,
        options: q.options && q.options.length > 0 ? q.options : null,
        conditional_question_index: q.conditional_question_id !== null && q.conditional_question_id !== undefined 
            ? q.conditional_question_id 
            : null,
        conditional_question_id: null, // Sera résolu côté serveur
        conditional_value: q.conditional_value || null,
    }));
    form.post('/questionnaires', {
        onSuccess: () => {
            router.visit('/questionnaires');
        },
    });
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Questionnaires',
        href: '/questionnaires',
    },
    {
        title: 'Créer',
    },
];
</script>

<template>
    <Head title="Créer un questionnaire" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Créer un nouveau questionnaire</CardTitle>
                            <CardDescription>
                                Remplissez les informations pour créer un nouveau questionnaire
                            </CardDescription>
                        </div>
                        <Button variant="outline" @click="router.visit('/questionnaires')">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Retour
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <Label for="title">Titre *</Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                required
                                placeholder="Titre du questionnaire"
                            />
                            <InputError :message="form.errors.title" />
                        </div>

                        <div class="space-y-2">
                            <Label for="description">Description</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Description du questionnaire"
                                rows="3"
                                class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="status">Statut *</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="published">Publié</SelectItem>
                                        <SelectItem value="archived">Archivé</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.status" />
                            </div>

                            <div class="space-y-2">
                                <Label for="target_type">Type de destinataires *</Label>
                                <Select v-model="form.target_type">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="employees">Employés</SelectItem>
                                        <SelectItem value="supervisors">Superviseurs</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.target_type" />
                            </div>
                        </div>

                        <Separator />

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold">Questions</h3>
                                    <p class="text-sm text-muted-foreground">
                                        Ajoutez les questions pour ce questionnaire
                                    </p>
                                </div>
                                <Button type="button" @click="addQuestion">
                                    <Plus class="mr-2 h-4 w-4" />
                                    Ajouter une question
                                </Button>
                            </div>

                            <div v-if="questions.length === 0" class="rounded-lg border border-dashed p-8 text-center">
                                <p class="text-sm text-muted-foreground">
                                    Aucune question ajoutée. Cliquez sur "Ajouter une question" pour commencer.
                                </p>
                            </div>

                            <div
                                v-for="(question, index) in questions"
                                :key="index"
                                class="rounded-lg border p-4 space-y-4"
                            >
                                <div class="flex items-center justify-between">
                                    <h4 class="font-medium">Question {{ index + 1 }}</h4>
                                    <div class="flex items-center gap-2">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            :disabled="index === 0"
                                            @click="moveQuestion(index, 'up')"
                                        >
                                            ↑
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            :disabled="index === questions.length - 1"
                                            @click="moveQuestion(index, 'down')"
                                        >
                                            ↓
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            @click="removeQuestion(index)"
                                        >
                                            <Trash2 class="h-4 w-4 text-destructive" />
                                        </Button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <Label>Type de question *</Label>
                                        <Select v-model="question.type">
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="text">Texte</SelectItem>
                                                <SelectItem value="textarea">Zone de texte</SelectItem>
                                                <SelectItem value="radio">Choix unique</SelectItem>
                                                <SelectItem value="checkbox">Choix multiples</SelectItem>
                                                <SelectItem value="select">Liste déroulante</SelectItem>
                                                <SelectItem value="number">Nombre</SelectItem>
                                                <SelectItem value="date">Date</SelectItem>
                                                <SelectItem value="email">Email</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="space-y-2">
                                        <Label>
                                            <input
                                                v-model="question.required"
                                                type="checkbox"
                                                class="mr-2"
                                            />
                                            Question obligatoire
                                        </Label>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label>Texte de la question *</Label>
                                    <Input
                                        v-model="question.question"
                                        required
                                        placeholder="Entrez la question"
                                    />
                                </div>

                                <div
                                    v-if="['radio', 'checkbox', 'select'].includes(question.type)"
                                    class="space-y-2"
                                >
                                    <Label>Options (une par ligne)</Label>
                                    <textarea
                                        :value="questionOptionsText[index] || (Array.isArray(question.options) ? question.options.join('\n') : '')"
                                        @input="(e) => { 
                                            const value = (e.target as HTMLTextAreaElement).value;
                                            questionOptionsText[index] = value;
                                            const lines = value.split('\n');
                                            question.options = lines.map(o => o.trim()).filter(o => o.length > 0);
                                        }"
                                        placeholder="Option 1&#10;Option 2&#10;Option 3"
                                        rows="4"
                                        class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                        style="white-space: pre-wrap;"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Séparez les options par des retours à la ligne
                                    </p>
                                </div>

                                <div
                                    v-if="index > 0"
                                    class="space-y-2 rounded-lg border border-dashed p-4"
                                >
                                    <Label class="text-sm font-medium">Question conditionnelle (optionnel)</Label>
                                    <p class="text-xs text-muted-foreground mb-2">
                                        Cette question s'affichera uniquement si la réponse à une question précédente correspond à une valeur spécifique.
                                    </p>
                                    
                                    <div class="space-y-2">
                                        <Label>Question parente</Label>
                                        <Select
                                            :model-value="question.conditional_question_id !== null && question.conditional_question_id !== undefined ? String(question.conditional_question_id) : null"
                                            @update:model-value="(val) => { 
                                                question.conditional_question_id = val && val !== 'none' ? Number(val) : null;
                                                if (!val || val === 'none') question.conditional_value = null;
                                            }"
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Sélectionner une question parente" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">Aucune (question toujours visible)</SelectItem>
                                                <SelectItem
                                                    v-for="(prevQuestion, prevIndex) in questions.slice(0, index)"
                                                    :key="prevIndex"
                                                    :value="String(prevIndex)"
                                                    :disabled="!['select', 'checkbox', 'radio'].includes(prevQuestion.type)"
                                                >
                                                    Question {{ prevIndex + 1 }}: {{ prevQuestion.question.length > 50 ? prevQuestion.question.substring(0, 50) + '...' : prevQuestion.question }}
                                                    <span v-if="!['select', 'checkbox', 'radio'].includes(prevQuestion.type)" class="text-muted-foreground text-xs ml-2">
                                                        (non disponible - doit être select/checkbox/radio)
                                                    </span>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p class="text-xs text-muted-foreground">
                                            Seules les questions de type select, checkbox ou radio peuvent être utilisées comme question parente
                                        </p>
                                    </div>

                                    <div
                                        v-if="question.conditional_question_id !== null && question.conditional_question_id !== undefined"
                                        class="space-y-2"
                                    >
                                        <Label>Valeur conditionnelle *</Label>
                                        <Select
                                            :model-value="question.conditional_value || null"
                                            @update:model-value="(val) => { question.conditional_value = val ? String(val) : null; }"
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Sélectionner une valeur" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="(option, optIndex) in (questions[question.conditional_question_id]?.options || [])"
                                                    :key="optIndex"
                                                    :value="option"
                                                >
                                                    {{ option }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p class="text-xs text-muted-foreground">
                                            Sélectionnez la valeur de la question parente qui déclenchera l'affichage de cette question
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <InputError :message="form.errors.questions" />

                        <div class="flex justify-end gap-4">
                            <Button
                                type="button"
                                variant="outline"
                                @click="router.visit('/questionnaires')"
                            >
                                Annuler
                            </Button>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Enregistrement...' : 'Créer le questionnaire' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
