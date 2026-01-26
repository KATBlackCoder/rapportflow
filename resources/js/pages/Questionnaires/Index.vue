<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
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
import { type BreadcrumbItem, type PaginatedData } from '@/types';
import { Eye, MoreHorizontal, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';

type Questionnaire = {
    id: number;
    title: string;
    description: string | null;
    status: string;
    target_type: string;
    created_by: number;
    created_at: string;
    creator: {
        id: number;
        name: string;
    };
    questions_count?: number;
};

type Props = {
    questionnaires: PaginatedData<Questionnaire>;
    filters: {
        search?: string;
        status?: string;
    };
};

const props = defineProps<Props>();

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref<string>(props.filters.status || 'all');
const deleteDialogOpen = ref(false);
const questionnaireToDelete = ref<Questionnaire | null>(null);

const deleteForm = useForm({});

const openDeleteDialog = (questionnaire: Questionnaire) => {
    questionnaireToDelete.value = questionnaire;
    deleteDialogOpen.value = true;
};

const closeDeleteDialog = () => {
    deleteDialogOpen.value = false;
    questionnaireToDelete.value = null;
};

const confirmDelete = () => {
    if (questionnaireToDelete.value) {
        deleteForm.delete(`/questionnaires/${questionnaireToDelete.value.id}`, {
            onSuccess: () => {
                closeDeleteDialog();
            },
        });
    }
};

const search = () => {
    router.get(
        '/questionnaires',
        {
            search: searchQuery.value || undefined,
            status: statusFilter.value === 'all' ? undefined : statusFilter.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Questionnaires',
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
</script>

<template>
    <Head title="Questionnaires" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Liste des questionnaires</CardTitle>
                            <CardDescription>
                                Gestion et visualisation de tous les questionnaires disponibles
                            </CardDescription>
                        </div>
                        <Button @click="router.visit('/questionnaires/create')">
                            <Plus class="mr-2 h-4 w-4" />
                            Créer un questionnaire
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="mb-4 flex gap-4">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Rechercher par titre..."
                                class="pl-9 max-w-sm"
                                @keyup.enter="search"
                            />
                        </div>
                        <Select v-model="statusFilter" @update:model-value="search">
                            <SelectTrigger class="w-[180px]">
                                <SelectValue placeholder="Tous les statuts" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous les statuts</SelectItem>
                                <SelectItem value="published">Publié</SelectItem>
                                <SelectItem value="archived">Archivé</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button @click="search">
                            <Search class="mr-2 h-4 w-4" />
                            Rechercher
                        </Button>
                    </div>

                    <Separator class="mb-4" />

                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Titre</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead>Type de destinataires</TableHead>
                                    <TableHead>Créateur</TableHead>
                                    <TableHead>Date de création</TableHead>
                                    <TableHead class="w-[50px]"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="questionnaires.data.length > 0">
                                    <TableRow
                                        v-for="questionnaire in questionnaires.data"
                                        :key="questionnaire.id"
                                    >
                                        <TableCell class="font-medium">
                                            {{ questionnaire.title }}
                                        </TableCell>
                                        <TableCell class="text-sm text-muted-foreground">
                                            {{ questionnaire.description ? (questionnaire.description.length > 50 ? questionnaire.description.substring(0, 50) + '...' : questionnaire.description) : '-' }}
                                        </TableCell>
                                        <TableCell>
                                            <Badge :variant="getStatusBadgeVariant(questionnaire.status)">
                                                {{ getStatusLabel(questionnaire.status) }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {{ getTargetTypeLabel(questionnaire.target_type) }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-sm text-muted-foreground">
                                            {{ questionnaire.creator.name }}
                                        </TableCell>
                                        <TableCell class="text-sm text-muted-foreground">
                                            {{ new Date(questionnaire.created_at).toLocaleDateString('fr-FR') }}
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="router.visit(`/questionnaires/${questionnaire.id}`)"
                                                >
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="router.visit(`/questionnaires/${questionnaire.id}/edit`)"
                                                >
                                                    <Pencil class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="openDeleteDialog(questionnaire)"
                                                >
                                                    <Trash2 class="h-4 w-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <TableEmpty
                                    v-else
                                    :colspan="7"
                                >
                                    <p class="text-sm text-muted-foreground">
                                        Aucun questionnaire trouvé
                                    </p>
                                </TableEmpty>
                            </TableBody>
                        </Table>
                    </div>

                    <Separator v-if="questionnaires.links && questionnaires.links.length > 3" class="my-4" />

                    <div
                        v-if="questionnaires.links && questionnaires.links.length > 3"
                        class="flex items-center justify-between"
                    >
                        <div class="text-sm text-muted-foreground">
                            Affichage de {{ questionnaires.from }} à {{ questionnaires.to }} sur {{ questionnaires.total }} questionnaires
                        </div>
                        <div class="flex items-center gap-2">
                            <template v-for="link in questionnaires.links" :key="link.label">
                                <Button
                                    v-if="link.url"
                                    :as-child="true"
                                    :variant="link.active ? 'default' : 'outline'"
                                    size="sm"
                                >
                                    <Link :href="link.url">
                                        <span v-html="link.label" />
                                    </Link>
                                </Button>
                                <Button
                                    v-else
                                    :variant="link.active ? 'default' : 'outline'"
                                    :disabled="true"
                                    size="sm"
                                >
                                    <span v-html="link.label" />
                                </Button>
                            </template>
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
                        <strong v-if="questionnaireToDelete">
                            {{ questionnaireToDelete.title }}
                        </strong>
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
