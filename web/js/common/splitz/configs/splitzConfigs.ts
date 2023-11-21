import { SplitzInitConfig } from 'common/splitz/types';
import { TransactionsEntityRoute } from 'merchant/views/Transactions/v2/common/constants';

const {
  PAYMENTS,
  ORDERS,
  FAILED_PAYMENTS,
  BATCH_PAYMENTS,
  DISPUTES,
  SUCCESS_RATE,
  REFUNDS,
  BATCH_REFUNDS,
  BATCH_REFUNDS_UPLOAD,
  UPLOAD_INVOICES,
  INVOICES,
} = TransactionsEntityRoute;

// default = merchant(i.e product and partner) and linkedAccount dashboard.

export const splitzConfig: SplitzInitConfig = {
  onInit: {
    default: [],
    merchant: [
      {
        uniqueHashKey: 'pos_onboarding',
        experimentId: {
          beta: 'MVPjQVuTnq6nHb',
          production: 'MVNsecIfv7m93R',
        },
        defaultVariant: {
          name: 'variables',
          variables: [
            {
              key: 'result',
              value: 'off',
            },
          ],
        },
      },
      {
        uniqueHashKey: 'firs_request',
        experimentId: {
          beta: 'MkokP4l9jGy625',
          production: 'MkpxzLNTIMUt9d',
        },
        defaultVariant: {
          name: 'variables',
          variables: [
            {
              key: 'result',
              value: 'off',
            },
          ],
        },
      },
      {
        uniqueHashKey: 'config_based_tags',
        experimentId: {
          beta: 'MJFQR9sKIrQsNL',
          production: 'MmVVvCn7kpwSSX',
        },
        defaultVariant: {
          name: 'variables',
          variables: [
            {
              key: 'result',
              value: 'off',
            },
          ],
        },
      },
      {
        uniqueHashKey: 'settlementsV3_details_revamp',
        experimentId: {
          beta: 'MPFDhXp1ooZQom',
          production: 'N02pvjbyZUaw4f',
        },
        defaultVariant: {
          name: 'variables',
          variables: [
            {
              key: 'result',
              value: 'off',
            },
          ],
        },
      },
    ],
    linkedAccount: [],
    pokedex: [],
  },
  routeBased: [
    {
      routesToMatch: [/^(?=.*\/reports(?:\/(?:downloads|schedules))?).*$/i],
      abExperiments: [
        {
          uniqueHashKey: 'Reports_Revamp_Recents',
          experimentId: {
            beta: 'LhJWXVuaVbbpnR',
            production: 'LhJcdQNbjDvQo5',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
        {
          uniqueHashKey: 'Reports_Schedules',
          experimentId: {
            beta: 'LpcUyqou4GEsNz',
            production: 'LoMqyEzW0E5wvE',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
      ],
    },
    {
      routesToMatch: ['/wallet/batch-actions'],
      abExperiments: [
        {
          uniqueHashKey: 'create_bulk_gift_cards',
          experimentId: {
            beta: 'MumLEub9N56cvC',
            production: 'MvCbAPF7OTRc1H',
          },
          defaultVariant: {
            name: 'variant',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
      ],
    },
    {
      routesToMatch: ['/smartcollect'],
      abExperiments: [
        {
          uniqueHashKey: 'rbl_account_migration',
          experimentId: {
            beta: 'MbParid2BNlpve',
            production: 'MbPd1lnvPzPzSo',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
      ],
    },
    {
      matchByDashboard: ['linkedAccount'],
      routesToMatch: ['/reports'],
      abExperiments: [
        {
          uniqueHashKey: 'LA_Reports_Revamp',
          experimentId: {
            beta: 'Lby668Otnw4zQE',
            production: 'LbzO5k3RZqiN4V',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
      ],
    },
    {
      matchByDashboard: ['partner'],
      routesToMatch: [/partners.*/i],
      abExperiments: [
        {
          uniqueHashKey: 'partnerships_oauth_phantom_configurator',
          experimentId: {
            beta: 'MT04AJ2UqjKHCV',
            production: 'MT0jjJjiPjwd9l',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
        {
          uniqueHashKey: 'partnerships_oauth_phantom',
          experimentId: {
            beta: 'LoGdTEB7Wo0UuW',
            production: 'LoGggyN9DVhO7A',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
        {
          uniqueHashKey: 'partnerships_easier_access_to_submerchant_kyc',
          experimentId: {
            beta: 'MNuYX8JifIAFCc',
            production: 'MNuWeZSwz01j8V',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
        {
          uniqueHashKey: 'partnership_capital_bureau_link',
          experimentId: {
            beta: 'MsjzRLsAMWNIEa',
            production: 'MskGdpIq4sehth',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
      ],
    },
    {
      matchByDashboard: ['product'],
      routesToMatch: [
        PAYMENTS,
        ORDERS,
        FAILED_PAYMENTS,
        BATCH_PAYMENTS,
        DISPUTES,
        SUCCESS_RATE,
        REFUNDS,
        BATCH_REFUNDS,
        BATCH_REFUNDS_UPLOAD,
        UPLOAD_INVOICES,
        INVOICES,
        `${PAYMENTS}/:id`,
        `${REFUNDS}/:id`,
      ],
      abExperiments: [
        {
          uniqueHashKey: 'Transactions_Revamp',
          experimentId: {
            beta: 'MPFDhXp1ooZQom',
            production: 'MPFKF4K2JVFXa2',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
      ],
    },
    {
      matchByDashboard: ['product'],
      routesToMatch: ['/dashboard'],
      abExperiments: [
        {
          uniqueHashKey: 'Festive_Anime',
          experimentId: {
            beta: 'MWkQ2FycdHtU8S',
            production: 'MWkSKmsQrMfKl1',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
      ],
    },
    {
      routesToMatch: ['/offers'],
      abExperiments: [
        {
          uniqueHashKey: 'Low_cost_offer',
          experimentId: {
            beta: 'MSfpg3rG4RJMto',
            production: 'MT1KxclWw7zMtH',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
      ],
    },
    {
      routesToMatch: [/^\/magic(?:\/.*)?$/i],
      abExperiments: [
        {
          uniqueHashKey: 'magic_analytics_setting',
          experimentId: {
            beta: 'MWKHCSGKltMkYQ',
            production: 'MWVoZbRUxayCAu',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
        {
          uniqueHashKey: 'magic_shopify_shipping_engine',
          experimentId: {
            beta: 'MYEaKTAQCsPIyl',
            production: 'MYEdnXbyYkrovw',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
        {
          uniqueHashKey: 'magic_coupon_engine',
          experimentId: {
            beta: 'MKhTcpqsBvixkj',
            production: 'MKhbJ5VlupICIG',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
        {
          uniqueHashKey: 'magic_shopify_coupon_sync',
          experimentId: {
            beta: 'MsN8hpfoohpFo9',
            production: 'MsN53IGTr8bm5u',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
      ],
    },
    {
      matchByDashboard: ['product'],
      routesToMatch: ['/dashboard', '/account-settings', /website-app-settings.*/i],
      abExperiments: [
        {
          uniqueHashKey: 'noCodePolicyWizard',
          experimentId: {
            beta: 'MYG6HUs91NZPOr',
            production: 'MYG8V2uC114MO5',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
        {
          uniqueHashKey: 'policyWizardV2',
          experimentId: {
            beta: 'MbRbhEPnxYkzqx',
            production: 'MbRfPfUNiETQ7V',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
      ],
    },
    {
      matchByDashboard: ['product'],
      routesToMatch: [/^(\/optimizer\/(add-provider|update-provider\/[^/]*$))/i],
      abExperiments: [
        {
          uniqueHashKey: 'add_provider_revamp',
          experimentId: {
            beta: 'MuLnzwkynPb089',
            production: 'MyHpKJDgmv1kXU',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
      ],
    },
    {
      routesToMatch: [/capital\/cash-advance.*/i],
      abExperiments: [
        {
          uniqueHashKey: 'capitalPreclosureEdiExp',
          experimentId: {
            beta: 'N0GTyuOXHLrynG',
            production: 'N0GTyuOXHLrynG',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
      ],
    },
  ],
};
