<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import CrudField from '@/components/crud/CrudField.vue';
import { Button } from '@/components/ui/button';
import type { CrudField as CrudFieldConfig, FormAction } from '@/types/crud';

withDefaults(
    defineProps<{
        action: FormAction;
        fields: CrudFieldConfig[];
        defaults?: Record<string, unknown>;
        submitLabel: string;
        resetOnSuccess?: boolean;
        formClass?: string;
        fieldLabelClass?: string;
        fieldIdPrefix?: string;
    }>(),
    {
        defaults: () => ({}),
        resetOnSuccess: false,
        formClass: 'flex flex-col gap-4',
        fieldLabelClass: undefined,
        fieldIdPrefix: undefined,
    },
);

const emit = defineEmits<{
    success: [];
}>();
</script>

<template>
    <Form
        v-bind="action"
        :reset-on-success="resetOnSuccess"
        :class="formClass"
        v-slot="{ errors, processing }"
        @success="emit('success')"
    >
        <CrudField
            v-for="field in fields"
            :key="field.name"
            :field="field"
            :error="errors[field.name]"
            :default-value="defaults[field.name]"
            :label-class="fieldLabelClass"
            :id-prefix="fieldIdPrefix"
        />

        <slot name="fields" :errors="errors" />

        <Button type="submit" :disabled="processing">
            {{ submitLabel }}
        </Button>
    </Form>
</template>
