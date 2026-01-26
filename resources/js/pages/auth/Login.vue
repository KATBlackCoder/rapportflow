<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Lock, LogIn, User } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/login';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <AuthBase
        title="Connexion"
        description="Entrez vos identifiants pour accéder à votre compte"
    >
        <Head title="Connexion" />

        <Alert v-if="status" variant="default" class="mb-6">
            <AlertDescription class="text-sm">
                {{ status }}
            </AlertDescription>
        </Alert>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="space-y-6"
        >
            <div class="space-y-4">
                <div class="space-y-2">
                    <Label for="username" class="text-sm font-medium">
                        Nom d'utilisateur
                    </Label>
                    <div class="relative">
                        <User
                            class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            id="username"
                            type="text"
                            name="username"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="username"
                            placeholder="nom@telephone.org"
                            class="pl-9"
                            :class="errors.username ? 'border-destructive' : ''"
                        />
                    </div>
                    <InputError :message="errors.username" />
                </div>

                <div class="space-y-2">
                    <Label for="password" class="text-sm font-medium">
                        Mot de passe
                    </Label>
                    <div class="relative">
                        <Lock
                            class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="pl-9"
                            :class="errors.password ? 'border-destructive' : ''"
                        />
                    </div>
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center space-x-2">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <Label
                        for="remember"
                        class="text-sm font-normal leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    >
                        Se souvenir de moi
                    </Label>
                </div>
            </div>

            <Button
                type="submit"
                class="w-full"
                size="lg"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <LogIn v-if="!processing" class="mr-2 size-4" />
                <Spinner v-else class="mr-2 size-4" />
                {{ processing ? 'Connexion...' : 'Se connecter' }}
            </Button>
        </Form>
    </AuthBase>
</template>
