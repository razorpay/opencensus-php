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
    merchant: [],
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
      routesToMatch: ['/'],
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
  ],
};
