<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
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
import EmployeeFormDialog from '@/components/EmployeeFormDialog.vue';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type PaginatedData } from '@/types';
import { MoreHorizontal, Pencil, Plus, Trash2 } from 'lucide-vue-next';

type Employee = {
    id: number;
    employee_id: string;
    first_name: string;
    last_name: string;
    email: string | null;
    phone: string;
    position: string;
    department: string | null;
    manager_id: number | null;
    salary: number | null;
    hire_date: string | null;
    status: string;
    manager: {
        id: number;
        first_name: string;
        last_name: string;
    } | null;
    user: {
        id: number;
        name: string;
    } | null;
};

type Manager = {
    id: number;
    first_name: string;
    last_name: string;
};

type Props = {
    employees: PaginatedData<Employee>;
    managers: Manager[];
};

const props = defineProps<Props>();

const { getInitials } = useInitials();
const searchQuery = ref('');
const dialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedEmployee = ref<Employee | null>(null);
const employeeToDelete = ref<Employee | null>(null);

const deleteForm = useForm({});

const openCreateDialog = () => {
    selectedEmployee.value = null;
    dialogOpen.value = true;
};

const openEditDialog = (employee: Employee) => {
    selectedEmployee.value = employee;
    dialogOpen.value = true;
};

const openDeleteDialog = (employee: Employee) => {
    employeeToDelete.value = employee;
    deleteDialogOpen.value = true;
};

const closeDialog = () => {
    dialogOpen.value = false;
    selectedEmployee.value = null;
};

const closeDeleteDialog = () => {
    deleteDialogOpen.value = false;
    employeeToDelete.value = null;
};

const confirmDelete = () => {
    if (employeeToDelete.value) {
        deleteForm.delete(`/employees/${employeeToDelete.value.id}`, {
            onSuccess: () => {
                closeDeleteDialog();
            },
        });
    }
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Employees',
    },
];

const filteredEmployees = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.employees.data;
    }

    const query = searchQuery.value.toLowerCase();
    return props.employees.data.filter((employee) => {
        const fullName = `${employee.first_name} ${employee.last_name}`.toLowerCase();
        const email = employee.email?.toLowerCase() || '';
        const phone = employee.phone.toLowerCase();
        const employeeId = employee.employee_id.toLowerCase();
        const department = employee.department?.toLowerCase() || '';

        return (
            fullName.includes(query) ||
            email.includes(query) ||
            phone.includes(query) ||
            employeeId.includes(query) ||
            department.includes(query)
        );
    });
});

const getPositionLabel = (position: string): string => {
    const labels: Record<string, string> = {
        employer: 'Employé',
        superviseur: 'Superviseur',
        chef_superviseur: 'Chef Superviseur',
        manager: 'Manager',
    };

    return labels[position] || position;
};

const getStatusBadgeVariant = (status: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        active: 'default',
        inactive: 'secondary',
        suspended: 'destructive',
        terminated: 'outline',
    };

    return variants[status] || 'secondary';
};

const getStatusLabel = (status: string): string => {
    const labels: Record<string, string> = {
        active: 'Actif',
        inactive: 'Inactif',
        suspended: 'Suspendu',
        terminated: 'Résilié',
    };

    return labels[status] || status;
};
</script>

<template>
    <Head title="Employees" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Liste des employés</CardTitle>
                            <CardDescription>
                                Gestion et visualisation de tous les employés de l'organisation
                            </CardDescription>
                        </div>
                        <Button @click="openCreateDialog">
                            <Plus class="mr-2 h-4 w-4" />
                            Ajouter un employé
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="mb-4">
                        <Input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Rechercher un employé..."
                            class="max-w-sm"
                        />
                    </div>

                    <Separator class="mb-4" />

                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Employé</TableHead>
                                    <TableHead>ID Employé</TableHead>
                                    <TableHead>Téléphone</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Poste</TableHead>
                                    <TableHead>Département</TableHead>
                                    <TableHead>Manager</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead class="w-[50px]"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="filteredEmployees.length > 0">
                                    <TableRow
                                        v-for="employee in filteredEmployees"
                                        :key="employee.id"
                                    >
                                        <TableCell>
                                            <div class="flex items-center gap-3">
                                                <Avatar class="h-10 w-10">
                                                    <AvatarFallback>
                                                        {{ getInitials(`${employee.first_name} ${employee.last_name}`) }}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <div class="font-medium">
                                                        {{ employee.first_name }} {{ employee.last_name }}
                                                    </div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <code class="relative rounded bg-muted px-[0.3rem] py-[0.2rem] font-mono text-sm">
                                                {{ employee.employee_id }}
                                            </code>
                                        </TableCell>
                                        <TableCell class="text-sm">
                                            {{ employee.phone }}
                                        </TableCell>
                                        <TableCell class="text-sm text-muted-foreground">
                                            {{ employee.email || '-' }}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {{ getPositionLabel(employee.position) }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-sm text-muted-foreground">
                                            {{ employee.department || '-' }}
                                        </TableCell>
                                        <TableCell class="text-sm text-muted-foreground">
                                            <span v-if="employee.manager">
                                                {{ employee.manager.first_name }} {{ employee.manager.last_name }}
                                            </span>
                                            <span v-else class="text-muted-foreground">-</span>
                                        </TableCell>
                                        <TableCell>
                                            <Badge :variant="getStatusBadgeVariant(employee.status)">
                                                {{ getStatusLabel(employee.status) }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <DropdownMenu>
                                                <DropdownMenuTrigger as-child>
                                                    <Button variant="ghost" size="sm">
                                                        <MoreHorizontal class="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem @click="openEditDialog(employee)">
                                                        <Pencil class="mr-2 h-4 w-4" />
                                                        Modifier
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        @click="openDeleteDialog(employee)"
                                                        class="text-destructive"
                                                    >
                                                        <Trash2 class="mr-2 h-4 w-4" />
                                                        Supprimer
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <TableEmpty
                                    v-else
                                    :colspan="9"
                                >
                                    <p class="text-sm text-muted-foreground">
                                        {{ searchQuery ? 'Aucun employé ne correspond à votre recherche' : 'Aucun employé trouvé' }}
                                    </p>
                                </TableEmpty>
                            </TableBody>
                        </Table>
                    </div>

                    <Separator v-if="employees.links && employees.links.length > 3" class="my-4" />

                    <div
                        v-if="employees.links && employees.links.length > 3"
                        class="flex items-center justify-between"
                    >
                        <div class="text-sm text-muted-foreground">
                            Affichage de {{ employees.from }} à {{ employees.to }} sur {{ employees.total }} employés
                        </div>
                        <div class="flex items-center gap-2">
                            <template v-for="link in employees.links" :key="link.label">
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

        <EmployeeFormDialog
            :open="dialogOpen"
            :employee="selectedEmployee"
            :managers="managers"
            @close="closeDialog"
        />

        <Dialog :open="deleteDialogOpen" @update:open="closeDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Supprimer l'employé</DialogTitle>
                    <DialogDescription>
                        Êtes-vous sûr de vouloir supprimer
                        <strong v-if="employeeToDelete">
                            {{ employeeToDelete.first_name }} {{ employeeToDelete.last_name }}
                        </strong>
                        ? Cette action est irréversible.
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
