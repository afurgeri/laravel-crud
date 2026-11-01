import { LayoutGrid } from '@lucide/vue';
import type { useTranslation } from '@/composables/useTranslation';
import { dashboard } from '@/routes';
import type { NavGroup, NavItem } from '@/types';

type Translate = ReturnType<typeof useTranslation>['t'];

/**
 * The sidebar menu. Add a group per area of the application; an item with a
 * permission is only shown to users who have it.
 */
export function navigationGroups(t: Translate): NavGroup[] {
    return [
        {
            title: t('Home'),
            items: [
                {
                    title: t('Dashboard'),
                    href: dashboard(),
                    icon: LayoutGrid,
                },
            ],
        },
    ];
}

/**
 * Links shown above the user menu, e.g. documentation or support.
 */
export function footerNavigationItems(t: Translate): NavItem[] {
    return [
        // { title: t('Documentation'), href: 'https://example.com/docs', icon: BookOpen },
    ];
}
