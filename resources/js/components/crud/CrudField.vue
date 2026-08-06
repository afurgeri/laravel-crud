<script setup lang="ts">
import { X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import CrudCombobox from '@/components/crud/CrudCombobox.vue';
import RemoteCombobox from '@/components/crud/RemoteCombobox.vue';
import InputError from '@/components/InputError.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/composables/useTranslation';
import type { CrudField, CrudFieldSlotProps } from '@/types/crud';

const props = defineProps<{
    field: CrudField;
    error?: string;
    defaultValue?: unknown;
    readOnly?: boolean;
    labelClass?: string;
    idPrefix?: string;
}>();

defineSlots<{
    default(props: CrudFieldSlotProps): unknown;
}>();

type CrudFieldBreakpoint = 'base' | 'sm' | 'md' | 'lg' | 'xl' | '2xl';

const spanClassesByBreakpoint: Record<CrudFieldBreakpoint, string[]> = {
    base: [
        'col-span-1',
        'col-span-2',
        'col-span-3',
        'col-span-4',
        'col-span-5',
        'col-span-6',
        'col-span-7',
        'col-span-8',
        'col-span-9',
        'col-span-10',
        'col-span-11',
        'col-span-12',
    ],
    sm: [
        'sm:col-span-1',
        'sm:col-span-2',
        'sm:col-span-3',
        'sm:col-span-4',
        'sm:col-span-5',
        'sm:col-span-6',
        'sm:col-span-7',
        'sm:col-span-8',
        'sm:col-span-9',
        'sm:col-span-10',
        'sm:col-span-11',
        'sm:col-span-12',
    ],
    md: [
        'md:col-span-1',
        'md:col-span-2',
        'md:col-span-3',
        'md:col-span-4',
        'md:col-span-5',
        'md:col-span-6',
        'md:col-span-7',
        'md:col-span-8',
        'md:col-span-9',
        'md:col-span-10',
        'md:col-span-11',
        'md:col-span-12',
    ],
    lg: [
        'lg:col-span-1',
        'lg:col-span-2',
        'lg:col-span-3',
        'lg:col-span-4',
        'lg:col-span-5',
        'lg:col-span-6',
        'lg:col-span-7',
        'lg:col-span-8',
        'lg:col-span-9',
        'lg:col-span-10',
        'lg:col-span-11',
        'lg:col-span-12',
    ],
    xl: [
        'xl:col-span-1',
        'xl:col-span-2',
        'xl:col-span-3',
        'xl:col-span-4',
        'xl:col-span-5',
        'xl:col-span-6',
        'xl:col-span-7',
        'xl:col-span-8',
        'xl:col-span-9',
        'xl:col-span-10',
        'xl:col-span-11',
        'xl:col-span-12',
    ],
    '2xl': [
        '2xl:col-span-1',
        '2xl:col-span-2',
        '2xl:col-span-3',
        '2xl:col-span-4',
        '2xl:col-span-5',
        '2xl:col-span-6',
        '2xl:col-span-7',
        '2xl:col-span-8',
        '2xl:col-span-9',
        '2xl:col-span-10',
        '2xl:col-span-11',
        '2xl:col-span-12',
    ],
};

const checkboxValue = ref(booleanValue(props.defaultValue));
const selectValue = ref(optionValue(props.defaultValue));
const textValue = ref(inputValue(props.defaultValue));
const confirmationValue = ref('');
const remoteSelectValue = computed({
    get: () => selectValue.value ?? '',
    set: (value: string) => {
        selectValue.value = value;
    },
});
const arrayValues = ref(arrayValue(props.defaultValue));
const arrayInputValue = ref('');
const arrayInputError = ref<string>();
const arrayCleared = ref(false);
const { t } = useTranslation();

const fieldId = computed(() =>
    props.idPrefix ? `${props.idPrefix}-${props.field.name}` : props.field.name,
);

watch(
    () => props.defaultValue,
    (value) => {
        checkboxValue.value = booleanValue(value);
        textValue.value = inputValue(value);
    },
);

watch(
    () => props.defaultValue,
    (value) => {
        selectValue.value = optionValue(value);
    },
);

watch(
    () => props.defaultValue,
    (value) => {
        arrayValues.value = arrayValue(value);
        arrayInputValue.value = '';
        arrayInputError.value = undefined;
        arrayCleared.value = false;
    },
);

const hasClearableValue = computed(() => {
    if (!props.field.clearable || props.readOnly) {
        return false;
    }

    if (props.field.type === 'checkbox') {
        return checkboxValue.value;
    }

    if (props.field.type === 'array') {
        return arrayValues.value.length > 0 || arrayInputValue.value !== '';
    }

    if (['select', 'combobox', 'remote-select'].includes(props.field.type)) {
        return selectValue.value !== undefined && selectValue.value !== '';
    }

    return textValue.value !== undefined && textValue.value !== '';
});

function inputValue(value: unknown): string | number | undefined {
    return typeof value === 'string' || typeof value === 'number'
        ? value
        : undefined;
}

function spanClasses(span: CrudField['span']): string[] {
    return (Object.entries(span) as [CrudFieldBreakpoint, number][]).flatMap(
        ([breakpoint, columns]) =>
            spanClassesByBreakpoint[breakpoint]?.[columns - 1] ?? [],
    );
}

function booleanValue(value: unknown): boolean {
    return value === true || value === 1 || value === '1';
}

function optionValue(value: unknown): string | undefined {
    if (value === null || value === undefined) {
        return undefined;
    }

    if (typeof value === 'boolean') {
        return value ? '1' : '0';
    }

    return typeof value === 'string' || typeof value === 'number'
        ? String(value)
        : undefined;
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

function clearValue(): void {
    switch (props.field.type) {
        case 'checkbox':
            checkboxValue.value = false;
            break;
        case 'array':
            arrayValues.value = [];
            arrayInputValue.value = '';
            arrayInputError.value = undefined;
            arrayCleared.value = true;
            break;
        case 'select':
        case 'combobox':
        case 'remote-select':
            selectValue.value = undefined;
            break;
        default:
            textValue.value = '';
            confirmationValue.value = '';
            break;
    }
}
</script>

<template>
    <div v-if="field.visible" class="contents">
        <div :class="['space-y-2', spanClasses(field.span)]">
            <Label :for="fieldId" :class="labelClass">{{ field.label }}</Label>
            <slot
                v-if="$slots.default"
                v-bind="{
                    field,
                    id: fieldId,
                    name: field.name,
                    defaultValue,
                    error,
                    required: field.required,
                    readOnly: readOnly ?? false,
                }"
            />
            <template v-if="!$slots.default && field.type === 'array'">
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
                v-if="!$slots.default && field.type === 'checkbox'"
                type="hidden"
                :name="field.name"
                :value="checkboxValue ? '1' : '0'"
            />
            <Checkbox
                v-if="!$slots.default && field.type === 'checkbox'"
                :id="idPrefix ? `${idPrefix}-${field.name}` : field.name"
                v-model="checkboxValue"
                :disabled="readOnly"
                :aria-invalid="error ? 'true' : undefined"
            />
            <input
                v-if="
                    !$slots.default &&
                    ['select', 'combobox', 'remote-select'].includes(field.type)
                "
                type="hidden"
                :name="field.name"
                :value="selectValue ?? ''"
            />
            <Select
                v-if="!$slots.default && field.type === 'select'"
                v-model="selectValue"
                :disabled="readOnly"
            >
                <SelectTrigger
                    :id="idPrefix ? `${idPrefix}-${field.name}` : field.name"
                    class="w-full"
                    :aria-invalid="error ? 'true' : undefined"
                >
                    <SelectValue :placeholder="field.label" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in field.options ?? []"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <CrudCombobox
                v-if="!$slots.default && field.type === 'combobox'"
                v-model="selectValue"
                :id="fieldId"
                :options="field.options ?? []"
                :disabled="readOnly"
                :invalid="Boolean(error)"
                :placeholder="field.label"
            />
            <RemoteCombobox
                v-if="!$slots.default && field.type === 'remote-select'"
                v-model="remoteSelectValue"
                :id="fieldId"
                :remote="field.remote!"
                :placeholder="field.label"
                :disabled="readOnly"
            />
            <Textarea
                v-if="!$slots.default && field.type === 'textarea'"
                :id="idPrefix ? `${idPrefix}-${field.name}` : field.name"
                :name="field.name"
                :required="field.required"
                :disabled="readOnly"
                :aria-invalid="error ? 'true' : undefined"
                v-model="textValue"
            />
            <Input
                v-if="
                    !$slots.default &&
                    ![
                        'array',
                        'checkbox',
                        'select',
                        'combobox',
                        'remote-select',
                        'textarea',
                    ].includes(field.type)
                "
                :id="idPrefix ? `${idPrefix}-${field.name}` : field.name"
                :name="field.name"
                :type="field.type"
                :step="field.step"
                :required="field.required"
                :disabled="readOnly"
                :autocomplete="
                    field.type === 'password' ? 'new-password' : undefined
                "
                :aria-invalid="error ? 'true' : undefined"
                v-model="textValue"
            />
            <input
                v-if="!$slots.default && field.type === 'array' && arrayCleared"
                type="hidden"
                :name="`${field.name}__clear`"
                value="1"
            />
            <button
                v-if="hasClearableValue"
                type="button"
                class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm text-muted-foreground hover:bg-muted hover:text-foreground"
                :aria-label="`Clear ${field.label}`"
                @click="clearValue"
            >
                <X class="size-3.5" />
                Clear
            </button>
            <InputError :message="arrayInputError ?? error" />
        </div>

        <div
            v-if="field.confirmed && !$slots.default && !readOnly"
            :class="['space-y-2', spanClasses(field.span)]"
        >
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
                v-model="confirmationValue"
            />
        </div>
    </div>
</template>
