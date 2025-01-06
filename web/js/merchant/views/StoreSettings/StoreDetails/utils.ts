import { BreadCrumbType } from 'merchant/views/BillMeSettings/common/components/Breadcrumbs';

export const getPageBreadcrumbs = (storeName: string): BreadCrumbType[] => [
  {
    label: 'Account & Settings',
    href: '/account-settings',
  },
  {
    label: 'Store Settings',
    href: '/store-settings/stores-list',
  },
  {
    label: storeName,
  },
];
