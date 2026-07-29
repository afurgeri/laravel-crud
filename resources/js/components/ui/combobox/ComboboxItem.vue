<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { ComboboxItem } from 'reka-ui';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
    <ComboboxItem
        data-slot="combobox-item"
        v-bind="$attrs"
        :class="
            cn(
                'relative flex cursor-default items-center rounded-sm px-2 py-1.5 text-sm outline-none select-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground',
                props.class,
            )
        "
    >
        <slot />
    </ComboboxItem>
</template>
