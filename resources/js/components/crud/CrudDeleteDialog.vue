<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { FormAction } from '@/types/crud';

withDefaults(
    defineProps<{
        action: FormAction;
        triggerLabel: string;
        title: string;
        description?: string;
        confirmLabel?: string;
        cancelLabel?: string;
    }>(),
    {
        description: undefined,
        confirmLabel: undefined,
        cancelLabel: 'Cancel',
    },
);

const open = ref(false);
</script>

<template>
    <Dialog v-model:open="open">
        <Tooltip :ignore-non-keyboard-focus="true">
            <TooltipTrigger as-child>
                <DialogTrigger as-child>
                    <Button
                        type="button"
                        variant="destructive"
                        size="icon-sm"
                        :aria-label="triggerLabel"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </DialogTrigger>
            </TooltipTrigger>
            <TooltipContent>{{ triggerLabel }}</TooltipContent>
        </Tooltip>

        <DialogContent>
            <Form
                v-bind="action"
                @success="open = false"
                v-slot="{ processing }"
            >
                <DialogHeader class="space-y-3">
                    <DialogTitle>{{ title }}</DialogTitle>
                    <DialogDescription v-if="description">
                        {{ description }}
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="mt-6 gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">
                            {{ cancelLabel }}
                        </Button>
                    </DialogClose>

                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="processing"
                    >
                        {{ confirmLabel ?? triggerLabel }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
