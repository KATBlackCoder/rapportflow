<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { Form, Head } from '@inertiajs/vue3';
</script>

<template>
    <AuthBase
        title="Create an account"
        description="Enter your employee details below to create your account"
    >
        <Head title="Register" />

        <Form
            v-bind="store.form()"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="first_name">First Name</Label>
                    <Input
                        id="first_name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="given-name"
                        name="first_name"
                        placeholder="First name"
                    />
                    <InputError :message="errors.first_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="last_name">Last Name</Label>
                    <Input
                        id="last_name"
                        type="text"
                        required
                        :tabindex="2"
                        autocomplete="family-name"
                        name="last_name"
                        placeholder="Last name"
                    />
                    <InputError :message="errors.last_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="phone">Phone</Label>
                    <Input
                        id="phone"
                        type="text"
                        required
                        :tabindex="3"
                        autocomplete="tel"
                        name="phone"
                        placeholder="12345678"
                        maxlength="8"
                    />
                    <InputError :message="errors.phone" />
                    <p class="text-xs text-muted-foreground">
                        8 digits (Malian format)
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="position">Position</Label>
                    <select
                        id="position"
                        name="position"
                        required
                        :tabindex="4"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="">Select a position</option>
                        <option value="employer">Employé</option>
                        <option value="superviseur">Superviseur</option>
                        <option value="chef_superviseur">Chef superviseur</option>
                        <option value="manager">Manager</option>
                    </select>
                    <InputError :message="errors.position" />
                </div>

                <div class="grid gap-2">
                    <Label for="department">Department (Optional)</Label>
                    <Input
                        id="department"
                        type="text"
                        :tabindex="5"
                        name="department"
                        placeholder="Department"
                    />
                    <InputError :message="errors.department" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address (Optional)</Label>
                    <Input
                        id="email"
                        type="email"
                        :tabindex="6"
                        autocomplete="email"
                        name="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    tabindex="7"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" />
                    Create account
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink
                    :href="login()"
                    class="underline underline-offset-4"
                    :tabindex="6"
                    >Log in</TextLink
                >
            </div>
        </Form>
    </AuthBase>
</template>
