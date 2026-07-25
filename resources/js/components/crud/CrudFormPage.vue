<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import CrudField from '@/components/crud/CrudField.vue';
import CrudForm from '@/components/crud/CrudForm.vue';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/composables/useTranslation';
import type {
    CrudField as CrudFieldConfig,
    CrudHref,
    CrudSchema,
    FormAction,
} from '@/types/crud';

defineSlots<{
    fields(props: {
        errors: Record<string, string | undefined>;
    }): unknown;
}>();

const props = withDefaults(
    defineProps<{
        schema: CrudSchema;
        action?: FormAction;
        backHref: CrudHref;
        title: string;
        submitLabel: string;
        description?: string;
        initialValues?: Record<string, unknown>;
        fields?: CrudFieldConfig[];
        fieldIdPrefix?: string;
        readOnly?: boolean;
    }>(),
    {
        description: undefined,
        initialValues: () => ({}),
        fields: undefined,
        fieldIdPrefix: undefined,
        readOnly: false,
    },
);

const { t } = useTranslation();

function fieldDefault(
    field: CrudFieldConfig,
    initialValues: Record<string, unknown>,
): unknown {
    if (Object.prototype.hasOwnProperty.call(initialValues, field.name)) {
        return initialValues[field.name];
    }

    return field.defaultValue;
}
</script>

<template>
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <div class="flex items-center gap-3">
            <Button as-child variant="ghost" size="icon-sm">
                <Link
                    :href="backHref"
                    :aria-label="t('Back to :name', { name: schema.title })"
                >
                    <ArrowLeft class="size-4" />
                </Link>
            </Button>

            <div>
                <p class="text-sm text-muted-foreground">{{ schema.title }}</p>
                <h1 class="text-3xl font-semibold tracking-tight text-foreground">
                    {{ title }}
                </h1>
            </div>
        </div>

        <section
            class="rounded-2xl border border-border/70 bg-card p-5 shadow-sm sm:p-8"
        >
            <p v-if="description" class="mb-6 text-sm text-muted-foreground">
                {{ description }}
            </p>

            <CrudForm
                v-if="!readOnly && action"
                :action="action"
                :fields="fields ?? schema.fields"
                :initial-values="initialValues"
                :submit-label="submitLabel"
                :field-id-prefix="fieldIdPrefix"
                form-class="flex w-full flex-col gap-6"
            >
                <template #fields="slotProps">
                    <slot name="fields" v-bind="slotProps" />
                </template>
            </CrudForm>

            <div v-else class="flex w-full flex-col gap-6">
                <CrudField
                    v-for="field in fields ?? schema.fields"
                    :key="field.name"
                    :field="field"
                    :default-value="fieldDefault(field, initialValues)"
                    :id-prefix="fieldIdPrefix"
                />

                <slot name="fields" :errors="{}" />
            </div>
        </section>
    </div>
</template>
