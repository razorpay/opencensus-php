import {
  HomeIcon,
  TransactionsIcon,
  SettlementsIcon,
  PaymentLinkIcon,
  ReportsIcon,
  BulkPayoutsIcon,
  FileTextIcon,
  IconComponent,
  RazorpayxPayrollIcon,
  BillIcon,
  AcceptPaymentsIcon,
  RazorpayXIcon,
  UsersIcon,
} from '@razorpay/blade/components';
import { QuickActionItem } from './types';

const productAliasToIconMap: Record<string, IconComponent> = {
  home_quick_action_sub_item: HomeIcon,
  transactions_quick_action_sub_item: TransactionsIcon,
  settlements_quick_action_sub_item: SettlementsIcon,
  payment_links_quick_action_sub_item: PaymentLinkIcon,
  reports_quick_action_sub_item: ReportsIcon,
  payouts_quick_action_sub_item: BulkPayoutsIcon,
  current_account_quick_action_sub_item: FileTextIcon,
  payroll_top_navigation_item: RazorpayxPayrollIcon,
  billme_top_navigation_item: BillIcon,
  payments_top_navigation_item: AcceptPaymentsIcon,
  banking_top_navigation_item: RazorpayXIcon,
  partners_top_navigation_item: UsersIcon,
};

export const connectedProductsData: QuickActionItem = {
  id: '2',
  type: 'quick_action_item',
  title: '',
  description: '',
  alias: 'one_navigation',
  actions: [],
  inputs: [],
  components: [
    {
      id: '21',
      type: 'quick_action_item',
      title: 'Payments',
      description: 'Payments',
      alias: 'payments_top_navigation_item',
      analytics: { enabled: true },
      styles: null,
      actions: [
        {
          title: '',
          action: 'navigate',
          type: 'navigate',
          icon: '',
          icon_position: '',
          action_params: {
            path: '/dashboard',
          },
        },
      ],
      inputs: [],
      components: [
        {
          id: '213',
          type: 'quick_action_sub_item',
          title: '',
          description: '',
          alias: 'payments_dashboard_page',
          analytics: { enabled: true },
          styles: null,
          actions: [
            {
              title: '',
              action: 'navigate',
              type: 'navigate',
              icon: '',
              icon_position: '',
              action_params: {
                path: '/dashboard',
              },
            },
          ],
          inputs: [],
          components: [],
        },
      ],
    },
    {
      id: '25',
      type: 'quick_action_item',
      title: 'Partners',
      description: 'Partnership',
      alias: 'partners_top_navigation_item',
      analytics: { enabled: true },
      styles: null,
      actions: [
        {
          title: '',
          action: 'navigate',
          type: 'navigate',
          icon: '',
          icon_position: '',
          action_params: {
            path: '/partners',
          },
        },
      ],
      inputs: [],
      components: [
        {
          id: '262',
          type: 'quick_action_sub_item',
          title: '',
          description: '',
          alias: 'payments_dashboard_page',
          analytics: { enabled: true },
          styles: null,
          actions: [
            {
              title: '',
              action: 'navigate',
              type: 'navigate',
              icon: '',
              icon_position: '',
              action_params: {
                path: '/partners',
              },
            },
          ],
          inputs: [],
          components: [],
        },
      ],
    },
    {
      id: '22',
      type: 'quick_action_item',
      title: 'Banking',
      description: 'Banking',
      alias: 'banking_top_navigation_item',
      analytics: { enabled: true },
      styles: null,
      actions: [
        {
          title: '',
          action: 'navigate',
          type: 'navigate',
          icon: 'right_arrow_icon',
          icon_position: 'right',
          action_params: {
            path: '/banking',
          },
        },
      ],
      inputs: [],
      components: [
        {
          id: '223',
          type: 'quick_action_sub_item',
          title: '',
          description: '',
          alias: 'banking_navigate',
          analytics: { enabled: true },
          styles: null,
          actions: [
            {
              title: '',
              action: 'navigate',
              type: 'navigate',
              icon: 'right_arrow_icon',
              icon_position: 'right',
              action_params: {
                path: '/banking',
              },
            },
          ],
          inputs: [],
          components: [],
        },
      ],
    },
    {
      id: '23',
      type: 'quick_action_item',
      title: 'Payroll',
      description: 'Payroll',
      alias: 'payroll_top_navigation_item',
      analytics: { enabled: true },
      styles: null,
      actions: [
        {
          title: '',
          action: 'navigate',
          type: 'navigate',
          icon: '',
          icon_position: '',
          action_params: {
            path: '/payroll',
          },
        },
      ],
      inputs: [],
      components: [
        {
          id: '231',
          type: 'quick_action_sub_item',
          title: '',
          description: '',
          alias: 'payments_dashboard_page',
          analytics: { enabled: true },
          styles: null,
          actions: [
            {
              title: '',
              action: 'navigate',
              type: 'navigate',
              icon: '',
              icon_position: '',
              action_params: {
                path: '/payroll',
              },
            },
          ],
          inputs: [],
          components: [],
        },
      ],
    },
  ],
  analytics: {
    enabled: true,
  },
  styles: {
    brand_logo: 'https://upload.wikimedia.org/wikipedia/commons/7/77/Razorpay_logo.png',
  },
};

export default productAliasToIconMap;
