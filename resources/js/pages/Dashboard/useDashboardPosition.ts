import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { AppPageProps } from '@/types';
import type { DashboardProps } from './types';

export type DashboardPosition = 'employer' | 'superviseur' | 'chef_superviseur' | 'manager';

export function useDashboardPosition() {
    const page = usePage<AppPageProps<DashboardProps>>();
    const position = computed<DashboardPosition>(
        () =>
            (page.props.auth?.user?.employee?.position as DashboardPosition) ?? 'employer',
    );

    return {
        position,
        isEmployer: computed(() => position.value === 'employer'),
        isSuperviseur: computed(() => position.value === 'superviseur'),
        isChefSuperviseur: computed(() => position.value === 'chef_superviseur'),
        isManager: computed(() => position.value === 'manager'),
        isEmployerOrSuperviseur: computed(
            () => position.value === 'employer' || position.value === 'superviseur',
        ),
        isChefOrManager: computed(
            () =>
                position.value === 'chef_superviseur' || position.value === 'manager',
        ),
    };
}
