<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import rapports from '@/routes/rapports';
import { BarChart3, ClipboardList, FileCheck } from 'lucide-vue-next';

defineProps<{
    showSupervisorActions: boolean;
}>();
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <Button as-child>
            <Link :href="rapports.create().url">
                <ClipboardList class="mr-2 h-4 w-4" />
                Faire un rapport
            </Link>
        </Button>
        <template v-if="showSupervisorActions">
            <Button
                variant="outline"
                as-child
            >
                <Link :href="rapports.corrections().url">
                    <FileCheck class="mr-2 h-4 w-4" />
                    Corriger un rapport
                </Link>
            </Button>
            <Button
                variant="outline"
                as-child
            >
                <Link :href="rapports.analysis().url">
                    <BarChart3 class="mr-2 h-4 w-4" />
                    Analyser les rapports
                </Link>
            </Button>
        </template>
    </div>
</template>
