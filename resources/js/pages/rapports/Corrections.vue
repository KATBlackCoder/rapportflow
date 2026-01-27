<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import { ArrowLeft, Edit, FileText } from 'lucide-vue-next';

type Correction = {
    questionnaire_id: number;
    row_identifier: string | null;
    status: string;
    submitted_at: string | null;
    correction_reason: string | null;
    reviewed_at: string | null;
    questionnaire?: {
        id: number;
        title: string;
    };
};

type Props = {
    corrections: PaginatedData<Correction>;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { label: 'Dashboard', href: dashboard() },
    { label: 'Rapport', href: rapports.index().url },
    { label: 'Corriger un rapport', href: rapports.corrections().url },
];

const getStatusLabel = (status: string): string => {
    const labels: Record<string, string> = {
        returned_for_correction: 'Renvoyé pour correction',
    };

    return labels[status] || status;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Corriger un rapport" />

        <div class="container mx-auto py-6">
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="rapports.index().url">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold">Corriger un rapport</h1>
                        <p class="text-sm text-muted-foreground">
                            Corrigez les rapports renvoyés pour correction
                        </p>
                    </div>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Edit class="h-5 w-5" />
                        Rapports à corriger
                    </CardTitle>
                    <CardDescription>
                        Liste des rapports renvoyés pour correction. Vous pouvez les modifier ici.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Questionnaire</TableHead>
                                <TableHead>Identifiant ligne</TableHead>
                                <TableHead>Raison de correction</TableHead>
                                <TableHead>Date de renvoi</TableHead>
                                <TableHead>Date de soumission initiale</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableEmpty
                                v-if="corrections.data.length === 0"
                                :colspan="6"
                                message="Aucun rapport à corriger"
                            />
                            <TableRow
                                v-for="(correction, index) in corrections.data"
                                :key="index"
                            >
                                <TableCell class="font-medium">
                                    {{ correction.questionnaire?.title || 'N/A' }}
                                </TableCell>
                                <TableCell>
                                    {{ correction.row_identifier || 'N/A' }}
                                </TableCell>
                                <TableCell>
                                    <span class="line-clamp-2">
                                        {{ correction.correction_reason || 'N/A' }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    {{
                                        correction.reviewed_at
                                            ? new Date(correction.reviewed_at).toLocaleDateString(
                                                  'fr-FR',
                                              )
                                            : 'N/A'
                                    }}
                                </TableCell>
                                <TableCell>
                                    {{
                                        correction.submitted_at
                                            ? new Date(correction.submitted_at).toLocaleDateString(
                                                  'fr-FR',
                                              )
                                            : 'N/A'
                                    }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button as-child>
                                        <Link
                                            :href="
                                                rapports.showCorrection({
                                                    response: correction.questionnaire_id,
                                                }).url
                                            "
                                        >
                                            <Edit class="mr-2 h-4 w-4" />
                                            Corriger
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Pagination -->
                    <div
                        v-if="corrections.links && corrections.links.length > 3"
                        class="mt-4 flex items-center justify-between"
                    >
                        <div class="text-sm text-muted-foreground">
                            Affichage de {{ corrections.from }} à {{ corrections.to }} sur
                            {{ corrections.total }} résultats
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-for="link in corrections.links"
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
