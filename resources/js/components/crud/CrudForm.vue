<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { reactive, ref, useSlots } from 'vue';
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
const fieldValues = reactive<Record<string, unknown>>({});
const slots = useSlots();

function hasFieldSlot(fieldName: string): boolean {
    return Boolean(slots[`field-${fieldName}`]);
}

const emit = defineEmits<{
    success: [];
}>();

function fieldDefault(field: CrudFieldConfig): unknown {
    if (Object.prototype.hasOwnProperty.call(props.initialValues, field.name)) {
        return props.initialValues[field.name];
    }

    return field.defaultValue;
}

for (const field of props.fields) {
    fieldValues[field.name] = fieldDefault(field);
}

function handleFieldValue(name: string, value: unknown): void {
    fieldValues[name] = value;
}

function handleSuccess(): void {
    if (props.resetOnSuccess) {
        for (const field of props.fields) {
            fieldValues[field.name] = fieldDefault(field);
        }

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
            :values="fieldValues"
            :label-class="fieldLabelClass"
            :id-prefix="fieldIdPrefix"
            @value-change="handleFieldValue"
        >
            <template v-if="hasFieldSlot(field.name)" #default="slotProps">
                <slot :name="`field-${field.name}`" v-bind="slotProps" />
            </template>
        </CrudField>

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
