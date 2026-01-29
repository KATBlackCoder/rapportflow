<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ClipboardList, FileText, LayoutGrid, Moon, Sun, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useAppearance } from '@/composables/useAppearance';
import { dashboard } from '@/routes';
import employees from '@/routes/employees';
import questionnaires from '@/routes/questionnaires';
import rapports from '@/routes/rapports';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const page = usePage();
const user = page.props.auth.user;

const canViewEmployees = computed(() => {
    if (!user?.employee) {
        return false;
    }

    const position = user.employee.position;
    return position === 'manager' || position === 'chef_superviseur';
});

const canViewQuestionnaires = computed(() => {
    if (!user?.employee) {
        return false;
    }

    const position = user.employee.position;
    return position === 'manager' || position === 'chef_superviseur';
});

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (canViewEmployees.value) {
        items.push({
            title: 'Employees',
            href: employees.index().url,
            icon: Users,
        });
    }

    // Seuls les managers et chefs superviseurs peuvent voir les questionnaires
    if (canViewQuestionnaires.value) {
        items.push({
            title: 'Questionnaires',
            href: questionnaires.index().url,
            icon: ClipboardList,
        });
    }

    // Tous les utilisateurs authentifiés peuvent accéder aux rapports
    items.push({
        title: 'Rapport',
        href: rapports.index().url,
        icon: FileText,
    });

    return items;
});

const footerNavItems: NavItem[] = [];

const { resolvedAppearance, updateAppearance } = useAppearance();

function toggleTheme() {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <SidebarMenu>
                <SidebarMenuItem>
                    <TooltipProvider :delay-duration="0">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <SidebarMenuButton
                                    class="w-full cursor-pointer"
                                    @click="toggleTheme"
                                >
                                    <Moon
                                        v-if="resolvedAppearance === 'light'"
                                        class="size-5 shrink-0"
                                    />
                                    <Sun
                                        v-else
                                        class="size-5 shrink-0"
                                    />
                                    <span class="truncate group-data-[collapsible=icon]:hidden">
                                        {{ resolvedAppearance === 'dark' ? 'Mode clair' : 'Mode sombre' }}
                                    </span>
                                </SidebarMenuButton>
                            </TooltipTrigger>
                            <TooltipContent side="right">
                                <p>{{ resolvedAppearance === 'dark' ? 'Mode clair' : 'Mode sombre' }}</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </SidebarMenuItem>
            </SidebarMenu>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
