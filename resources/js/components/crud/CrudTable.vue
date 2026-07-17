<script setup lang="ts" generic="T extends CrudRecord">
import { ChevronDown, ChevronUp } from '@lucide/vue';
import type { CrudColumn, CrudRecord, CrudSort } from '@/types/crud';

defineProps<{
    columns: CrudColumn[];
    records: T[];
    sort?: CrudSort;
    actionsLabel?: string;
    emptyLabel?: string;
}>();

defineEmits<{
    sort: [column: string];
}>();
</script>

<template>
    <div>
        <div
            class="hidden overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm md:block dark:border-sidebar-border"
        >
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/40 text-muted-foreground">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.name"
                            class="px-4 py-3 font-medium"
                        >
                            <button
                                v-if="column.sortable"
                                type="button"
                                class="inline-flex items-center gap-1 hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                @click="$emit('sort', column.name)"
                            >
                                {{ column.label }}
                                <ChevronUp
                                    v-if="
                                        sort?.column === column.name &&
                                        sort?.direction === 'asc'
                                    "
                                    class="size-3.5"
                                />
                                <ChevronDown
                                    v-else-if="
                                        sort?.column === column.name &&
                                        sort?.direction === 'desc'
                                    "
                                    class="size-3.5"
                                />
                            </button>
                            <template v-else>
                                {{ column.label }}
                            </template>
                        </th>
                        <th class="px-4 py-3 text-right font-medium">
                            {{ actionsLabel ?? 'Actions' }}
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border"
                >
                    <tr v-for="record in records" :key="record.id">
                        <td
                            v-for="column in columns"
                            :key="column.name"
                            class="px-4 py-3"
                        >
                            <slot
                                :name="`cell-${column.name}`"
                                :column="column"
                                :record="record"
                                :value="record[column.name]"
                            >
                                {{ record[column.name] }}
                            </slot>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <slot name="actions" :record="record" />
                        </td>
                    </tr>
                    <tr v-if="records.length === 0">
                        <td
                            :colspan="columns.length + 1"
                            class="px-4 py-6 text-muted-foreground"
                        >
                            {{ emptyLabel ?? 'No records found.' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-3 md:hidden">
            <div
                v-for="record in records"
                :key="record.id"
                class="rounded-xl border border-sidebar-border/70 bg-card p-4 shadow-sm dark:border-sidebar-border"
            >
                <dl class="flex flex-col gap-2">
                    <div
                        v-for="column in columns"
                        :key="column.name"
                        class="flex items-baseline justify-between gap-4"
                    >
                        <dt
                            class="shrink-0 text-xs font-medium text-muted-foreground"
                        >
                            {{ column.label }}
                        </dt>
                        <dd class="text-right text-sm break-words">
                            <slot
                                :name="`cell-${column.name}`"
                                :column="column"
                                :record="record"
                                :value="record[column.name]"
                            >
                                {{ record[column.name] }}
                            </slot>
                        </dd>
                    </div>
                </dl>

                <div
                    class="mt-3 flex flex-wrap items-center justify-end gap-2 border-t border-sidebar-border/70 pt-3 dark:border-sidebar-border"
                >
                    <slot name="actions" :record="record" />
                </div>
            </div>

            <div
                v-if="records.length === 0"
                class="rounded-xl border border-sidebar-border/70 bg-card p-6 text-center text-muted-foreground dark:border-sidebar-border"
            >
                {{ emptyLabel ?? 'No records found.' }}
            </div>
        </div>
    </div>
</template>
