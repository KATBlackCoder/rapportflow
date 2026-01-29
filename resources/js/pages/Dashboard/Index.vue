<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    BarChart3,
    ClipboardList,
    Download,
    FileText,
    Users,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import employees from '@/routes/employees';
import questionnaires from '@/routes/questionnaires';
import rapports from '@/routes/rapports';
import { type BreadcrumbItem } from '@/types';
import MetricCard from './MetricCard.vue';
import QuickActions from './QuickActions.vue';
import RecentActivityCard from './RecentActivityCard.vue';
import StatCard from './StatCard.vue';
import type { DashboardProps } from './types';
import { useDashboardPosition } from './useDashboardPosition';
import { formatDate } from './utils';

withDefaults(defineProps<DashboardProps>(), {
    stats: () => ({}),
    recentReports: () => [],
    pendingCorrections: () => [],
    lastReport: null,
    availableQuestionnairesCount: 0,
});

const {
    isEmployer,
    isSuperviseur,
    isEmployerOrSuperviseur,
    isChefSuperviseur,
    isManager,
    isChefOrManager,
} = useDashboardPosition();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <QuickActions
                v-if="isEmployerOrSuperviseur"
                :show-supervisor-actions="isSuperviseur"
            />

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <StatCard
                    v-if="isEmployer"
                    title="Rapports à faire"
                    description="Questionnaires disponibles (ciblant les employés) non encore remplis récemment"
                    :icon="ClipboardList"
                >
                    <p class="text-3xl font-bold">
                        {{ availableQuestionnairesCount ?? 0 }}
                    </p>
                    <template #footer>
                        <Button as-child>
                            <Link :href="rapports.create().url">
                                Faire un rapport
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    </template>
                </StatCard>

                <MetricCard
                    v-if="isEmployer"
                    label="Questionnaires soumis (total)"
                    :value="stats.myReportsCount ?? 0"
                    :icon="FileText"
                />
                <MetricCard
                    v-if="isEmployer"
                    label="Soumissions (30 derniers jours)"
                    :value="stats.mySubmissionsLast30DaysCount ?? 0"
                    :icon="BarChart3"
                />
                <MetricCard
                    v-if="isEmployer"
                    label="Demandés en correction"
                    :value="stats.pendingCorrectionsCount ?? 0"
                    :icon="AlertCircle"
                />

                <StatCard
                    v-if="isEmployerOrSuperviseur"
                    title="Mes rapports"
                    description="Dernier rapport soumis"
                    :icon="FileText"
                >
                    <template v-if="lastReport">
                        <p class="font-medium">{{ lastReport.questionnaire_title }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ formatDate(lastReport.submitted_at) }}
                        </p>
                    </template>
                    <p
                        v-else
                        class="text-muted-foreground"
                    >
                        Aucun rapport soumis
                    </p>
                    <template #footer>
                        <Button variant="outline" as-child>
                            <Link :href="rapports.myReports().url">Voir mes rapports</Link>
                        </Button>
                    </template>
                </StatCard>

                <StatCard
                    v-if="isEmployerOrSuperviseur"
                    title="À corriger"
                    description="Rapports renvoyés pour correction"
                    :icon="AlertCircle"
                >
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
                    <template #footer>
                        <Button variant="outline" as-child>
                            <Link :href="rapports.corrections().url">
                                Voir les corrections
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    </template>
                </StatCard>

                <MetricCard
                    v-if="isSuperviseur"
                    label="Effectif équipe"
                    :value="stats.supervisedEmployeesCount ?? 0"
                    :icon="Users"
                />
                <MetricCard
                    v-if="isSuperviseur"
                    label="Total soumissions (équipe)"
                    :value="stats.teamTotalSubmissionsCount ?? 0"
                    :icon="BarChart3"
                />
                <MetricCard
                    v-if="isSuperviseur"
                    label="Soumissions (30 derniers jours)"
                    :value="stats.teamReportsCount ?? 0"
                    :icon="FileText"
                />
                <StatCard
                    v-if="isSuperviseur"
                    title="Rapports de l'équipe"
                    description="Analyser les rapports soumis par votre équipe"
                    :icon="BarChart3"
                >
                    <p class="text-sm text-muted-foreground">
                        Accéder à la liste et à l'analyse des rapports.
                    </p>
                    <template #footer>
                        <Button variant="outline" as-child>
                            <Link :href="rapports.analysis().url">
                                Analyser les rapports
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    </template>
                </StatCard>

                <MetricCard
                    v-if="isChefOrManager"
                    label="Total personnel"
                    :value="stats.employeesCount ?? 0"
                    :icon="Users"
                />
                <MetricCard
                    v-if="isChefSuperviseur"
                    label="Superviseurs"
                    :value="stats.supervisorsCount ?? 0"
                    :icon="Users"
                />
                <MetricCard
                    v-if="isChefOrManager"
                    label="Questionnaires publiés"
                    :value="stats.questionnairesCount ?? 0"
                    :icon="ClipboardList"
                />
                <MetricCard
                    v-if="isChefOrManager"
                    label="Total soumissions"
                    :value="stats.totalReportsCount ?? 0"
                    :icon="FileText"
                />
                <MetricCard
                    v-if="isChefOrManager"
                    label="À corriger"
                    :value="stats.pendingCorrectionsCount ?? 0"
                    :icon="AlertCircle"
                />
                <StatCard
                    v-if="isChefOrManager"
                    title="Actions rapports"
                    description="Analyser et exporter"
                    :icon="BarChart3"
                >
                    <template #footer>
                        <div class="flex flex-wrap gap-2">
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
                        </div>
                    </template>
                </StatCard>

                <StatCard
                    v-if="canAccessQuestionnaires"
                    title="Questionnaires"
                    description="Gestion des questionnaires"
                    :icon="ClipboardList"
                >
                    <p class="text-3xl font-bold">
                        {{ stats.questionnairesCount ?? 0 }}
                    </p>
                    <template #footer>
                        <Button variant="outline" as-child>
                            <Link :href="questionnaires.index().url">
                                Voir les questionnaires
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    </template>
                </StatCard>

                <StatCard
                    v-if="canAccessEmployees"
                    title="Employés"
                    description="Gestion des employés"
                    :icon="Users"
                >
                    <template #footer>
                        <Button variant="outline" as-child>
                            <Link :href="employees.index().url">
                                Voir les employés
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    </template>
                </StatCard>
            </div>

            <RecentActivityCard
                v-if="isManager && recentReports.length > 0"
                :reports="recentReports"
            />
        </div>
    </AppLayout>
</template>
