<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft, Edit, Trash2 } from 'lucide-vue-next';

type Question = {
    id: number;
    type: string;
    question: string;
    required: boolean;
    order: number;
    options?: string[] | null;
    conditional_question_id?: number | null;
    conditional_value?: string | null;
};

type Questionnaire = {
    id: number;
    title: string;
    description: string | null;
    status: string;
    target_type: string;
    created_at: string;
    creator: {
        id: number;
        name: string;
    };
    questions: Question[];
};

type Props = {
    questionnaire: Questionnaire;
};

const props = defineProps<Props>();

const deleteDialogOpen = ref(false);
const deleteForm = useForm({});

const openDeleteDialog = () => {
    deleteDialogOpen.value = true;
};

const closeDeleteDialog = () => {
    deleteDialogOpen.value = false;
};

const confirmDelete = () => {
    deleteForm.delete(`/questionnaires/${props.questionnaire.id}`, {
        onSuccess: () => {
            closeDeleteDialog();
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
        title: props.questionnaire.title,
    },
];

const getStatusBadgeVariant = (
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        published: 'default',
        archived: 'secondary',
    };

    return variants[status] || 'secondary';
};

const getStatusLabel = (status: string): string => {
    const labels: Record<string, string> = {
        published: 'Publié',
        archived: 'Archivé',
    };

    return labels[status] || status;
};

const getTargetTypeLabel = (targetType: string): string => {
    const labels: Record<string, string> = {
        employees: 'Employés',
        supervisors: 'Superviseurs',
    };

    return labels[targetType] || targetType;
};

const getQuestionTypeLabel = (type: string): string => {
    const labels: Record<string, string> = {
        text: 'Texte',
        textarea: 'Zone de texte',
        radio: 'Choix unique',
        checkbox: 'Choix multiples',
        select: 'Liste déroulante',
        number: 'Nombre',
        date: 'Date',
        email: 'Email',
    };

    return labels[type] || type;
};
</script>

<template>
    <Head :title="questionnaire.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>{{ questionnaire.title }}</CardTitle>
                            <CardDescription>
                                Détails du questionnaire
                            </CardDescription>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                @click="router.visit(`/questionnaires/${questionnaire.id}/edit`)"
                            >
                                <Edit class="mr-2 h-4 w-4" />
                                Modifier
                            </Button>
                            <Button
                                variant="destructive"
                                @click="openDeleteDialog"
                            >
                                <Trash2 class="mr-2 h-4 w-4" />
                                Supprimer
                            </Button>
                            <Button variant="outline" @click="router.visit('/questionnaires')">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Retour
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label class="text-sm font-medium">Statut</Label>
                            <div>
                                <Badge :variant="getStatusBadgeVariant(questionnaire.status)">
                                    {{ getStatusLabel(questionnaire.status) }}
                                </Badge>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label class="text-sm font-medium">Type de destinataires</Label>
                            <div>
                                <Badge variant="outline">
                                    {{ getTargetTypeLabel(questionnaire.target_type) }}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    <div v-if="questionnaire.description" class="space-y-2">
                        <Label class="text-sm font-medium">Description</Label>
                        <p class="text-sm text-muted-foreground">
                            {{ questionnaire.description }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label class="text-sm font-medium">Créateur</Label>
                            <p class="text-sm text-muted-foreground">
                                {{ questionnaire.creator.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label class="text-sm font-medium">Date de création</Label>
                            <p class="text-sm text-muted-foreground">
                                {{ new Date(questionnaire.created_at).toLocaleDateString('fr-FR') }}
                            </p>
                        </div>
                    </div>

                    <Separator />

                    <div class="space-y-4">
                        <div>
                            <h3 class="text-lg font-semibold">Questions</h3>
                            <p class="text-sm text-muted-foreground">
                                {{ questionnaire.questions.length }} question(s) dans ce questionnaire
                            </p>
                        </div>

                        <div
                            v-for="(question, index) in questionnaire.questions"
                            :key="question.id"
                            class="rounded-lg border p-4 space-y-2"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Badge variant="outline">Question {{ index + 1 }}</Badge>
                                    <Badge variant="secondary">
                                        {{ getQuestionTypeLabel(question.type) }}
                                    </Badge>
                                    <Badge
                                        v-if="question.required"
                                        variant="default"
                                    >
                                        Obligatoire
                                    </Badge>
                                </div>
                            </div>

                            <p class="font-medium">{{ question.question }}</p>

                            <div
                                v-if="question.options && question.options.length > 0"
                                class="space-y-1"
                            >
                                <p class="text-sm font-medium text-muted-foreground">Options :</p>
                                <ul class="list-disc list-inside space-y-1">
                                    <li
                                        v-for="(option, optIndex) in question.options"
                                        :key="optIndex"
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{ option }}
                                    </li>
                                </ul>
                            </div>

                            <div
                                v-if="question.conditional_question_id"
                                class="rounded bg-muted p-2 text-sm text-muted-foreground"
                            >
                                <p>
                                    <strong>Question conditionnelle :</strong> S'affiche si la réponse à la question
                                    {{ question.conditional_question_id }} est "{{ question.conditional_value }}"
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Dialog :open="deleteDialogOpen" @update:open="closeDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Supprimer le questionnaire</DialogTitle>
                    <DialogDescription>
                        Êtes-vous sûr de vouloir supprimer
                        <strong>{{ questionnaire.title }}</strong>
                        ? Cette action est irréversible et supprimera également toutes les questions associées.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="closeDeleteDialog">
                        Annuler
                    </Button>
                    <Button
                        variant="destructive"
                        :disabled="deleteForm.processing"
                        @click="confirmDelete"
                    >
                        {{ deleteForm.processing ? 'Suppression...' : 'Supprimer' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
