<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { Component } from 'vue';

defineProps<{
    title: string;
    description: string;
    icon: Component;
}>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <component :is="icon" class="h-5 w-5" />
                {{ title }}
            </CardTitle>
            <CardDescription>
                {{ description }}
            </CardDescription>
        </CardHeader>
        <CardContent v-if="$slots.default">
            <slot />
        </CardContent>
        <CardFooter v-if="$slots.footer">
            <slot name="footer" />
        </CardFooter>
    </Card>
</template>
