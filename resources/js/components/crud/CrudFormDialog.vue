<script setup lang="ts">
import { ref } from 'vue';
import CrudForm from '@/components/crud/CrudForm.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { CrudField, FormAction } from '@/types/crud';

withDefaults(
    defineProps<{
        action: FormAction;
        fields: CrudField[];
        triggerLabel: string;
        title: string;
        submitLabel: string;
        description?: string;
        defaults?: Record<string, unknown>;
        resetOnSuccess?: boolean;
        fieldIdPrefix?: string;
        triggerTooltip?: string;
    }>(),
    {
        description: undefined,
        defaults: () => ({}),
        resetOnSuccess: false,
        fieldIdPrefix: undefined,
        triggerTooltip: undefined,
    },
);

const open = ref(false);
</script>

<template>
    <Dialog v-model:open="open">
        <Tooltip v-if="triggerTooltip" :ignore-non-keyboard-focus="true">
            <TooltipTrigger as-child>
                <DialogTrigger as-child>
                    <slot name="trigger">
                        <Button type="button">{{ triggerLabel }}</Button>
                    </slot>
                </DialogTrigger>
            </TooltipTrigger>
            <TooltipContent>{{ triggerTooltip }}</TooltipContent>
        </Tooltip>

        <DialogTrigger v-else as-child>
            <slot name="trigger">
                <Button type="button">{{ triggerLabel }}</Button>
            </slot>
        </DialogTrigger>

        <DialogContent
            class="max-h-[calc(100dvh-2rem)] overflow-y-auto sm:max-w-lg"
        >
            <DialogHeader class="text-left">
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <CrudForm
                :action="action"
                :fields="fields"
                :defaults="defaults"
                :submit-label="submitLabel"
                :reset-on-success="resetOnSuccess"
                :field-id-prefix="fieldIdPrefix"
                form-class="flex flex-col gap-4 px-1 pb-6"
                @success="open = false"
            >
                <template #fields="slotProps">
                    <slot name="fields" v-bind="slotProps" />
                </template>
            </CrudForm>
        </DialogContent>
    </Dialog>
</template>
