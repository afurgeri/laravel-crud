<script setup lang="ts">
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/composables/useTranslation';
import type { CrudField } from '@/types/crud';

const props = defineProps<{
    field: CrudField;
    error?: string;
    defaultValue?: unknown;
    readOnly?: boolean;
    labelClass?: string;
    idPrefix?: string;
}>();

const checkboxValue = ref(booleanValue(props.defaultValue));
const arrayValues = ref(arrayValue(props.defaultValue));
const arrayInputValue = ref('');
const arrayInputError = ref<string>();
const { t } = useTranslation();

watch(
    () => props.defaultValue,
    (value) => {
        checkboxValue.value = booleanValue(value);
    },
);

watch(
    () => props.defaultValue,
    (value) => {
        arrayValues.value = arrayValue(value);
        arrayInputValue.value = '';
        arrayInputError.value = undefined;
    },
);

function inputValue(value: unknown): string | number | undefined {
    return typeof value === 'string' || typeof value === 'number'
        ? value
        : undefined;
}

function booleanValue(value: unknown): boolean {
    return value === true || value === 1 || value === '1';
}

function arrayValue(value: unknown): string[] {
    if (!Array.isArray(value)) {
        return [];
    }

    return value
        .filter(
            (item): item is string | number =>
                typeof item === 'string' || typeof item === 'number',
        )
        .map(String);
}

function addArrayValue(): void {
    const value = arrayInputValue.value.trim();

    if (value === '') {
        return;
    }

    if (props.field.unique_items && arrayValues.value.includes(value)) {
        arrayInputError.value = 'This value is already included.';

        return;
    }

    arrayValues.value.push(value);
    arrayInputValue.value = '';
    arrayInputError.value = undefined;
}

function removeArrayValue(index: number): void {
    arrayValues.value.splice(index, 1);
    arrayInputError.value = undefined;
}
</script>

<template>
    <div class="space-y-4">
        <div class="space-y-2">
            <Label
                :for="idPrefix ? `${idPrefix}-${field.name}` : field.name"
                :class="labelClass"
                >{{ field.label }}</Label
            >
            <template v-if="field.type === 'array'">
                <div v-if="readOnly" class="flex flex-wrap gap-2">
                    <span
                        v-for="value in arrayValues"
                        :key="value"
                        class="rounded-md bg-muted px-2 py-1 text-sm"
                    >
                        {{ value }}
                    </span>
                    <span
                        v-if="arrayValues.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No values.
                    </span>
                </div>

                <div v-else class="space-y-2">
                    <div class="flex items-center gap-2">
                        <Input
                            :id="
                                idPrefix
                                    ? `${idPrefix}-${field.name}`
                                    : field.name
                            "
                            v-model="arrayInputValue"
                            :required="
                                field.required && arrayValues.length === 0
                            "
                            :disabled="readOnly"
                            :aria-invalid="
                                error || arrayInputError ? 'true' : undefined
                            "
                            @keydown.enter.prevent="addArrayValue"
                        />
                        <button
                            type="button"
                            class="rounded-md border border-input px-3 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground disabled:pointer-events-none disabled:opacity-50"
                            :disabled="
                                readOnly || arrayInputValue.trim() === ''
                            "
                            @click="addArrayValue"
                        >
                            {{ t('Add') }}
                        </button>
                    </div>

                    <div
                        v-if="arrayValues.length > 0"
                        class="flex flex-wrap gap-2"
                    >
                        <span
                            v-for="(value, index) in arrayValues"
                            :key="`${value}-${index}`"
                            class="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-1 text-sm"
                        >
                            {{ value }}
                            <button
                                type="button"
                                class="rounded-sm px-1 text-muted-foreground hover:bg-background hover:text-foreground"
                                :aria-label="`Remove ${value}`"
                                @click="removeArrayValue(index)"
                            >
                                ×
                            </button>
                            <input
                                type="hidden"
                                :name="`${field.name}[]`"
                                :value="value"
                            />
                        </span>
                    </div>
                </div>
            </template>
            <input
                v-else-if="field.type === 'checkbox'"
                type="hidden"
                :name="field.name"
                :value="checkboxValue ? '1' : '0'"
            />
            <Checkbox
                v-if="field.type === 'checkbox'"
                :id="idPrefix ? `${idPrefix}-${field.name}` : field.name"
                v-model="checkboxValue"
                :disabled="readOnly"
                :aria-invalid="error ? 'true' : undefined"
            />
            <Textarea
                v-else-if="field.type === 'textarea'"
                :id="idPrefix ? `${idPrefix}-${field.name}` : field.name"
                :name="field.name"
                :required="field.required"
                :disabled="readOnly"
                :aria-invalid="error ? 'true' : undefined"
                :default-value="inputValue(defaultValue)"
            />
            <Input
                v-else-if="field.type !== 'array'"
                :id="idPrefix ? `${idPrefix}-${field.name}` : field.name"
                :name="field.name"
                :type="field.type"
                :required="field.required"
                :disabled="readOnly"
                :autocomplete="
                    field.type === 'password' ? 'new-password' : undefined
                "
                :aria-invalid="error ? 'true' : undefined"
                :default-value="inputValue(defaultValue)"
            />
            <InputError :message="arrayInputError ?? error" />
        </div>

        <div v-if="field.confirmed" class="space-y-2">
            <Label
                :for="
                    idPrefix
                        ? `${idPrefix}-${field.name}-confirmation`
                        : `${field.name}_confirmation`
                "
                :class="labelClass"
                >Confirm {{ field.label }}</Label
            >
            <Input
                :id="
                    idPrefix
                        ? `${idPrefix}-${field.name}-confirmation`
                        : `${field.name}_confirmation`
                "
                :name="`${field.name}_confirmation`"
                :type="field.type"
                :required="field.required"
                autocomplete="new-password"
            />
        </div>
    </div>
</template>
