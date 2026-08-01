<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import CrudField from '@/components/crud/CrudField.vue';
import { Button } from '@/components/ui/button';
import type { CrudField as CrudFieldConfig, FormAction } from '@/types/crud';

const props = withDefaults(
    defineProps<{
        action: FormAction;
        fields: CrudFieldConfig[];
        initialValues?: Record<string, unknown>;
        submitLabel: string;
        resetOnSuccess?: boolean;
        readOnly?: boolean;
        formClass?: string;
        fieldLabelClass?: string;
        fieldIdPrefix?: string;
    }>(),
    {
        initialValues: () => ({}),
        resetOnSuccess: false,
        readOnly: false,
        formClass: 'grid grid-cols-12 gap-4',
        fieldLabelClass: undefined,
        fieldIdPrefix: undefined,
    },
);

const fieldRenderKey = ref(0);

const emit = defineEmits<{
    success: [];
}>();

function fieldDefault(field: CrudFieldConfig): unknown {
    if (Object.prototype.hasOwnProperty.call(props.initialValues, field.name)) {
        return props.initialValues[field.name];
    }

    return field.defaultValue;
}

function handleSuccess(): void {
    if (props.resetOnSuccess) {
        fieldRenderKey.value += 1;
    }

    emit('success');
}
</script>

<template>
    <Form
        v-bind="action"
        :reset-on-success="resetOnSuccess"
        :class="formClass"
        v-slot="{ errors, processing }"
        @success="handleSuccess"
    >
        <CrudField
            v-for="field in fields.filter((field) => field.visible)"
            :key="`${field.name}-${fieldRenderKey}`"
            :field="field"
            :read-only="readOnly"
            :error="errors[field.name]"
            :default-value="fieldDefault(field)"
            :label-class="fieldLabelClass"
            :id-prefix="fieldIdPrefix"
        />

        <div class="col-span-12">
            <slot name="fields" :errors="errors" />
        </div>

        <Button
            v-if="!readOnly"
            type="submit"
            class="col-span-12 w-full"
            :disabled="processing"
        >
            {{ submitLabel }}
        </Button>
    </Form>
</template>
