<script setup lang="ts">
import { Check, ChevronsUpDown, LoaderCircle } from '@lucide/vue';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxTrigger,
    ComboboxViewport,
    ComboboxRoot as Combobox,
} from 'reka-ui';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
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

    return source.filter((option): option is CrudFilterOption =>
        Boolean(
            option &&
            typeof option === 'object' &&
            'value' in option &&
            'label' in option &&
            typeof option.value === 'string' &&
            typeof option.label === 'string',
        ),
    );
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
                    class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-left text-sm ring-offset-background outline-none placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span class="truncate">
                        {{ selectedOption?.label ?? placeholder }}
                    </span>
                    <ChevronsUpDown class="ml-2 size-4 shrink-0 opacity-50" />
                </button>
            </ComboboxTrigger>
        </ComboboxAnchor>
        <ComboboxContent
            class="z-50 w-(--reka-combobox-trigger-width) rounded-md border bg-popover text-popover-foreground shadow-md"
        >
            <ComboboxInput
                v-model="searchTerm"
                :placeholder="placeholder"
                class="h-9 w-full border-b border-input bg-transparent px-3 text-sm outline-none"
            />
            <ComboboxViewport class="max-h-60 overflow-y-auto p-1">
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
                        class="relative flex cursor-default items-center rounded-sm px-2 py-1.5 text-sm outline-none select-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
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
    </Combobox>
</template>
