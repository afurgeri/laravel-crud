import type { Method } from '@inertiajs/core';

export type CrudColumn = {
    name: string;
    label: string;
    sortable: boolean;
};

export type CrudField = {
    name: string;
    label: string;
    type: string;
    confirmed: boolean;
    required: boolean;
    rules: string[];
    visible_on_update: boolean;
};

export type CrudSort = {
    column: string | null;
    direction: 'asc' | 'desc';
} | null;

export type CrudSearch = {
    enabled: boolean;
    value: string | null;
};

export type CrudFilterOption = {
    value: string;
    label: string;
};

export type CrudFilter = {
    name: string;
    label: string;
    type: 'text' | 'date' | 'number' | 'select';
    operator: string;
    relation: boolean;
    clearable: boolean;
    range: string | null;
    value: unknown;
    options?: CrudFilterOption[];
    max_date?: string | null;
};

export type CrudSchema = {
    resource: string;
    title: string;
    description: string | null;
    empty_label: string | null;
    columns: CrudColumn[];
    fields: CrudField[];
    sort: CrudSort;
    search: CrudSearch;
    filters: CrudFilter[];
};

export type CrudPaginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type CrudRecord = Record<string, unknown> & {
    id: string | number;
};

export type FormAction = {
    action: string;
    method: Method;
};

export type CrudCreateConfig = {
    can: boolean;
    action: FormAction;
    label?: string;
    title?: string;
    description?: string;
    submitLabel?: string;
};

export type CrudEditConfig<T extends CrudRecord> = {
    action: (record: T) => FormAction;
    can?: (record: T) => boolean;
    label?: string;
    title?: (record: T) => string;
    description?: string;
    submitLabel?: string;
};

export type CrudDestroyConfig<T extends CrudRecord> = {
    action: (record: T) => FormAction;
    can?: (record: T) => boolean;
    label?: string;
    title?: (record: T) => string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
};
