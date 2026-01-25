<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const action = ref<'keep' | 'change' | null>(null);

const form = useForm({
    action: 'keep',
    password: '',
    password_confirmation: '',
});

const handleKeepPassword = () => {
    action.value = 'keep';
    form.action = 'keep';
    form.password = '';
    form.password_confirmation = '';
    form.post('/first-login', {
        onSuccess: () => {
            // Redirection handled by server
        },
    });
};

const handleSubmit = () => {
    if (action.value === 'change') {
        form.action = 'change';
        form.post('/first-login');
    }
};
</script>

<template>
    <AuthBase
        title="First login"
        description="Please choose whether to keep your default password or set a new one"
    >
        <Head title="First Login" />

        <Card>
            <CardHeader>
                <CardTitle>Password Setup</CardTitle>
                <CardDescription>
                    This is your first login. Please choose an option below.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex flex-col gap-6">
                    <div class="grid gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            class="w-full"
                            :disabled="form.processing"
                            @click="handleKeepPassword"
                        >
                            <Spinner v-if="form.processing" />
                            Keep the default password
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            class="w-full"
                            :disabled="form.processing"
                            @click="action = 'change'"
                        >
                            Choose a new password
                        </Button>
                    </div>

                    <form
                        v-if="action === 'change'"
                        @submit.prevent="handleSubmit"
                        class="flex flex-col gap-4"
                    >
                        <div class="grid gap-2">
                            <Label for="password">New Password</Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                                autofocus
                                autocomplete="new-password"
                                placeholder="Enter new password"
                            />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation">Confirm Password</Label>
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                placeholder="Confirm new password"
                            />
                            <InputError :message="form.errors.password_confirmation" />
                        </div>

                        <InputError :message="form.errors.action" />

                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="form.processing"
                        >
                            <Spinner v-if="form.processing" />
                            Continue
                        </Button>
                    </form>
                </div>
            </CardContent>
        </Card>
    </AuthBase>
</template>
