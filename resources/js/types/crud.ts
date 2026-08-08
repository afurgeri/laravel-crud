import type { Method } from '@inertiajs/core';

export type CrudHref =
    | string
    | {
          url: string;
          method: 'get';
      };

export type CrudLayoutWidth = 'standard' | 'wide' | 'full';

export type CrudTemporalType = 'date' | 'time' | 'datetime';

export type CrudSpan = {
    base: number;
    sm?: number;
    md?: number;
    lg?: number;
    xl?: number;
    '2xl'?: number;
};

export type CrudColumn = {
    name: string;
    label: string;
    sortable: boolean;
    width?: string;
    min_width?: string;
    max_width?: string;
    fixed?: boolean;
};

export type CrudField = {
    name: string;
    label: string;
    type:
        | 'text'
        | 'email'
        | 'password'
        | 'checkbox'
        | 'select'
        | 'combobox'
        | 'remote-select'
        | 'number'
        | CrudTemporalType
        | 'textarea'
        | 'array';
    confirmed: boolean;
    required: boolean;
    rules: string[];
    clearable: boolean;
    multiple?: boolean;
    step?: string;
    unique_items?: boolean;
    visible: boolean;
    visible_on_update: boolean;
    span: {
        base: number;
        sm?: number;
        md?: number;
        lg?: number;
        xl?: number;
        '2xl'?: number;
    };
    defaultValue?: unknown;
    timezone?: string;
    options?: CrudFilterOption[];
    remote?: CrudRemoteFilter;
};

export type CrudFieldSlotProps = {
    field: CrudField;
    id: string;
    name: string;
    defaultValue: unknown;
    error?: string;
    required: boolean;
    readOnly: boolean;
};

export type CrudSort = {
    column: string | null;
    direction: 'asc' | 'desc';
} | null;

export type CrudSearch = {
    enabled: boolean;
    value: string | null;
    span: CrudSpan;
};

export type CrudFilterOption = {
    value: string;
    label: string;
};

export type CrudRemoteFilter = {
    url: string;
    min_chars: number;
    debounce: number;
    source?: 'filter' | 'field';
};

export type CrudFilter = {
    name: string;
    label: string;
    type:
        | 'text'
        | CrudTemporalType
        | 'number'
        | 'select'
        | 'combobox'
        | 'remote-select';
    operator: string;
    relation: boolean;
    clearable: boolean;
    multiple?: boolean;
    range: string | null;
    value: unknown;
    span: CrudSpan;
    options?: CrudFilterOption[];
    remote?: CrudRemoteFilter;
    max_date?: string | null;
    timezone?: string;
};

export type CrudSchema = {
    resource: string;
    form_mode: 'dialog' | 'page';
    page_width: CrudLayoutWidth;
    form_width: CrudLayoutWidth;
    operations: {
        show: boolean;
        create: boolean;
        update: boolean;
        delete: boolean;
    };
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
    href?: CrudHref;
    label?: string;
    title?: string;
    description?: string;
    submitLabel?: string;
};

export type CrudEditConfig<T extends CrudRecord> = {
    action: (record: T) => FormAction;
    href?: (record: T) => CrudHref;
    can?: (record: T) => boolean;
    label?: string;
    title?: (record: T) => string;
    description?: string;
    submitLabel?: string;
};

export type CrudShowConfig<T extends CrudRecord> = {
    href: (record: T) => CrudHref;
    can?: (record: T) => boolean;
    label?: string;
    title?: (record: T) => string;
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
