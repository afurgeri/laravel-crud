<script setup lang="ts" generic="T extends CrudRecord">
import { Link, router } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Eye,
    Pencil,
    Plus,
    SlidersHorizontal,
} from '@lucide/vue';
import { computed, reactive, ref, useSlots } from 'vue';
import CrudDeleteDialog from '@/components/crud/CrudDeleteDialog.vue';
import CrudFilters from '@/components/crud/CrudFilters.vue';
import CrudFormDialog from '@/components/crud/CrudFormDialog.vue';
import CrudTable from '@/components/crud/CrudTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useTranslation } from '@/composables/useTranslation';
import type {
    CrudCreateConfig,
    CrudDestroyConfig,
    CrudEditConfig,
    CrudPaginator,
    CrudRecord,
    CrudSchema,
    CrudShowConfig,
} from '@/types/crud';

type CrudFilterValue = string | string[];

const props = withDefaults(
    defineProps<{
        schema: CrudSchema;
        records: CrudPaginator<T>;
        create: CrudCreateConfig;
        show?: CrudShowConfig<T>;
        edit: CrudEditConfig<T>;
        destroy: CrudDestroyConfig<T>;
        lockedLabel?: string;
        workspace?: string;
    }>(),
    {
        lockedLabel: undefined,
        workspace: undefined,
    },
);

const { t } = useTranslation();
const slots = useSlots();

function hasSlot(name: string): boolean {
    return Boolean(slots[name]);
}

const layoutWidthClasses = {
    standard: 'max-w-7xl',
    wide: 'max-w-screen-2xl',
    full: 'max-w-none',
} as const;

function canEditRecord(record: T): boolean {
    return (
        props.schema.operations.update &&
        (props.edit.can ? props.edit.can(record) : true)
    );
}

function canDestroyRecord(record: T): boolean {
    return (
        props.schema.operations.delete &&
        (props.destroy.can ? props.destroy.can(record) : true)
    );
}

function canShowRecord(record: T): boolean {
    return (
        props.schema.operations.show &&
        (props.show?.can ? props.show.can(record) : true)
    );
}

function usesFullPageForms(): boolean {
    return props.schema.form_mode === 'page';
}

function editRecordTitle(record: T): string {
    if (props.edit.title) {
        return props.edit.title(record);
    }

    return t('Edit :id', { id: String(record.id) });
}

function destroyRecordTitle(record: T): string {
    if (props.destroy.title) {
        return props.destroy.title(record);
    }

    return t('Delete this record?');
}

function recordFieldPrefix(record: T): string {
    return `${props.schema.resource}-${String(record.id)}`;
}

const sortState = reactive({
    column: props.schema.sort?.column ?? null,
    direction: props.schema.sort?.direction ?? 'asc',
});

const searchValue = ref(props.schema.search.value ?? '');
const isLoading = ref(false);
const filtersOpen = ref(true);

const filterValues = reactive<Record<string, CrudFilterValue>>(
    Object.fromEntries(
        props.schema.filters.map((filter) => [
            filter.name,
            filterSchemaValue(filter),
        ]),
    ),
);

const activeFilterCount = computed(
    () =>
        Object.values(filterValues).filter(hasFilterValue).length +
        (searchValue.value !== '' ? 1 : 0),
);

let navigateTimer: ReturnType<typeof setTimeout> | undefined;

type CrudQuery = {
    page?: number;
    sort?: string;
    direction?: 'asc' | 'desc';
    search?: string;
    filters?: Record<string, CrudFilterValue>;
};

function filterSchemaValue(
    filter: CrudSchema['filters'][number],
): CrudFilterValue {
    if (filter.multiple && Array.isArray(filter.value)) {
        return filter.value.filter(
            (value): value is string => typeof value === 'string',
        );
    }

    return typeof filter.value === 'string' ? filter.value : '';
}

function hasFilterValue(value: CrudFilterValue): boolean {
    return Array.isArray(value) ? value.length > 0 : value !== '';
}

function navigate(page = 1): void {
    clearTimeout(navigateTimer);

    const query: CrudQuery = {};

    if (page > 1) {
        query.page = page;
    }

    if (sortState.column) {
        query.sort = sortState.column;
        query.direction = sortState.direction;
    }

    if (searchValue.value) {
        query.search = searchValue.value;
    }

    const activeFilters = Object.fromEntries(
        Object.entries(filterValues).filter(([, value]) => hasFilterValue(value)),
    );

    if (Object.keys(activeFilters).length > 0) {
        query.filters = activeFilters;
    }

    router.get(window.location.pathname, query, {
        preserveScroll: true,
        preserveState: true,
        onStart: () => {
            isLoading.value = true;
        },
        onFinish: () => {
            isLoading.value = false;
        },
    });
}

function goToPage(page: number): void {
    if (
        page < 1 ||
        page > props.records.last_page ||
        page === props.records.current_page
    ) {
        return;
    }

    navigate(page);
}

function navigateDebounced(): void {
    clearTimeout(navigateTimer);
    navigateTimer = setTimeout(navigate, 400);
}

function handleSort(column: string): void {
    sortState.direction =
        sortState.column === column && sortState.direction === 'asc'
            ? 'desc'
            : 'asc';
    sortState.column = column;

    navigate();
}

function handleSearch(value: string): void {
    searchValue.value = value;
    navigateDebounced();
}

function handleFilter(
    name: string,
    value: CrudFilterValue,
    immediate: boolean,
): void {
    filterValues[name] = value;

    if (immediate) {
        navigate();

        return;
    }

    navigateDebounced();
}

function handleClearFilters(): void {
    searchValue.value = '';

    for (const filter of props.schema.filters) {
        filterValues[filter.name] = filter.multiple ? [] : '';
    }

    navigate();
}
</script>

<template>
    <TooltipProvider :delay-duration="0">
        <div
            :class="[
                'mx-auto flex w-full flex-col gap-5 p-4 sm:p-6 lg:p-8',
                layoutWidthClasses[schema.page_width],
            ]"
        >
            <div
                class="flex flex-col gap-4 px-1 py-2 md:flex-row md:items-center md:justify-between"
            >
                <div class="space-y-2">
                    <p
                        v-if="workspace"
                        class="text-[11px] font-semibold tracking-[0.18em] text-primary uppercase"
                    >
                        {{ t(workspace ?? '') }}
                    </p>
                    <h1
                        class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl"
                    >
                        {{ schema.title }}
                    </h1>
                    <p
                        v-if="schema.description"
                        class="text-sm text-muted-foreground"
                    >
                        {{ schema.description }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <slot name="toolbar-actions" />

                    <Link
                        v-if="
                            schema.operations.create &&
                            create.can &&
                            usesFullPageForms() &&
                            create.href
                        "
                        :href="create.href"
                        class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium whitespace-nowrap text-primary-foreground shadow-lg shadow-indigo-500/20 transition-all hover:bg-primary/90"
                    >
                        <Plus class="size-4" />
                        {{ create.label ?? t('Create') }}
                    </Link>

                    <CrudFormDialog
                        v-else-if="schema.operations.create && create.can"
                        :action="create.action"
                        :fields="schema.fields"
                        :trigger-label="create.label ?? t('Create')"
                        :title="create.title ?? create.label ?? t('Create')"
                        :description="create.description"
                        :submit-label="
                            create.submitLabel ?? create.label ?? t('Create')
                        "
                        reset-on-success
                        :field-id-prefix="`${schema.resource}-create`"
                    >
                        <template #trigger>
                            <Button
                                type="button"
                                class="gap-2 shadow-lg shadow-indigo-500/20"
                            >
                                <Plus class="size-4" />
                                {{ create.label ?? t('Create') }}
                            </Button>
                        </template>
                        <template
                            v-for="field in schema.fields.filter((field) =>
                                hasSlot(`create-field-${field.name}`),
                            )"
                            :key="field.name"
                            #[`field-${field.name}`]="slotProps"
                        >
                            <slot
                                :name="`create-field-${field.name}`"
                                v-bind="slotProps"
                            />
                        </template>
                        <template #fields="slotProps">
                            <slot name="create-fields" v-bind="slotProps" />
                        </template>
                    </CrudFormDialog>
                </div>
            </div>

            <CrudTable
                :columns="schema.columns"
                :records="records.data"
                :sort="schema.sort"
                :loading="isLoading"
                :empty-label="schema.empty_label ?? t('No records found.')"
                @sort="handleSort"
            >
                <template
                    v-if="schema.search?.enabled || schema.filters?.length > 0"
                    #toolbar
                >
                    <div
                        class="flex items-center justify-end border-b border-border/70 px-3 py-2 sm:px-4"
                    >
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            :aria-expanded="filtersOpen"
                            :aria-controls="`${schema.resource}-filters`"
                            class="gap-2 text-muted-foreground hover:text-foreground"
                            @click="filtersOpen = !filtersOpen"
                        >
                            <SlidersHorizontal class="size-3.5" />
                            {{ t('Filters') }}
                            <Badge
                                v-if="activeFilterCount > 0"
                                variant="secondary"
                                class="min-w-5 justify-center rounded-full px-1.5 text-[10px]"
                            >
                                {{ activeFilterCount }}
                            </Badge>
                            <ChevronDown
                                class="size-3.5 transition-transform duration-300 ease-out"
                                :class="filtersOpen && 'rotate-180'"
                            />
                        </Button>
                    </div>

                    <Transition
                        enter-active-class="grid overflow-hidden transition-[grid-template-rows] duration-300 ease-out"
                        enter-from-class="grid-rows-[0fr]"
                        enter-to-class="grid-rows-[1fr]"
                        leave-active-class="grid overflow-hidden transition-[grid-template-rows] duration-300 ease-in"
                        leave-from-class="grid-rows-[1fr]"
                        leave-to-class="grid-rows-[0fr]"
                    >
                        <div
                            v-show="filtersOpen"
                            :id="`${schema.resource}-filters`"
                            class="grid min-h-0 border-b border-border/70"
                        >
                            <div class="min-h-0 overflow-hidden">
                                <CrudFilters
                                    :search="schema.search"
                                    :filters="schema.filters"
                                    :search-value="searchValue"
                                    :filter-values="filterValues"
                                    @search="handleSearch"
                                    @filter="handleFilter"
                                    @clear="handleClearFilters"
                                />
                            </div>
                        </div>
                    </Transition>
                </template>

                <template
                    v-for="column in schema.columns"
                    :key="column.name"
                    #[`cell-${column.name}`]="slotProps"
                >
                    <slot :name="`cell-${column.name}`" v-bind="slotProps">
                        {{ slotProps.value }}
                    </slot>
                </template>

                <template #actions="{ record }">
                    <div class="flex items-center justify-end gap-2">
                        <slot name="actions-before" :record="record" />

                        <Tooltip
                            v-if="show && canShowRecord(record)"
                            :ignore-non-keyboard-focus="true"
                        >
                            <TooltipTrigger as-child>
                                <Link
                                    :href="show.href(record)"
                                    class="inline-flex size-8 items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
                                    :aria-label="show.label ?? t('Show')"
                                    :title="show.title?.(record) ?? t('Show')"
                                >
                                    <Eye class="size-4" />
                                </Link>
                            </TooltipTrigger>
                            <TooltipContent>{{
                                show.label ?? t('Show')
                            }}</TooltipContent>
                        </Tooltip>

                        <Tooltip
                            v-if="
                                canEditRecord(record) &&
                                usesFullPageForms() &&
                                edit.href
                            "
                            :ignore-non-keyboard-focus="true"
                        >
                            <TooltipTrigger as-child>
                                <Link
                                    :href="edit.href(record)"
                                    class="inline-flex size-8 items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
                                    :aria-label="edit.label ?? t('Edit')"
                                    :title="editRecordTitle(record)"
                                >
                                    <Pencil class="size-4" />
                                </Link>
                            </TooltipTrigger>
                            <TooltipContent>{{
                                edit.label ?? t('Edit')
                            }}</TooltipContent>
                        </Tooltip>

                        <CrudFormDialog
                            v-else-if="canEditRecord(record)"
                            :action="edit.action(record)"
                            :fields="
                                schema.fields.filter(
                                    (field) => field.visible_on_update,
                                )
                            "
                            :initial-values="record"
                            :trigger-label="edit.label ?? t('Edit')"
                            :trigger-tooltip="edit.label ?? t('Edit')"
                            :title="editRecordTitle(record)"
                            :description="edit.description"
                            :submit-label="
                                edit.submitLabel ?? t('Save changes')
                            "
                            :field-id-prefix="recordFieldPrefix(record)"
                        >
                            <template #trigger>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    size="icon-sm"
                                    :aria-label="edit.label ?? t('Edit')"
                                >
                                    <Pencil class="size-4" />
                                </Button>
                            </template>
                            <template
                                v-for="field in schema.fields.filter(
                                    (field) =>
                                        field.visible_on_update &&
                                        hasSlot(`edit-field-${field.name}`),
                                )"
                                :key="field.name"
                                #[`field-${field.name}`]="slotProps"
                            >
                                <slot
                                    :name="`edit-field-${field.name}`"
                                    :record="record"
                                    v-bind="slotProps"
                                />
                            </template>

                            <template #fields="slotProps">
                                <slot
                                    name="edit-fields"
                                    :record="record"
                                    v-bind="slotProps"
                                />
                            </template>
                        </CrudFormDialog>

                        <CrudDeleteDialog
                            v-if="canDestroyRecord(record)"
                            :action="destroy.action(record)"
                            :trigger-label="destroy.label ?? t('Delete')"
                            :title="destroyRecordTitle(record)"
                            :description="
                                destroy.description ??
                                t('This action cannot be undone.')
                            "
                            :confirm-label="
                                destroy.confirmLabel ??
                                destroy.label ??
                                t('Delete')
                            "
                            :cancel-label="destroy.cancelLabel ?? t('Cancel')"
                        />

                        <span
                            v-if="
                                !canShowRecord(record) &&
                                !canEditRecord(record) &&
                                !canDestroyRecord(record)
                            "
                            class="text-sm text-muted-foreground"
                        >
                            {{ lockedLabel ?? t('Locked') }}
                        </span>

                        <slot name="actions-after" :record="record" />
                    </div>
                </template>
                <template #footer>
                    <div
                        v-if="records.last_page > 1"
                        class="flex flex-col gap-3 px-4 py-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between"
                    >
                        <span>
                            {{
                                t('Showing :from to :to of :count', {
                                    from: records.from ?? 0,
                                    to: records.to ?? 0,
                                    count: records.total,
                                })
                            }}
                        </span>

                        <div
                            class="flex items-center justify-between gap-3 sm:justify-end"
                        >
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="records.current_page === 1"
                                @click="goToPage(records.current_page - 1)"
                            >
                                <ChevronLeft class="size-4" />
                                {{ t('Previous') }}
                            </Button>

                            <span class="whitespace-nowrap">
                                {{
                                    t('Page :current of :last', {
                                        current: records.current_page,
                                        last: records.last_page,
                                    })
                                }}
                            </span>

                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="
                                    records.current_page === records.last_page
                                "
                                @click="goToPage(records.current_page + 1)"
                            >
                                {{ t('Next') }}
                                <ChevronRight class="size-4" />
                            </Button>
                        </div>
                    </div>
                </template>
            </CrudTable>
        </div>
    </TooltipProvider>
</template>
