<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    BarChart3,
    ClipboardList,
    Download,
    FileCheck,
    FileText,
    Users,
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import employees from '@/routes/employees';
import questionnaires from '@/routes/questionnaires';
import rapports from '@/routes/rapports';
import { type AppPageProps, type BreadcrumbItem } from '@/types';
import type { DashboardProps } from './types';

withDefaults(defineProps<DashboardProps>(), {
    stats: () => ({}),
    recentReports: () => [],
    pendingCorrections: () => [],
    lastReport: null,
    availableQuestionnairesCount: 0,
});

const page = usePage<AppPageProps<DashboardProps>>();
const position = page.props.auth?.user?.employee?.position ?? 'employer';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

function formatDate(iso: string | null): string {
    if (!iso) return '—';
    const d = new Date(iso);
    return new Intl.DateTimeFormat('fr-FR', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(d);
}

const isEmployer = position === 'employer';
const isSuperviseur = position === 'superviseur';
const isChefSuperviseur = position === 'chef_superviseur';
const isManager = position === 'manager';
const isEmployerOrSuperviseur = isEmployer || isSuperviseur;
const isChefOrManager = isChefSuperviseur || isManager;
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <!-- Actions rapides -->
            <div
                v-if="isEmployerOrSuperviseur"
                class="flex flex-wrap items-center gap-2"
            >
                <Button as-child>
                    <Link :href="rapports.create().url">
                        <ClipboardList class="mr-2 h-4 w-4" />
                        Faire un rapport
                    </Link>
                </Button>
                <Button
                    v-if="isSuperviseur"
                    variant="outline"
                    as-child
                >
                    <Link :href="rapports.corrections().url">
                        <FileCheck class="mr-2 h-4 w-4" />
                        Corriger un rapport
                    </Link>
                </Button>
                <Button
                    v-if="isSuperviseur"
                    variant="outline"
                    as-child
                >
                    <Link :href="rapports.analysis().url">
                        <BarChart3 class="mr-2 h-4 w-4" />
                        Analyser les rapports
                    </Link>
                </Button>
            </div>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                <!-- Employé : Rapports à faire -->
                <Card v-if="isEmployer">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <ClipboardList class="h-5 w-5" />
                            Rapports à faire
                        </CardTitle>
                        <CardDescription>
                            Questionnaires disponibles (ciblant les employés) non encore remplis récemment
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold">
                            {{ availableQuestionnairesCount ?? 0 }}
                        </p>
                    </CardContent>
                    <CardFooter>
                        <Button as-child>
                            <Link :href="rapports.create().url">
                                Faire un rapport
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    </CardFooter>
                </Card>

                <!-- Employé / Superviseur : Mes rapports -->
                <Card v-if="isEmployerOrSuperviseur">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileText class="h-5 w-5" />
                            Mes rapports
                        </CardTitle>
                        <CardDescription>
                            Dernier rapport soumis
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <template v-if="lastReport">
                            <p class="font-medium">{{ lastReport.questionnaire_title }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ formatDate(lastReport.submitted_at) }}
                            </p>
                        </template>
                        <template v-else>
                            <p class="text-muted-foreground">Aucun rapport soumis</p>
                        </template>
                    </CardContent>
                    <CardFooter>
                        <Button variant="outline" as-child>
                            <Link :href="rapports.myReports().url">Voir mes rapports</Link>
                        </Button>
                    </CardFooter>
                </Card>

                <!-- Employé / Superviseur : À corriger -->
                <Card v-if="isEmployerOrSuperviseur">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <AlertCircle class="h-5 w-5" />
                            À corriger
                        </CardTitle>
                        <CardDescription>
                            Rapports renvoyés pour correction
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold">
                            {{ stats.pendingCorrectionsCount ?? 0 }}
                        </p>
                        <ul
                            v-if="pendingCorrections.length > 0"
                            class="mt-2 space-y-1 text-sm"
                        >
                            <li
                                v-for="item in pendingCorrections.slice(0, 3)"
                                :key="item.response_id"
                            >
                                <Link
                                    :href="rapports.showCorrection({ response: item.response_id }).url"
                                    class="text-primary hover:underline"
                                >
                                    {{ item.questionnaire_title }}
                                </Link>
                            </li>
                        </ul>
                    </CardContent>
                    <CardFooter>
                        <Button variant="outline" as-child>
                            <Link :href="rapports.corrections().url">
                                Voir les corrections
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    </CardFooter>
                </Card>

                <!-- Superviseur : Mon équipe -->
                <Card v-if="isSuperviseur">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Mon équipe
                        </CardTitle>
                        <CardDescription>
                            Employés supervisés
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold">
                            {{ stats.supervisedEmployeesCount ?? 0 }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Superviseur : Rapports de l'équipe -->
                <Card v-if="isSuperviseur">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <BarChart3 class="h-5 w-5" />
                            Rapports de l'équipe
                        </CardTitle>
                        <CardDescription>
                            Rapports soumis sur les 30 derniers jours
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold">
                            {{ stats.teamReportsCount ?? 0 }}
                        </p>
                    </CardContent>
                    <CardFooter>
                        <Button variant="outline" as-child>
                            <Link :href="rapports.analysis().url">
                                Analyser les rapports
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    </CardFooter>
                </Card>

                <!-- Chef superviseur / Manager : Vue équipe ou globale -->
                <Card v-if="isChefOrManager">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            {{ isManager ? 'Vue globale' : 'Vue équipe / département' }}
                        </CardTitle>
                        <CardDescription>
                            Indicateurs agrégés
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Employés</span>
                            <span class="font-medium">{{ stats.employeesCount ?? 0 }}</span>
                        </div>
                        <div
                            v-if="isChefSuperviseur"
                            class="flex justify-between"
                        >
                            <span class="text-muted-foreground">Superviseurs</span>
                            <span class="font-medium">{{ stats.supervisorsCount ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Questionnaires publiés</span>
                            <span class="font-medium">{{ stats.questionnairesCount ?? 0 }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Chef superviseur / Manager : Rapports -->
                <Card v-if="isChefOrManager">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileText class="h-5 w-5" />
                            Rapports
                        </CardTitle>
                        <CardDescription>
                            Total soumis
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold">
                            {{ stats.totalReportsCount ?? 0 }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            À corriger : {{ stats.pendingCorrectionsCount ?? 0 }}
                        </p>
                    </CardContent>
                    <CardFooter class="flex flex-wrap gap-2">
                        <Button variant="outline" as-child>
                            <Link :href="rapports.analysis().url">Analyser les rapports</Link>
                        </Button>
                        <Button
                            v-if="canExportReports"
                            variant="outline"
                            as-child
                        >
                            <Link :href="rapports.export().url">
                                <Download class="mr-2 h-4 w-4" />
                                Export Excel
                            </Link>
                        </Button>
                    </CardFooter>
                </Card>

                <!-- Chef superviseur / Manager : Questionnaires -->
                <Card v-if="canAccessQuestionnaires">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <ClipboardList class="h-5 w-5" />
                            Questionnaires
                        </CardTitle>
                        <CardDescription>
                            Gestion des questionnaires
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold">
                            {{ stats.questionnairesCount ?? 0 }}
                        </p>
                    </CardContent>
                    <CardFooter>
                        <Button variant="outline" as-child>
                            <Link :href="questionnaires.index().url">
                                Voir les questionnaires
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    </CardFooter>
                </Card>

                <!-- Chef superviseur / Manager : Employés -->
                <Card v-if="canAccessEmployees">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Employés
                        </CardTitle>
                        <CardDescription>
                            Gestion des employés
                        </CardDescription>
                    </CardHeader>
                    <CardFooter>
                        <Button variant="outline" as-child>
                            <Link :href="employees.index().url">
                                Voir les employés
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    </CardFooter>
                </Card>
            </div>

            <!-- Activité récente (Manager) -->
            <Card v-if="isManager && recentReports.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <BarChart3 class="h-5 w-5" />
                        Activité récente
                    </CardTitle>
                    <CardDescription>
                        Derniers rapports soumis
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ul class="space-y-3">
                        <li
                            v-for="report in recentReports"
                            :key="report.id"
                            class="flex items-center justify-between border-b border-sidebar-border/70 py-2 last:border-0 dark:border-sidebar-border"
                        >
                            <div>
                                <p class="font-medium">{{ report.questionnaire_title }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ report.respondent_name }}
                                    · {{ formatDate(report.submitted_at) }}
                                </p>
                            </div>
                            <Badge variant="secondary">Soumis</Badge>
                        </li>
                    </ul>
                </CardContent>
                <CardFooter>
                    <Button variant="outline" as-child>
                        <Link :href="rapports.analysis().url">Analyser tous les rapports</Link>
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
