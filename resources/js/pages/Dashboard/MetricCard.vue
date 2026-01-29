<script setup lang="ts">
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import type { Component } from 'vue';

defineProps<{
    label: string;
    value: string | number;
    icon?: Component;
}>();
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <span class="text-sm font-medium text-muted-foreground">
                {{ label }}
            </span>
            <component
                v-if="icon"
                :is="icon"
                class="h-4 w-4 text-muted-foreground"
            />
        </CardHeader>
        <CardContent>
            <p class="text-2xl font-bold">
                {{ value }}
            </p>
        </CardContent>
    </Card>
</template>
