<script setup lang="ts">
import { X } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { CrudFilter, CrudSearch } from '@/types/crud';

const props = defineProps<{
    search: CrudSearch;
    filters: CrudFilter[];
    searchValue: string;
    filterValues: Record<string, string>;
    clearLabel?: string;
}>();

const emit = defineEmits<{
    search: [value: string];
    filter: [name: string, value: string, immediate: boolean];
    clear: [];
}>();

type FilterEntry =
    | { kind: 'single'; filter: CrudFilter }
    | { kind: 'range'; group: string; from: CrudFilter; to: CrudFilter };

const entries = computed<FilterEntry[]>(() => {
    const result: FilterEntry[] = [];
    const seenGroups = new Set<string>();

    for (const filter of props.filters) {
        if (filter.range === null) {
            result.push({ kind: 'single', filter });
            continue;
        }

        if (seenGroups.has(filter.range)) {
            continue;
        }

        seenGroups.add(filter.range);

        const pair = props.filters.filter((f) => f.range === filter.range);
        const from = pair.find(
            (f) => f.operator === '>' || f.operator === '>=',
        );
        const to = pair.find((f) => f.operator === '<' || f.operator === '<=');

        if (from && to) {
            result.push({ kind: 'range', group: filter.range, from, to });
        } else {
            result.push({ kind: 'single', filter });
        }
    }

    return result;
});

const hasActiveFilters = computed(
    () =>
        props.searchValue !== '' ||
        Object.values(props.filterValues).some((value) => value !== ''),
);

function inputType(filter: CrudFilter): string {
    return filter.type === 'number' ? 'number' : 'text';
}

function rangeInvalid(entry: { from: CrudFilter; to: CrudFilter }): boolean {
    const from = props.filterValues[entry.from.name];
    const to = props.filterValues[entry.to.name];

    return Boolean(from && to && from > to);
}

function clearFilter(name: string): void {
    emit('filter', name, '', true);
}
</script>

<template>
    <div
        v-if="search.enabled || filters.length > 0"
        class="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 bg-card p-4 shadow-sm dark:border-sidebar-border"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end">
            <div
                v-if="search.enabled"
                class="flex w-full flex-col gap-2 sm:w-auto"
            >
                <Label for="crud-search">Search</Label>
                <Input
                    id="crud-search"
                    :model-value="searchValue"
                    type="search"
                    placeholder="Search..."
                    class="w-full sm:w-56"
                    @update:model-value="
                        (value) => emit('search', String(value))
                    "
                />
            </div>

            <template
                v-for="entry in entries"
                :key="entry.kind === 'single' ? entry.filter.name : entry.group"
            >
                <div
                    v-if="entry.kind === 'single'"
                    class="flex w-full flex-col gap-2 sm:w-auto"
                >
                    <Label :for="`filter-${entry.filter.name}`">{{
                        entry.filter.label
                    }}</Label>

                    <div class="flex w-full items-center gap-1 sm:w-48">
                        <Select
                            v-if="entry.filter.type === 'select'"
                            :model-value="
                                filterValues[entry.filter.name] || undefined
                            "
                            @update:model-value="
                                (value) =>
                                    emit(
                                        'filter',
                                        entry.filter.name,
                                        String(value ?? ''),
                                        true,
                                    )
                            "
                        >
                            <SelectTrigger
                                :id="`filter-${entry.filter.name}`"
                                class="w-full"
                            >
                                <SelectValue
                                    :placeholder="entry.filter.label"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in entry.filter.options ?? []"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <DatePicker
                            v-else-if="entry.filter.type === 'date'"
                            :id="`filter-${entry.filter.name}`"
                            :model-value="filterValues[entry.filter.name] ?? ''"
                            :max-value="entry.filter.max_date ?? undefined"
                            class="w-full"
                            @update:model-value="
                                (value) =>
                                    emit(
                                        'filter',
                                        entry.filter.name,
                                        value,
                                        false,
                                    )
                            "
                        />

                        <Input
                            v-else
                            :id="`filter-${entry.filter.name}`"
                            :model-value="filterValues[entry.filter.name] ?? ''"
                            :type="inputType(entry.filter)"
                            class="w-full"
                            @update:model-value="
                                (value) =>
                                    emit(
                                        'filter',
                                        entry.filter.name,
                                        String(value),
                                        false,
                                    )
                            "
                        />

                        <Button
                            v-if="
                                entry.filter.clearable &&
                                filterValues[entry.filter.name]
                            "
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            class="shrink-0"
                            :aria-label="`Clear ${entry.filter.label}`"
                            @click="clearFilter(entry.filter.name)"
                        >
                            <X class="size-4" />
                        </Button>
                    </div>
                </div>

                <div
                    v-else
                    class="flex w-full flex-col gap-4 sm:w-auto sm:flex-row sm:items-end sm:gap-2"
                >
                    <div class="flex w-full flex-col gap-2 sm:w-auto">
                        <Label :for="`filter-${entry.from.name}`">{{
                            entry.from.label
                        }}</Label>
                        <div class="flex w-full items-center gap-1 sm:w-40">
                            <DatePicker
                                :id="`filter-${entry.from.name}`"
                                :model-value="
                                    filterValues[entry.from.name] ?? ''
                                "
                                :max-value="entry.from.max_date ?? undefined"
                                :aria-invalid="rangeInvalid(entry)"
                                class="w-full"
                                @update:model-value="
                                    (value) =>
                                        emit(
                                            'filter',
                                            entry.from.name,
                                            value,
                                            false,
                                        )
                                "
                            />
                            <Button
                                v-if="
                                    entry.from.clearable &&
                                    filterValues[entry.from.name]
                                "
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                class="shrink-0"
                                :aria-label="`Clear ${entry.from.label}`"
                                @click="clearFilter(entry.from.name)"
                            >
                                <X class="size-4" />
                            </Button>
                        </div>
                    </div>

                    <div class="flex w-full flex-col gap-2 sm:w-auto">
                        <Label :for="`filter-${entry.to.name}`">{{
                            entry.to.label
                        }}</Label>
                        <div class="flex w-full items-center gap-1 sm:w-40">
                            <DatePicker
                                :id="`filter-${entry.to.name}`"
                                :model-value="filterValues[entry.to.name] ?? ''"
                                :max-value="entry.to.max_date ?? undefined"
                                :aria-invalid="rangeInvalid(entry)"
                                class="w-full"
                                @update:model-value="
                                    (value) =>
                                        emit(
                                            'filter',
                                            entry.to.name,
                                            value,
                                            false,
                                        )
                                "
                            />
                            <Button
                                v-if="
                                    entry.to.clearable &&
                                    filterValues[entry.to.name]
                                "
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                class="shrink-0"
                                :aria-label="`Clear ${entry.to.label}`"
                                @click="clearFilter(entry.to.name)"
                            >
                                <X class="size-4" />
                            </Button>
                        </div>
                    </div>

                    <p
                        v-if="rangeInvalid(entry)"
                        class="text-sm text-destructive sm:pb-2"
                    >
                        The start of the range cannot be after the end.
                    </p>
                </div>
            </template>

            <Button
                v-if="hasActiveFilters"
                type="button"
                variant="ghost"
                size="sm"
                class="w-full sm:w-auto"
                @click="emit('clear')"
            >
                {{ clearLabel ?? 'Clear filters' }}
            </Button>
        </div>
    </div>
</template>
