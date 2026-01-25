<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

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
};

type Manager = {
    id: number;
    first_name: string;
    last_name: string;
};

type Props = {
    open: boolean;
    employee?: Employee | null;
    managers: Manager[];
    onClose: () => void;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
}>();

const isEditing = computed(() => !!props.employee);

const form = useForm({
    employee_id: '',
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    position: 'employer',
    department: '',
    manager_id: null as number | null,
    salary: null as number | null,
    hire_date: '',
    status: 'active',
});

watch(
    () => props.employee,
    (employee) => {
        if (employee) {
            form.employee_id = employee.employee_id;
            form.first_name = employee.first_name;
            form.last_name = employee.last_name;
            form.email = employee.email || '';
            form.phone = employee.phone;
            form.position = employee.position;
            form.department = employee.department || '';
            form.manager_id = employee.manager_id;
            form.salary = employee.salary;
            form.hire_date = employee.hire_date || '';
            form.status = employee.status;
        } else {
            form.reset();
        }
    },
    { immediate: true },
);

watch(
    () => props.open,
    (open) => {
        if (!open) {
            form.reset();
            form.clearErrors();
        }
    },
);

const submit = () => {
    if (isEditing.value && props.employee) {
        form.put(`/employees/${props.employee.id}`, {
            onSuccess: () => {
                emit('close');
            },
        });
    } else {
        form.post('/employees', {
            onSuccess: () => {
                emit('close');
            },
        });
    }
};

const handleClose = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>
                    {{ isEditing ? 'Modifier un employé' : 'Créer un nouvel employé' }}
                </DialogTitle>
                <DialogDescription>
                    {{ isEditing ? 'Modifiez les informations de l\'employé' : 'Remplissez les informations pour créer un nouvel employé' }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="employee_id">ID Employé *</Label>
                        <Input
                            id="employee_id"
                            v-model="form.employee_id"
                            required
                            placeholder="EMP001"
                        />
                        <InputError :message="form.errors.employee_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="phone">Téléphone *</Label>
                        <Input
                            id="phone"
                            v-model="form.phone"
                            required
                            placeholder="12345678"
                            maxlength="8"
                        />
                        <InputError :message="form.errors.phone" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="first_name">Prénom *</Label>
                        <Input
                            id="first_name"
                            v-model="form.first_name"
                            required
                            placeholder="Amadou"
                        />
                        <InputError :message="form.errors.first_name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="last_name">Nom *</Label>
                        <Input
                            id="last_name"
                            v-model="form.last_name"
                            required
                            placeholder="Traoré"
                        />
                        <InputError :message="form.errors.last_name" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="amadou@example.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="position">Poste *</Label>
                        <select
                            id="position"
                            v-model="form.position"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="employer">Employé</option>
                            <option value="superviseur">Superviseur</option>
                            <option value="chef_superviseur">Chef Superviseur</option>
                            <option value="manager">Manager</option>
                        </select>
                        <InputError :message="form.errors.position" />
                    </div>

                    <div class="space-y-2">
                        <Label for="status">Statut *</Label>
                        <select
                            id="status"
                            v-model="form.status"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="active">Actif</option>
                            <option value="inactive">Inactif</option>
                            <option value="suspended">Suspendu</option>
                            <option value="terminated">Résilié</option>
                        </select>
                        <InputError :message="form.errors.status" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="department">Département</Label>
                    <Input
                        id="department"
                        v-model="form.department"
                        placeholder="IT, RH, Production..."
                    />
                    <InputError :message="form.errors.department" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="manager_id">Manager</Label>
                        <select
                            id="manager_id"
                            :value="form.manager_id ?? ''"
                            @change="(e) => { const value = (e.target as HTMLSelectElement).value; form.manager_id = value ? Number(value) : null; }"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="">Aucun</option>
                            <option
                                v-for="manager in managers"
                                :key="manager.id"
                                :value="manager.id"
                            >
                                {{ manager.first_name }} {{ manager.last_name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.manager_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="salary">Salaire</Label>
                        <Input
                            id="salary"
                            :model-value="form.salary !== null ? String(form.salary) : ''"
                            @update:model-value="form.salary = $event && $event !== '' ? Number($event) : null"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                        />
                        <InputError :message="form.errors.salary" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="hire_date">Date d'embauche</Label>
                    <Input
                        id="hire_date"
                        v-model="form.hire_date"
                        type="date"
                    />
                    <InputError :message="form.errors.hire_date" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="handleClose"
                    >
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Enregistrement...' : isEditing ? 'Modifier' : 'Créer' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
