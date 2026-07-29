<script setup lang="ts">
import { Check, ChevronDown, LoaderCircle } from '@lucide/vue';
import { ComboboxContent, ComboboxPortal } from 'reka-ui';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxTrigger,
    ComboboxViewport,
} from '@/components/ui/combobox';
import { cn } from '@/lib/utils';
import type { CrudFilterOption, CrudRemoteFilter } from '@/types/crud';

const props = withDefaults(
    defineProps<{
        modelValue: string;
        remote: CrudRemoteFilter;
        id?: string;
        placeholder?: string;
    }>(),
    { placeholder: 'Search...' },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const open = ref(false);
const searchTerm = ref('');
const selectedValue = ref<string | undefined>(props.modelValue || undefined);
const selectedOption = ref<CrudFilterOption>();
const options = ref<CrudFilterOption[]>([]);
const loading = ref(false);

let debounceTimer: ReturnType<typeof setTimeout> | undefined;
let requestController: AbortController | undefined;
let requestSequence = 0;

function normalizeOptions(payload: unknown): CrudFilterOption[] {
    const source = Array.isArray(payload)
        ? payload
        : payload && typeof payload === 'object'
          ? ((payload as { data?: unknown; options?: unknown }).data ??
            (payload as { options?: unknown }).options ??
            [])
          : [];

    if (!Array.isArray(source)) {
        return [];
    }

    return source.flatMap((option): CrudFilterOption[] => {
        if (
            !option ||
            typeof option !== 'object' ||
            !('value' in option) ||
            !('label' in option) ||
            (typeof option.value !== 'string' &&
                typeof option.value !== 'number') ||
            typeof option.label !== 'string'
        ) {
            return [];
        }

        return [{ value: String(option.value), label: option.label }];
    });
}

async function loadOptions(search: string, selected?: string): Promise<void> {
    if (selected === undefined && search.length < props.remote.min_chars) {
        options.value = [];

        return;
    }

    requestController?.abort();
    requestController = new AbortController();
    const sequence = ++requestSequence;
    const url = new URL(props.remote.url, window.location.origin);

    if (search !== '') {
        url.searchParams.set('search', search);
    }

    if (selected !== undefined) {
        url.searchParams.set('selected', selected);
    }

    loading.value = true;

    try {
        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
            signal: requestController.signal,
        });

        if (!response.ok) {
            return;
        }

        const nextOptions = normalizeOptions(await response.json());

        if (sequence !== requestSequence) {
            return;
        }

        options.value = nextOptions;

        if (selected !== undefined) {
            selectedOption.value = nextOptions.find(
                (option) => option.value === selected,
            );
        }
    } catch (error) {
        if (!(error instanceof DOMException && error.name === 'AbortError')) {
            options.value = [];
        }
    } finally {
        if (sequence === requestSequence) {
            loading.value = false;
        }
    }
}

function scheduleSearch(value: string): void {
    clearTimeout(debounceTimer);

    if (value.length < props.remote.min_chars) {
        requestController?.abort();
        options.value = [];
        loading.value = false;

        return;
    }

    debounceTimer = setTimeout(() => loadOptions(value), props.remote.debounce);
}

function selectOption(option: CrudFilterOption): void {
    selectedValue.value = option.value;
    selectedOption.value = option;
    open.value = false;
    emit('update:modelValue', option.value);
}

watch(
    () => props.modelValue,
    (value) => {
        selectedValue.value = value || undefined;

        if (value === '') {
            selectedOption.value = undefined;
            searchTerm.value = '';
            options.value = [];
        }
    },
);

watch(searchTerm, scheduleSearch);

watch(open, (value) => {
    if (
        value &&
        props.modelValue !== '' &&
        selectedOption.value === undefined
    ) {
        void loadOptions('', props.modelValue);
    }
});

onMounted(() => {
    if (props.modelValue !== '') {
        void loadOptions('', props.modelValue);
    }
});

onBeforeUnmount(() => {
    clearTimeout(debounceTimer);
    requestController?.abort();
});
</script>

<template>
    <Combobox v-model="selectedValue" v-model:open="open">
        <ComboboxAnchor as-child>
            <ComboboxTrigger as-child>
                <button
                    type="button"
                    :id="id"
                    role="combobox"
                    :aria-expanded="open"
                    :data-placeholder="selectedOption ? undefined : ''"
                    class="border-input data-[placeholder]:text-muted-foreground [&_svg:not([class*='text-'])]:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive dark:bg-input/30 dark:hover:bg-input/50 flex h-9 w-full items-center justify-between gap-2 rounded-md border bg-transparent px-3 py-2 text-left text-sm whitespace-nowrap shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span class="truncate">
                        {{ selectedOption?.label ?? placeholder }}
                    </span>
                    <ChevronDown class="size-4 shrink-0 opacity-50" />
                </button>
            </ComboboxTrigger>
        </ComboboxAnchor>
        <ComboboxPortal>
            <ComboboxContent
                position="popper"
                align="center"
                :side-offset="4"
                class="z-50 max-h-(--reka-combobox-content-available-height) w-[var(--reka-combobox-trigger-width)] min-w-64 max-w-[calc(100vw-2rem)] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md"
            >
            <ComboboxInput v-model="searchTerm" :placeholder="placeholder" />
            <ComboboxViewport>
                <div
                    v-if="loading"
                    class="flex items-center justify-center gap-2 px-2 py-6 text-sm text-muted-foreground"
                >
                    <LoaderCircle class="size-4 animate-spin" />
                    Loading...
                </div>
                <ComboboxEmpty
                    v-else-if="searchTerm.length < remote.min_chars"
                    class="py-6 text-center text-sm text-muted-foreground"
                >
                    Type {{ remote.min_chars }} characters to search.
                </ComboboxEmpty>
                <ComboboxEmpty
                    v-else-if="options.length === 0"
                    class="py-6 text-center text-sm text-muted-foreground"
                >
                    No results found.
                </ComboboxEmpty>
                <ComboboxGroup v-else>
                    <ComboboxItem
                        v-for="option in options"
                        :key="option.value"
                        :value="option.value"
                        @select="selectOption(option)"
                    >
                        <ComboboxItemIndicator
                            class="mr-2 flex size-4 items-center justify-center"
                        >
                            <Check
                                :class="
                                    cn(
                                        'size-4',
                                        selectedValue === option.value
                                            ? 'opacity-100'
                                            : 'opacity-0',
                                    )
                                "
                            />
                        </ComboboxItemIndicator>
                        <span class="truncate">{{ option.label }}</span>
                    </ComboboxItem>
                </ComboboxGroup>
            </ComboboxViewport>
            </ComboboxContent>
        </ComboboxPortal>
    </Combobox>
</template>
