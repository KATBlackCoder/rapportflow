<script setup lang="ts">
import { dashboard, login } from '@/routes';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Welcome" />

    <div
        class="flex min-h-screen flex-col items-center justify-center bg-background p-6"
    >
        <div class="w-full max-w-md space-y-8">
            <div class="text-center space-y-2">
                <h1 class="text-4xl font-bold tracking-tight">
                    Bienvenue
                </h1>
                <p class="text-muted-foreground">
                    Connectez-vous pour accéder à votre tableau de bord
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Accès à l'application</CardTitle>
                    <CardDescription>
                        Utilisez vos identifiants pour vous connecter
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="block"
                    >
                        <Button class="w-full" size="lg">
                            Accéder au tableau de bord
                        </Button>
                    </Link>
                    <Link v-else :href="login()" class="block">
                        <Button class="w-full" size="lg">
                            Se connecter
                        </Button>
                    </Link>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
