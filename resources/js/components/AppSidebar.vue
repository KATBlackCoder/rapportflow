<script setup lang="ts">
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
import { dashboard } from '@/routes';
import employees from '@/routes/employees';
import questionnaires from '@/routes/questionnaires';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ClipboardList, LayoutGrid, Users } from 'lucide-vue-next';
import { computed } from 'vue';
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

    // Tous les utilisateurs authentifiés peuvent voir les questionnaires
    items.push({
        title: 'Questionnaires',
        href: questionnaires.index().url,
        icon: ClipboardList,
    });

    return items;
});

const footerNavItems: NavItem[] = [];
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
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
