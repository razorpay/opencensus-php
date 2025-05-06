import { QuickActionItem, QuickActionConnectedProductItem } from '../types';

const paymentsQuickActionMock: QuickActionItem = {
  id: '321',
  type: 'quick_action_item',
  title: 'Quick actions',
  description: 'Payments',
  actions: [],
  inputs: [],
  components: [
    {
      id: '3211',
      type: 'quick_action_sub_item',
      title: 'Home',
      actions: [
        {
          title: '',
          action: 'navigate',
          type: '',
          icon: '',
          icon_position: '',
          action_params: {
            path: '/settings/home',
          },
        },
      ],
      inputs: [],
      components: [],
      alias: 'home_quick_action_sub_item',
      analytics: {
        enabled: true,
      },
      styles: null,
    },
    {
      id: '3212',
      type: 'quick_action_sub_item',
      title: 'Transactions',
      actions: [
        {
          title: '',
          action: 'navigate',
          type: '',
          icon: '',
          icon_position: '',
          action_params: {
            path: '/settings/transactions',
          },
        },
      ],
      inputs: [],
      components: [],
      alias: 'transactions_quick_action_sub_item',
      analytics: {
        enabled: true,
      },
      styles: null,
    },
    {
      id: '3213',
      type: 'quick_action_sub_item',
      title: 'Settlements',
      actions: [
        {
          title: '',
          action: 'navigate',
          type: '',
          icon: '',
          icon_position: '',
          action_params: {
            path: '/settings/settlements',
          },
        },
      ],
      inputs: [],
      components: [],
      alias: 'settlements_quick_action_sub_item',
      analytics: {
        enabled: true,
      },
      styles: null,
    },
  ],
  alias: 'payments_quick_action_item',
  analytics: {
    enabled: true,
  },
  styles: null,
};

const bankingQuickActionMock: QuickActionItem = {
  id: '322',
  type: 'quick_action_item',
  title: 'Quick actions',
  description: 'Banking+',
  actions: [],
  inputs: [],
  components: [
    {
      id: '3221',
      type: 'quick_action_sub_item',
      title: 'Payouts',
      actions: [
        {
          title: '',
          action: 'navigate',
          type: '',
          icon: '',
          icon_position: '',
          action_params: {
            path: '/payouts',
          },
        },
      ],
      inputs: [],
      components: [],
      alias: 'payouts_quick_action_sub_item',
      analytics: {
        enabled: true,
      },
      styles: null,
    },
    {
      id: '3222',
      type: 'quick_action_sub_item',
      title: 'A/c statement',
      actions: [
        {
          title: '',
          action: 'navigate',
          type: '',
          icon: '',
          icon_position: '',
          action_params: {
            path: '/settings/ac_statement',
          },
        },
      ],
      inputs: [],
      components: [],
      alias: 'current_account_quick_action_sub_item',
      analytics: {
        enabled: true,
      },
      styles: null,
    },
  ],
  alias: 'banking_quick_action_item',
  analytics: {
    enabled: true,
  },
  styles: null,
};

export const connectedProductsMock: QuickActionConnectedProductItem[] = [
  {
    id: 'payments-1',
    alias: 'payments_top_navigation_item',
    title: 'Payments',
    description: 'Accept payments and manage transactions',
    isAlwaysOverflowing: false,
    selectAction: {
      actionType: 'navigate',
      value: {
        navigateTo: '/dashboard',
        type: 'internal_navigation',
      },
      pageData: null,
    },
  },
  {
    id: 'partners-1',
    alias: 'partners_top_navigation_item',
    title: 'Partners',
    description: 'Manage your partnerships',
    isAlwaysOverflowing: false,
    selectAction: {
      actionType: 'navigate',
      value: {
        navigateTo: '/partners',
        type: 'internal_navigation',
      },
      pageData: null,
    },
  },
  {
    id: 'banking-1',
    alias: 'banking_top_navigation_item',
    title: 'Banking',
    description: 'Manage your banking needs',
    isAlwaysOverflowing: false,
    selectAction: {
      actionType: 'navigate',
      value: {
        navigateTo: '/banking',
        type: 'internal_navigation',
      },
      pageData: null,
    },
  },
  {
    id: 'payroll-1',
    alias: 'payroll_top_navigation_item',
    title: 'Payroll',
    description: 'Manage your payroll',
    isAlwaysOverflowing: false,
    selectAction: {
      actionType: 'navigate',
      value: {
        navigateTo: '/payroll',
        type: 'internal_navigation',
      },
      pageData: null,
    },
  },
];

export default { paymentsQuickActionMock, bankingQuickActionMock };
