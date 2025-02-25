import { IOneNavigationResponse } from '@apps/shell/src/client/widgets/Sidebar/types';

const organizationBrand = {
  axis: {
    brand_image: 'https://cdn.razorpay.com/static/assets/hostedpages/axis_logo.svg',
    brand_colour: '#83003D',
  },
  yesbank: {
    brand_image:
      'https://rzp-1415-prod-dashboard-activation.s3.ap-south-1.amazonaws.com/org_IoHRQpwZ67N8jw/login_logo/phpphFaIF',
    brand_colour: '#0062A8',
  },
  razorpay: {
    brand_image: 'https://cdn.razorpay.com/logo.svg',
    brand_colour: '',
  },
} as const;

export const MASTER_CONFIG: IOneNavigationResponse = {
  id: '123',
  one_nav_config: organizationBrand.razorpay,
  components: [
    {
      id: '456',
      type: 'tab',
      title: 'Payments',
      one_nav_config: {
        default: true,
      },
      components: [
        {
          id: '321',
          type: 'navigation',
          one_nav_config: {
            banner: {
              activation_progress: Math.random() > 0.5 ? 78 : 100,
              activation_status: 'active',
              nc_eligible: false,
            },
          },
          components: [
            {
              id: '423',
              type: 'navigation_body',
              components: [
                {
                  id: '321',
                  type: 'navigation_link',
                  title: 'Home',
                  icon: 'union',
                  actions: { action: 'home' },
                },
                {
                  id: '321',
                  type: 'navigation_link',
                  title: 'Transactions',
                  icon: 'transactions',
                  actions: { action: 'transactions' },
                  one_nav_config: {
                    tooltip: 'View all transactions',
                  },
                },
                {
                  id: '321',
                  type: 'navigation_link',
                  title: 'Settlements',
                  icon: 'settlement',
                  actions: { action: 'settlements' },
                  one_nav_config: {
                    title_suffix: {
                      type: 'badge',
                      value: 'New',
                      color: 'positive',
                    },
                  },
                },
                {
                  id: '321',
                  type: 'navigation_link',
                  title: 'Reports',
                  icon: 'activity',
                  components: [
                    {
                      id: '321',
                      type: 'navigation_link',
                      title: 'Settlement Report',
                      icon: 'settlement',
                      actions: { action: 'reports' },
                    },
                    {
                      id: '321',
                      type: 'navigation_link',
                      title: 'Risk Report',
                      icon: 'transactions',
                      actions: { action: 'riskAndFraud' },
                    },
                  ],
                },
                {
                  id: '1233',
                  type: 'navigation_link',
                  title: 'Account & Settings',
                  icon: 'settings',
                  actions: { action: 'accountsettings' },
                },
                {
                  id: '454',
                  icon: 'payments',
                  type: 'navigation_section',
                  title: 'Miscellaneous',
                  one_nav_config: {
                    initial_items_count: 3,
                  },
                  components: [
                    {
                      id: '3232x3',
                      type: 'navigation_link',
                      title: 'Customers',
                      icon: 'user',
                      actions: { action: 'customers' },
                    },
                    {
                      id: '323545',
                      type: 'navigation_link',
                      title: 'Offers',
                      icon: 'tag',
                      actions: { action: 'offers' },
                    },
                    {
                      id: '32453',
                      type: 'navigation_link',
                      title: 'API Keys',
                      icon: 'layout',
                      actions: { action: 'api_keys' },
                    },
                    {
                      id: '32543',
                      type: 'navigation_link',
                      title: 'Developers',
                      icon: 'codeSnippet',
                      actions: { action: 'developers' },
                    },
                    {
                      id: '3243',
                      type: 'navigation_link',
                      title: 'App Store',
                      icon: 'appStore',
                      actions: { action: 'app_store' },
                    },
                  ],
                },
              ],
            },
            {
              id: '423',
              type: 'navigation_footer',
              components: [
                {
                  id: '1344',
                  type: 'mode_toggle',
                  one_nav_config: {
                    initial_value: 'test',
                    is_disabled: true,
                  },
                },
                {
                  id: '321',
                  type: 'navigation_link',
                  title: 'Account and Settings',
                  icon: 'settings',
                  actions: { action: 'my_account' },
                },
              ],
            },
          ],
        },
      ],
    },
    {
      id: '789',
      type: 'tab',
      title: 'Payroll',
      components: [
        {
          id: '101',
          type: 'navigation',
          components: [
            {
              id: '233',
              type: 'navigation_body',
              components: [
                {
                  id: '3123',
                  type: 'navigation_link',
                  title: 'Home',
                  icon: 'home',
                  actions: { action: '/payroll' },
                },
                {
                  id: '23',
                  type: 'navigation_link',
                  title: 'Reimbursements',
                  icon: 'home',
                  actions: { action: '/reimbursements' },
                },
                {
                  id: '12',
                  type: 'navigation_link',
                  title: 'Home',
                  icon: 'home',
                  actions: { action: '/payroll' },
                },
              ],
            },
          ],
        },
      ],
    },
  ],
};
