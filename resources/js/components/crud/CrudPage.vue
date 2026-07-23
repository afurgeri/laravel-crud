<script setup lang="ts" generic="T extends CrudRecord">
import { Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Eye, Pencil, Plus } from '@lucide/vue';
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
    CrudShowConfig,
} from '@/types/crud';

const props = withDefaults(
    defineProps<{
        schema: CrudSchema;
        records: CrudPaginator<T>;
        create: CrudCreateConfig;
        show?: CrudShowConfig<T>;
        edit: CrudEditConfig<T>;
        destroy: CrudDestroyConfig<T>;
        lockedLabel?: string;
    }>(),
    {
        lockedLabel: 'Locked',
    },
);

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
const isLoading = ref(false);

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
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
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

                <div class="relative mt-5 flex items-center gap-2 md:mt-0">
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
                        {{ create.label ?? 'Create' }}
                    </Link>

                    <CrudFormDialog
                        v-else-if="schema.operations.create && create.can"
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
                        <template #trigger>
                            <Button
                                type="button"
                                class="gap-2 shadow-lg shadow-indigo-500/20"
                            >
                                <Plus class="size-4" />
                                {{ create.label ?? 'Create' }}
                            </Button>
                        </template>
                        <template #fields="slotProps">
                            <slot name="create-fields" v-bind="slotProps" />
                        </template>
                    </CrudFormDialog>
                </div>
            </div>

            <div
                class="rounded-2xl border border-border/70 bg-card p-1 shadow-[0_10px_30px_-24px_rgba(15,23,42,0.45)]"
            >
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

            <CrudTable
                :columns="schema.columns"
                :records="records.data"
                :sort="schema.sort"
                :loading="isLoading"
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

                        <Link
                            v-if="show && canShowRecord(record)"
                            :href="show.href(record)"
                            class="inline-flex size-8 items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
                            :aria-label="show.label ?? 'Show'"
                            :title="show.title?.(record) ?? 'Show'"
                        >
                            <Eye class="size-4" />
                        </Link>

                        <Link
                            v-if="
                                canEditRecord(record) &&
                                usesFullPageForms() &&
                                edit.href
                            "
                            :href="edit.href(record)"
                            class="inline-flex size-8 items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
                            :aria-label="edit.label ?? 'Edit'"
                        >
                            <Pencil class="size-4" />
                        </Link>

                        <CrudFormDialog
                            v-else-if="canEditRecord(record)"
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
                                !canShowRecord(record) &&
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
