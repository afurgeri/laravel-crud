import { usePage } from '@inertiajs/vue3';

type TranslationReplacements = Record<string, string | number>;

export function useTranslation() {
    const page = usePage<{ translations?: Record<string, string> }>();

    function t(
        key: string,
        replacements: TranslationReplacements = {},
    ): string {
        let translation = page.props.translations?.[key] ?? key;

        for (const [placeholder, value] of Object.entries(replacements)) {
            translation = translation.replaceAll(
                `:${placeholder}`,
                String(value),
            );
        }

        return translation;
    }

    return { t };
}
