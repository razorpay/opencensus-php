import { SplitzInitConfig } from 'common/splitz/types';
import { TransactionsEntityRoute } from 'merchant/views/Transactions/v2/common/constants';

const {
  PAYMENTS,
  ORDERS,
  FAILED_PAYMENTS,
  DISPUTES,
  SUCCESS_RATE,
  REFUNDS,
  BATCH_REFUNDS,
  BATCH_REFUNDS_UPLOAD,
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
          uniqueHashKey: 'Partnerships_oauth_phantom',
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
      ],
    },
    {
      matchByDashboard: ['product'],
      routesToMatch: [
        PAYMENTS,
        ORDERS,
        FAILED_PAYMENTS,
        DISPUTES,
        SUCCESS_RATE,
        REFUNDS,
        BATCH_REFUNDS,
        BATCH_REFUNDS_UPLOAD,
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
      ],
    },
  ],
};
