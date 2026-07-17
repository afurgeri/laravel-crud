<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { CrudField } from '@/types/crud';

defineProps<{
    field: CrudField;
    error?: string;
    defaultValue?: unknown;
    labelClass?: string;
    idPrefix?: string;
}>();

function inputValue(value: unknown): string | number | undefined {
    return typeof value === 'string' || typeof value === 'number'
        ? value
        : undefined;
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
            <Input
                :id="idPrefix ? `${idPrefix}-${field.name}` : field.name"
                :name="field.name"
                :type="field.type"
                :required="field.required"
                :autocomplete="
                    field.type === 'password' ? 'new-password' : undefined
                "
                :aria-invalid="error ? 'true' : undefined"
                :default-value="inputValue(defaultValue)"
            />
            <InputError :message="error" />
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
