<script setup lang="ts">
import { Search, SlidersHorizontal, X } from '@lucide/vue';
import { computed } from 'vue';
import CrudCombobox from '@/components/crud/CrudCombobox.vue';
import RemoteCombobox from '@/components/crud/RemoteCombobox.vue';
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
import { useTranslation } from '@/composables/useTranslation';
import type { CrudFilter, CrudSearch, CrudSpan } from '@/types/crud';

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

const { t } = useTranslation();

const spanClassesByBreakpoint: Record<keyof CrudSpan, string[]> = {
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

function spanClasses(span: CrudSpan): string[] {
    return (Object.entries(span) as [keyof CrudSpan, number][]).flatMap(
        ([breakpoint, columns]) =>
            spanClassesByBreakpoint[breakpoint]?.[columns - 1] ?? [],
    );
}

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
    <div>
        <div
            v-if="search.enabled || filters.length > 0"
            class="flex flex-col gap-4 rounded-xl bg-card p-4"
        >
            <div class="grid grid-cols-12 items-start gap-4">
                <div
                    v-if="search.enabled"
                    :class="[
                        'flex w-full flex-col gap-2',
                        spanClasses(search.span),
                    ]"
                >
                    <Label
                        for="crud-search"
                        class="flex items-center gap-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        ><Search class="size-3.5" /> {{ t('Search') }}</Label
                    >
                    <Input
                        id="crud-search"
                        :model-value="searchValue"
                        type="search"
                        :placeholder="t('Search records...')"
                        class="w-full bg-muted/40"
                        @update:model-value="
                            (value) => emit('search', String(value))
                        "
                    />
                </div>

                <template
                    v-for="entry in entries"
                    :key="
                        entry.kind === 'single'
                            ? entry.filter.name
                            : entry.group
                    "
                >
                    <div
                        v-if="entry.kind === 'single'"
                        :class="[
                            'flex w-full flex-col gap-2',
                            spanClasses(entry.filter.span),
                        ]"
                    >
                        <Label
                            :for="`filter-${entry.filter.name}`"
                            class="flex items-center gap-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            ><SlidersHorizontal class="size-3.5" />{{
                                t(entry.filter.label)
                            }}</Label
                        >

                        <div class="flex w-full items-center gap-1">
                            <RemoteCombobox
                                v-if="entry.filter.type === 'remote-select'"
                                :id="`filter-${entry.filter.name}`"
                                :model-value="
                                    filterValues[entry.filter.name] || ''
                                "
                                :remote="entry.filter.remote!"
                                :placeholder="entry.filter.label"
                                @update:model-value="
                                    (value) =>
                                        emit(
                                            'filter',
                                            entry.filter.name,
                                            value,
                                            true,
                                        )
                                "
                            />
                            <CrudCombobox
                                v-else-if="entry.filter.type === 'combobox'"
                                :id="`filter-${entry.filter.name}`"
                                :model-value="
                                    filterValues[entry.filter.name] || undefined
                                "
                                :options="entry.filter.options ?? []"
                                :placeholder="entry.filter.label"
                                @update:model-value="
                                    (value) =>
                                        emit(
                                            'filter',
                                            entry.filter.name,
                                            value ?? '',
                                            true,
                                        )
                                "
                            />
                            <Select
                                v-else-if="entry.filter.type === 'select'"
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
                                        :placeholder="t(entry.filter.label)"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in entry.filter.options ??
                                        []"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ t(option.label) }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>

                            <DatePicker
                                v-else-if="entry.filter.type === 'date'"
                                :id="`filter-${entry.filter.name}`"
                                :model-value="
                                    filterValues[entry.filter.name] ?? ''
                                "
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
                                :model-value="
                                    filterValues[entry.filter.name] ?? ''
                                "
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
                                :aria-label="
                                    t('Clear :label', {
                                        label: entry.filter.label,
                                    })
                                "
                                @click="clearFilter(entry.filter.name)"
                            >
                                <X class="size-4" />
                            </Button>
                        </div>
                    </div>

                    <div v-else :class="['contents']">
                        <div
                            :class="[
                                'flex w-full min-w-0 flex-col gap-2 self-start',
                                spanClasses(entry.from.span),
                            ]"
                        >
                            <Label :for="`filter-${entry.from.name}`">{{
                                t(entry.from.label)
                            }}</Label>
                            <div class="flex w-full items-center gap-1">
                                <DatePicker
                                    :id="`filter-${entry.from.name}`"
                                    :model-value="
                                        filterValues[entry.from.name] ?? ''
                                    "
                                    :max-value="
                                        entry.from.max_date ?? undefined
                                    "
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
                                    :aria-label="
                                        t('Clear :label', {
                                            label: entry.from.label,
                                        })
                                    "
                                    @click="clearFilter(entry.from.name)"
                                >
                                    <X class="size-4" />
                                </Button>
                            </div>
                            <p
                                v-if="rangeInvalid(entry)"
                                class="text-sm text-destructive"
                            >
                                {{
                                    t(
                                        'The start of the range cannot be after the end.',
                                    )
                                }}
                            </p>
                        </div>

                        <div
                            :class="[
                                'flex w-full min-w-0 flex-col gap-2 self-start',
                                spanClasses(entry.to.span),
                            ]"
                        >
                            <Label :for="`filter-${entry.to.name}`">{{
                                t(entry.to.label)
                            }}</Label>
                            <div class="flex w-full items-center gap-1">
                                <DatePicker
                                    :id="`filter-${entry.to.name}`"
                                    :model-value="
                                        filterValues[entry.to.name] ?? ''
                                    "
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
                                    :aria-label="
                                        t('Clear :label', {
                                            label: entry.to.label,
                                        })
                                    "
                                    @click="clearFilter(entry.to.name)"
                                >
                                    <X class="size-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </template>

                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    :disabled="!hasActiveFilters"
                    :aria-hidden="!hasActiveFilters"
                    :class="[
                        'col-span-full w-auto shrink-0 justify-self-end',
                        !hasActiveFilters && 'invisible',
                    ]"
                    @click="emit('clear')"
                >
                    {{ clearLabel ?? t('Clear filters') }}
                </Button>
            </div>
        </div>
    </div>
</template>
