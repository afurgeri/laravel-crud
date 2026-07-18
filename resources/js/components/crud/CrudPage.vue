<script setup lang="ts" generic="T extends CrudRecord">
import { router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Pencil } from '@lucide/vue';
import { reactive, ref } from 'vue';
import CrudDeleteDialog from '@/components/crud/CrudDeleteDialog.vue';
import CrudFilters from '@/components/crud/CrudFilters.vue';
import CrudFormDialog from '@/components/crud/CrudFormDialog.vue';
import CrudTable from '@/components/crud/CrudTable.vue';
import { Button } from '@/components/ui/button';
import { TooltipProvider } from '@/components/ui/tooltip';
import type {
    CrudCreateConfig,
    CrudDestroyConfig,
    CrudEditConfig,
    CrudPaginator,
    CrudRecord,
    CrudSchema,
} from '@/types/crud';

const props = withDefaults(
    defineProps<{
        schema: CrudSchema;
        records: CrudPaginator<T>;
        create: CrudCreateConfig;
        edit: CrudEditConfig<T>;
        destroy: CrudDestroyConfig<T>;
        lockedLabel?: string;
    }>(),
    {
        lockedLabel: 'Locked',
    },
);

function canEditRecord(record: T): boolean {
    return props.edit.can ? props.edit.can(record) : true;
}

function canDestroyRecord(record: T): boolean {
    return props.destroy.can ? props.destroy.can(record) : true;
}

function editRecordTitle(record: T): string {
    if (props.edit.title) {
        return props.edit.title(record);
    }

    return `Edit ${String(record.id)}`;
}

function destroyRecordTitle(record: T): string {
    if (props.destroy.title) {
        return props.destroy.title(record);
    }

    return 'Delete this record?';
}

function recordFieldPrefix(record: T): string {
    return `${props.schema.resource}-${String(record.id)}`;
}

const sortState = reactive({
    column: props.schema.sort?.column ?? null,
    direction: props.schema.sort?.direction ?? 'asc',
});

const searchValue = ref(props.schema.search.value ?? '');

const filterValues = reactive<Record<string, string>>(
    Object.fromEntries(
        props.schema.filters.map((filter) => [
            filter.name,
            filterSchemaValue(filter),
        ]),
    ),
);

let navigateTimer: ReturnType<typeof setTimeout> | undefined;

type CrudQuery = {
    page?: number;
    sort?: string;
    direction?: 'asc' | 'desc';
    search?: string;
    filters?: Record<string, string>;
};

function filterSchemaValue(filter: CrudSchema['filters'][number]): string {
    return typeof filter.value === 'string' ? filter.value : '';
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
        Object.entries(filterValues).filter(([, value]) => value !== ''),
    );

    if (Object.keys(activeFilters).length > 0) {
        query.filters = activeFilters;
    }

    router.get(window.location.pathname, query, {
        preserveScroll: true,
        preserveState: true,
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

function handleFilter(name: string, value: string, immediate: boolean): void {
    filterValues[name] = value;

    if (immediate) {
        navigate();

        return;
    }

    navigateDebounced();
}

function handleClearFilters(): void {
    searchValue.value = '';

    for (const key of Object.keys(filterValues)) {
        filterValues[key] = '';
    }

    navigate();
}
</script>

<template>
    <TooltipProvider :delay-duration="0">
        <div class="flex flex-col gap-6 p-4">
            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
            >
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight">
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

                    <CrudFormDialog
                        v-if="create.can"
                        :action="create.action"
                        :fields="schema.fields"
                        :trigger-label="create.label ?? 'Create'"
                        :title="create.title ?? create.label ?? 'Create'"
                        :description="create.description"
                        :submit-label="
                            create.submitLabel ?? create.label ?? 'Create'
                        "
                        reset-on-success
                        :field-id-prefix="`${schema.resource}-create`"
                    >
                        <template #fields="slotProps">
                            <slot name="create-fields" v-bind="slotProps" />
                        </template>
                    </CrudFormDialog>
                </div>
            </div>

            <CrudFilters
                :search="schema.search"
                :filters="schema.filters"
                :search-value="searchValue"
                :filter-values="filterValues"
                @search="handleSearch"
                @filter="handleFilter"
                @clear="handleClearFilters"
            />

            <CrudTable
                :columns="schema.columns"
                :records="records.data"
                :sort="schema.sort"
                :empty-label="schema.empty_label ?? 'No records found.'"
                @sort="handleSort"
            >
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

                        <CrudFormDialog
                            v-if="canEditRecord(record)"
                            :action="edit.action(record)"
                            :fields="
                                schema.fields.filter(
                                    (field) => field.visible_on_update,
                                )
                            "
                            :defaults="record"
                            :trigger-label="edit.label ?? 'Edit'"
                            :trigger-tooltip="edit.label ?? 'Edit'"
                            :title="editRecordTitle(record)"
                            :description="edit.description"
                            :submit-label="edit.submitLabel ?? 'Save changes'"
                            :field-id-prefix="recordFieldPrefix(record)"
                        >
                            <template #trigger>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    size="icon-sm"
                                    :aria-label="edit.label ?? 'Edit'"
                                >
                                    <Pencil class="size-4" />
                                </Button>
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
                            :trigger-label="destroy.label ?? 'Delete'"
                            :title="destroyRecordTitle(record)"
                            :description="
                                destroy.description ??
                                'This action cannot be undone.'
                            "
                            :confirm-label="
                                destroy.confirmLabel ??
                                destroy.label ??
                                'Delete'
                            "
                            :cancel-label="destroy.cancelLabel ?? 'Cancel'"
                        />

                        <span
                            v-if="
                                !canEditRecord(record) &&
                                !canDestroyRecord(record)
                            "
                            class="text-sm text-muted-foreground"
                        >
                            {{ lockedLabel }}
                        </span>

                        <slot name="actions-after" :record="record" />
                    </div>
                </template>
            </CrudTable>

            <div
                v-if="records.last_page > 1"
                class="flex flex-col gap-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between"
            >
                <span>
                    Showing {{ records.from ?? 0 }} to {{ records.to ?? 0 }} of
                    {{ records.total }}
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
                        Previous
                    </Button>

                    <span class="whitespace-nowrap">
                        Page {{ records.current_page }} of
                        {{ records.last_page }}
                    </span>

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="records.current_page === records.last_page"
                        @click="goToPage(records.current_page + 1)"
                    >
                        Next
                        <ChevronRight class="size-4" />
                    </Button>
                </div>
            </div>
        </div>
    </TooltipProvider>
</template>
