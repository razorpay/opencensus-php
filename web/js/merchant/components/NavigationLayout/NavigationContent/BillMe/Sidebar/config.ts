import { BASE_ROUTES } from 'merchant/components/SidebarV2/utils/href';
import { ReportsIcon, SettingsIcon, BillMeIcon } from '@razorpay/blade/components';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

const BILL_ME_SIDE_NAV_ITEMS = [
  {
    label: 'BillMe',
    title: 'BillMe',
    href: BASE_ROUTES.bill_me,
    icon: BillMeIcon,
    to: BASE_ROUTES.bill_me,
    routeRegex: undefined,
  },
  {
    label: 'Reports',
    title: 'Reports',
    href: BASE_ROUTES.reports,
    icon: ReportsIcon,
    to: BASE_ROUTES.reports,
    routeRegex: '^/reports/[^/]+$',
    isPending: false,
  },
];

const BILL_ME_SIDE_NAV_FOOTER_ITEMS = [
  {
    label: 'Accounts & Settings',
    title: 'Accounts & Settings',
    href: ROUTES_INFO.ACCOUNT_AND_SETTINGS,
    icon: SettingsIcon,
    to: ROUTES_INFO.ACCOUNT_AND_SETTINGS,
    routeRegex: undefined,
    end: true,
  },
];

export const BILL_ME_SIDE_NAV_SECTION = {
  section_name: '',
  section_id: 'billme_side_nav_section',
  product_options: BILL_ME_SIDE_NAV_ITEMS,
};

export const BILL_ME_SIDE_NAV_FOOTER_SECTION = {
  section_name: '',
  section_id: 'billme_side_nav_footer_section',
  product_options: BILL_ME_SIDE_NAV_FOOTER_ITEMS,
};
