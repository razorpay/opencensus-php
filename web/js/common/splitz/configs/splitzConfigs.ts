/* eslint-disable max-lines */
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
        uniqueHashKey: 'insight_x_experiment',
        experimentId: {
          beta: 'OsfzT1j3yvxYca',
          production: 'Osg3KQZUfYYRcX',
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
        uniqueHashKey: 'ramp_settlements_for_excluded_segment',
        experimentId: {
          beta: 'PQMyYVTn3GVaoo',
          production: 'PQMwxDcW8bE6Ma',
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
        uniqueHashKey: 'inviteTeamMember2fa',
        experimentId: {
          beta: 'NJk8Ms2EafN0KA',
          production: 'NJmW82vPbjCU1e',
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
        uniqueHashKey: 'two_fa_route',
        experimentId: {
          beta: 'Ng1ZfgSF38n7J5',
          production: 'Ng14VTQBkDFQ8J',
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
        uniqueHashKey: 'account_code',
        experimentId: {
          beta: 'PxYImdV3vonPzh',
          production: 'Pxs7ls9USLV3Us',
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
        uniqueHashKey: 'settlements_soh_block',
        experimentId: {
          beta: 'PPV75LUJhQsV3F',
          production: 'PQPDXEie1XF0ic',
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
        uniqueHashKey: 'is_merchant_pos_for_ftx',
        experimentId: {
          beta: 'NdViMia8Elf8e7',
          production: 'NdVelrhkBdhLsY',
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
        uniqueHashKey: 'is_help_widget_disabled',
        experimentId: {
          beta: 'NozLYbvypZUjkv',
          production: 'Now1xr6jelTihz',
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
        uniqueHashKey: 'pos_soundbox',
        experimentId: {
          beta: 'Oy7dbr5GLrNFTJ',
          production: 'Oy7XP2SJswMiP5',
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
        uniqueHashKey: 'pos_brand_emi',
        experimentId: {
          beta: 'PARe3cKjrK4TIW',
          production: 'PARQn2CWO4Dlp0',
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
        uniqueHashKey: 'pos_payment_link',
        experimentId: {
          beta: 'PbAS6OJf62NkqW',
          production: 'PcF5ZZMY4akO8j',
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
        uniqueHashKey: 'pos_payment_link_qr_comp',
        experimentId: {
          beta: 'PoobhkRSe1t1pE',
          production: 'PooPBjLCcziyvU',
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
            {
              key: 'offersEnabled',
              value: 'off',
            },
            {
              key: 'isPanIndiaLive',
              value: 'off',
            },
          ],
        },
      },
      {
        uniqueHashKey: 'pos_sales_agent',
        experimentId: {
          beta: 'ODTBVSBUt60NFY',
          production: 'ODTCh4BKr0owNg',
        },
        defaultVariant: {
          name: 'variables',
          variables: [
            {
              key: 'result',
              value: 'off',
            },
            {
              key: 'ezetapMids',
              value: '',
            },
          ],
        },
      },
      {
        uniqueHashKey: 'omniChannelGtm',
        experimentId: {
          beta: 'N4hyDcUWYc1G8p',
          production: 'N6L7VhWOgGd1Jd',
        },
        requestData: (): Record<string, string> => ({
          customData: 'getAllowedCities',
        }),
        defaultVariant: {
          name: 'variables',
          variables: [
            {
              key: 'result',
              value: 'off',
            },
            {
              key: 'cities',
              value: '',
            },
          ],
        },
      },
      {
        uniqueHashKey: 'ftuxAfterL2',
        experimentId: {
          beta: 'NziszawWZ4GCni',
          production: 'Nzitvn5ZfZdesK',
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
        uniqueHashKey: 'my_devices',
        experimentId: {
          beta: 'Pbn7oLhLvDUZ60',
          production: 'Pbnj08lfKulLxf',
        },
        defaultVariant: {
          name: 'control',
          variables: [],
        },
      },
      {
        uniqueHashKey: 'partnerships_for_pos',
        experimentId: {
          beta: 'N3FXsNXuhB2qSf',
          production: 'N3FbGRify7Htzx',
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
        uniqueHashKey: 'partnerships_accounts_list_revamp',
        experimentId: {
          beta: 'NEXPO27pXq4xdu',
          production: 'NEXPYKWw3XJgkb',
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
          beta: 'PpkEmnPqDmD1bF',
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
      {
        uniqueHashKey: 'razorpay_gcms',
        experimentId: {
          beta: 'NEtQtUmS8rQ8Ag',
          production: 'NFKqZXBtmdq8z4',
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
        uniqueHashKey: 'rtux_homepage',
        experimentId: {
          beta: 'NQLsTXE9tcp1pE',
          production: 'NQLDxIVXOAnYez',
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
        uniqueHashKey: 'rtux_homepage_partner',
        experimentId: {
          beta: 'Q39j4rOAw0rZmQ',
          production: 'Q39lJ02S0XF1ZX',
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
        uniqueHashKey: 'rtux_top_insights_details_cta',
        experimentId: {
          beta: 'PesKDFb25KJNeW',
          production: 'PebDaH1KpqjGEv',
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
        uniqueHashKey: 'connected_navigation',
        experimentId: {
          beta: 'PK7igAoTxqLVkl',
          production: 'PK7cRCY8lsu5ER',
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
      {
        uniqueHashKey: 'rbacEnabled',
        experimentId: {
          beta: 'O85moJtATjSxPO',
          production: 'O85pnkikiVaF5N',
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
        uniqueHashKey: 'hide_notes_in_order_id',
        experimentId: {
          beta: 'PK4QeYa1whRInB',
          production: 'PK4SspoIDFxsrT',
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
        uniqueHashKey: 'internal_testing_whitelisting',
        experimentId: {
          beta: 'OzGYt2mDFeu00i',
          production: 'P0gtYnUusnH70C',
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
        uniqueHashKey: 'switch_merchant_modal_revamp',
        experimentId: {
          beta: 'OtU1plzdsGGh92',
          production: 'OtUWWTBEIKFbn6',
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
        uniqueHashKey: 'paypal_onboard_redirect',
        experimentId: {
          beta: 'OzPQ6DNFTJlbQi',
          production: 'OzPUk1WTGjY093',
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
        uniqueHashKey: 'ray_ai',
        experimentId: {
          beta: 'NgMk9aLHhP79nc',
          production: 'NgMh9RIZ3MoXev',
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
        uniqueHashKey: 'ray_onboarding_ai',
        experimentId: {
          beta: 'Oz2lq27rYHq1iP',
          production: 'Oz2kC4rmY6YECc',
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
        uniqueHashKey: 'tnc_update_modal',
        experimentId: {
          beta: 'P4s9QNbjLYFdw1',
          production: 'P4sAmsnaGAu89P',
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
        uniqueHashKey: 'magic_konnect',
        experimentId: {
          beta: 'NiHLK3gI0O8Kn1',
          production: 'NgzGEn4BoEgpgz',
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
        uniqueHashKey: 'diwali_report_banner',
        experimentId: {
          beta: 'Nntc2jFOd01hHO',
          production: 'NnteV1Ml7Fb4aD',
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
        uniqueHashKey: 'recon_sass_flag',
        experimentId: {
          beta: 'NaMSjrxhdTtjFO',
          production: 'NaMEVNbu71GpiJ',
        },
        defaultVariant: {
          name: 'variables',
          variables: [
            {
              key: 'turned',
              value: 'off',
            },
          ],
        },
      },
      {
        uniqueHashKey: 'enable_custom_reporting',
        experimentId: {
          beta: 'P6R9W5TW65FnG4',
          production: 'P4V6IAJs154hVG',
        },
        defaultVariant: {
          name: 'variables',
          variables: [
            {
              key: 'turned',
              value: 'off',
            },
          ],
        },
      },
      {
        uniqueHashKey: 'nocode_monetization',
        experimentId: {
          beta: 'OemGz3a8jmYDvi',
          production: 'OeoDrM3YNgYLeh',
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
        uniqueHashKey: 'storefront_v1',
        experimentId: {
          beta: 'Oux2ojFCgYupHe',
          production: 'Oux2bIMggWA9bP',
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
        uniqueHashKey: 'storefront_social_handle',
        experimentId: {
          beta: 'Oux2ojFCgYupHe',
          production: 'PxWciJBkjUEhtI',
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
        uniqueHashKey: 'stores',
        experimentId: {
          beta: 'Pirv5Qn3rEAHRl',
          production: 'PirHpxTMoDj823',
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
        uniqueHashKey: 'pp_onboarding_redirection',
        experimentId: {
          beta: 'PjeAi30h3xDT03',
          production: 'PjbbR9CKnFmY1X',
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
        uniqueHashKey: 'assisted_financing',
        experimentId: {
          beta: 'NVwyOLc893cct0',
          production: 'NYG6BgL7c6x72W',
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
        uniqueHashKey: 'refund_revamp',
        experimentId: {
          beta: 'OYYUaZJTQUczkI',
          production: 'OYYZ6mzZcBVkdW',
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
        uniqueHashKey: 'bounce_memo',
        experimentId: {
          beta: 'OxoBVeBzsXPKDk',
          production: 'OxpakNuYuZTl3z',
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
        uniqueHashKey: 'create_merchant_cta',
        experimentId: {
          beta: 'OoExDczJEroNZ0',
          production: 'OtOSbDPEDN8KFg',
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
        uniqueHashKey: 'diwali_themed_logo',
        experimentId: {
          beta: 'PCnGzgfkRdKHzS',
          production: 'PCnG8rM10Mz7bQ',
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
        uniqueHashKey: 'allow_merchant_to_delete_recon_run',
        experimentId: {
          beta: 'PCUe7zTtMjYUD1',
          production: 'I2XXDaIwDQaA6P',
        },
        defaultVariant: {
          name: 'variables',
          variables: [
            {
              key: 'turned',
              value: 'off',
            },
          ],
        },
      },
      {
        uniqueHashKey: 'pos_api_merchant_enablement',
        experimentId: {
          beta: 'OXEKmEW1H6Nk5b',
          production: 'OXEUIX6iFPCGoi',
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
        uniqueHashKey: 'bill_me_enabled',
        experimentId: {
          beta: 'PCozRd6m7HUVNR',
          production: 'PHumvQHBrGxWmp',
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
        uniqueHashKey: 'enable_2fa_for_protected_flows',
        experimentId: {
          beta: 'OvqHENDSx92RDN',
          production: 'OwEKutd9QhLehI',
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
        uniqueHashKey: 'bounce_memo_single_transaction',
        experimentId: {
          beta: 'PVTQ7xIFOcS6du',
          production: 'PVTRp1XskQOm9p',
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
        uniqueHashKey: 'is_time_offset_disabled_settlements',
        experimentId: {
          beta: 'Pc7dMi2j0TG8Wr',
          production: 'PcBRdh07FrBDtZ',
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
          uniqueHashKey: 'Data_Sync_Advertisement_Banner_Experiment',
          experimentId: {
            beta: 'P9JCe7k5KjNiUW',
            production: 'P9M0vAVr8PD2q6',
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
          uniqueHashKey: 'create_custom_report',
          experimentId: {
            beta: 'PgvryOORBW4DoI',
            production: 'PoqVFVzN69dLCj',
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
        {
          uniqueHashKey: 'gift_cards_transfer',
          experimentId: {
            beta: 'PSE3n0XAOaelcR',
            production: 'PSE20wn8enpU5w',
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
      routesToMatch: [/\/wallet\/(.*)/],
      abExperiments: [
        {
          uniqueHashKey: 'wallet_reports_experiment',
          experimentId: {
            beta: 'NEv2F6lXjGQ6rv',
            production: 'NEv4UKJo6oOzGK',
          },
          defaultVariant: {
            name: 'variant_off',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
            ],
          },
        },
        {
          uniqueHashKey: 'wallet_campaigns',
          experimentId: {
            beta: 'PoOmXHoxFLiyES',
            production: 'PoOqqreZomtVM3',
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
      routesToMatch: [`${PAYMENTS}/*`, `${REFUNDS}/*`],
      abExperiments: [
        {
          uniqueHashKey: 'refund_gateway_data',
          experimentId: {
            beta: 'N9OMSAws8YoLFh',
            production: 'N9NqlcqaRrtYdf',
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
          uniqueHashKey: 'payments_extra_refund_details',
          experimentId: {
            beta: 'Pjdb3qRRkiZNLr',
            production: 'PjbZD844F653MW',
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
      routesToMatch: ['/smartcollect/virtualaccounts'],
      abExperiments: [
        {
          uniqueHashKey: 'enable_smartcollect_vpa_option',
          experimentId: {
            beta: 'Nj6CqlLpTkrs3o',
            production: 'NiBZ5qQptf8t43',
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
            production: 'NHITocFEQvgrVs',
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
        {
          uniqueHashKey: 'partnerships_partner_playbook',
          experimentId: {
            beta: 'Mq6dCU0Pw9yWVc',
            production: 'Mq6WyVwB9qAhWd',
          },
          requestData: (requestDataArgs) => ({
            partner_type: requestDataArgs.partner_type,
          }),
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
        `${DISPUTES}/:id`,
      ],
      abExperiments: [
        {
          uniqueHashKey: 'export_payments_v2',
          experimentId: {
            beta: 'OvrPbmQy0feiR7',
            production: 'OvrQDHgTb5q5mL',
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
          uniqueHashKey: 'microfrontend_selfserve',
          experimentId: {
            beta: 'NKAgVqmbHtVIgN',
            production: 'NKAbJhHMEqZdd9',
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
          uniqueHashKey: 'Transaction_Retry_Timeline',
          experimentId: {
            beta: 'NC8Dl41iAfVXXf',
            production: 'NC7wGmgtIeUy7M',
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
          uniqueHashKey: 'enable_downtime_banner',
          experimentId: {
            beta: 'OH6P4bLoL8lgSh',
            production: 'OH6XOpF0HzXGUH',
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
          uniqueHashKey: 'Disputes_Revamp_V2',
          experimentId: {
            beta: 'NMqWLS3CYxKkBg',
            production: 'NMt4EHe96Igguy',
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
      routesToMatch: ['/dashboard'],
      abExperiments: [
        {
          uniqueHashKey: 'CURLEC_M2_BANNER',
          experimentId: {
            beta: 'OipA5KQj2vUTbS',
            production: 'OpOwORnR9ZRKMU',
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
          uniqueHashKey: 'mobile_hotjar_survey',
          experimentId: {
            beta: 'PHfspNoG4RCAOf',
            production: 'PHfpO51O3j3ahk',
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
          uniqueHashKey: 'enable_manual_rekyc',
          experimentId: {
            beta: 'PWDQ67wDXLfhLD',
            production: 'PWDYrJnxHXgUVV',
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
          uniqueHashKey: 'enable_razorpay_rewind',
          experimentId: {
            beta: 'Pg2HiL6hjOzsAf',
            production: 'Pg2CXXqNEjAE8l',
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
          uniqueHashKey: 'date_range_insight_charts',
          experimentId: {
            beta: 'PPQlgBorXXG30Z',
            production: 'PPQePz8GoeFig8',
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
      routesToMatch: ['/dashboard', '/account-settings'],
      abExperiments: [
        {
          uniqueHashKey: 'STREAKS_REWARDS_GROWTH',
          experimentId: {
            beta: 'MxTTd86ndTW9w8',
            production: 'MxU5rA1OJAmx3s',
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
      routesToMatch: ['/profile', '/account-settings', /bank-accounts-settlements.*/i],
      abExperiments: [
        {
          uniqueHashKey: 'block_bank_account_update',
          experimentId: {
            beta: 'PIKiCEIp6GFaJ5',
            production: 'PIK1zH5Q4ajNWS',
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
          uniqueHashKey: 'registered_address_for_gst',
          experimentId: {
            beta: 'PVu09z6AtRrP06',
            production: 'PVty9eKO0uBZbO',
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
      routesToMatch: [/offers.*/i],
      abExperiments: [
        {
          uniqueHashKey: 'upi_granular_offer_dashboard',
          experimentId: {
            beta: 'OkdDfUAR38LyMG',
            production: 'OqcWBRjd5vS2H5',
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
      routesToMatch: [/offers.*/i],
      abExperiments: [
        {
          uniqueHashKey: 'multi_payment_offer',
          experimentId: {
            beta: 'PsNO6QFeqdmnJN',
            production: 'PrXqu08KMp5FN1',
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
      routesToMatch: [/offers.*/i],
      abExperiments: [
        {
          uniqueHashKey: 'OE_FETCH_IIN_FROM_BIN_EXP',
          experimentId: {
            beta: 'PyL71FJgUotGil',
            production: 'PzDdB6h4PFn1Uc',
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
      routesToMatch: [/^(\/magic(\/.*)?|\/configuration\/magic\/.*)$/, '/dashboard/*'],
      abExperiments: [
        {
          uniqueHashKey: 'magicx_publicapp_cod',
          experimentId: {
            beta: 'P48gdKIuJWPB02',
            production: 'P48s8XHiHP9wRc',
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
      routesToMatch: [/^(\/magic(\/.*)?|\/configuration\/magic\/.*)$/],
      abExperiments: [
        {
          uniqueHashKey: 'freebie_coupon',
          experimentId: {
            beta: 'PVN5hvTb9QyaqE',
            production: 'PVMyRMYRZfdvFW',
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
          uniqueHashKey: 'magic_dashboard_revamp',
          experimentId: {
            beta: 'OKyLfVMA1lbZob',
            production: 'OL1uuhd0RNk5zR',
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
          uniqueHashKey: 'magicx_publicapp_acod',
          experimentId: {
            beta: 'PVrN4pgmYL1K5y',
            production: 'PUfOPVCxDrQClO',
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
          uniqueHashKey: 'magic_zones_file_upload',
          experimentId: {
            beta: 'Nh6mUrfEvCtOyQ',
            production: 'Nh70AvGOK3wYkz',
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
        {
          uniqueHashKey: 'magic_hide_cod_when_disabled',
          experimentId: {
            beta: 'NBmJ2kwae0JDrc',
            production: 'NBmG3Kn0WUEwC5',
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
          uniqueHashKey: 'magic_free_shipping_coupon',
          experimentId: {
            beta: 'NOnCx61NWSEk0G',
            production: 'NOnExCNWVaVdBv',
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
          uniqueHashKey: 'checkout_v2',
          experimentId: {
            beta: 'NOq9DTaOYYUIhO',
            production: 'NPN9bwDyfK9bZp',
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
          uniqueHashKey: 'magic_x_store_settings',
          experimentId: {
            beta: 'O1zHNwfSZIdefj',
            production: 'O1zKFheAyROmHX',
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
          uniqueHashKey: 'magic_multi_coupons_enabled',
          experimentId: {
            beta: 'NrkxHs8EKw6kL9',
            production: 'Nrkzkkk9biFr55',
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
      routesToMatch: [/^\/magic-konnect(?:\/.*)?$/i],
      abExperiments: [
        {
          uniqueHashKey: 'magic_konnect_login_enabled',
          experimentId: {
            beta: 'NiHMzXt5F48rKg',
            production: 'NiFX1PTuAdYwGc',
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
      routesToMatch: [
        /^(\/optimizer\/(onboarding|add-provider|update-provider|provider\/[^/]*$))/i,
      ],
      abExperiments: [
        {
          uniqueHashKey: 'checkout_dot_com_optimizer_gateway',
          experimentId: {
            beta: 'N8fLuDIZyjWzdy',
            production: 'N8xlNTXPTVMXWE',
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
          uniqueHashKey: 'easebuzz_optimizer_gateway',
          experimentId: {
            beta: 'NS38QuzGdG77EL',
            production: 'NRyZtAZn92wFlI',
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
          uniqueHashKey: 'integration_audit',
          experimentId: {
            beta: 'NW0ESOMHunD3on',
            production: 'Nl2wGYuH4sQ9ek',
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
          uniqueHashKey: 'opti_sme_onboarding',
          experimentId: {
            beta: 'PIpuaVkVfMGhGH',
            production: 'PVTCzMgAJV37ne',
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
      routesToMatch: ['/business-settings/gst'],
      abExperiments: [
        {
          uniqueHashKey: 'gst_update',
          experimentId: {
            beta: 'MxayRehE3jY1Sq',
            production: 'Mxawn4bg3JADhR',
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
      routesToMatch: ['website-app-settings/*', '/onboarding/api-keys'],
      abExperiments: [
        {
          uniqueHashKey: 'business_website_revamp',
          experimentId: {
            beta: 'NBQ9Mq1JoUvSG2',
            production: 'NBQC99TbrljfUB',
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
          uniqueHashKey: 'business_website_v2_automation',
          experimentId: {
            beta: 'OAjeLeTYDObyns',
            production: 'OAjgnAslLpwJXb',
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
        /^\/paymentlinks(?:\/.*)?$/i,
        '/account-settings',
        /payments-and-refunds-settings.*/i,
      ],
      abExperiments: [
        {
          uniqueHashKey: 'whatsAppPLEnabled',
          experimentId: {
            beta: 'N2aMq4RRuGpcMd',
            production: 'N2aJVBCooxc93k',
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
      routesToMatch: [/payment-methods.*/i],
      abExperiments: [
        {
          uniqueHashKey: 'recurring_instrument_requester',
          experimentId: {
            beta: 'OQX4MqIo2lXmFR',
            production: 'OQX1qb9G4chANl',
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
          uniqueHashKey: 'enable_web_scrapper',
          experimentId: {
            beta: 'P9GJoqWO6FhUSm',
            production: 'P9FTxfH5Fa7yEE',
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
      routesToMatch: ['/payment-methods/international-payments'],
      abExperiments: [
        {
          uniqueHashKey: 'internationalAdditionalDocs',
          experimentId: {
            beta: 'NBK3wNvcX3FDdD',
            production: 'NBK6z9A2DKMnuA',
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
      routesToMatch: ['/payment-methods/international-payments'],
      abExperiments: [
        {
          uniqueHashKey: 'disableInternationalPaymentMethods',
          experimentId: {
            beta: 'NEZeS5Hfnzxzcx',
            production: 'NFK2nqlt1QFfC1',
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
      routesToMatch: ['/payment-methods/international-payments'],
      abExperiments: [
        {
          uniqueHashKey: 'showIntlMethodEnablement',
          experimentId: {
            beta: 'NV4AhqZxTQt7ok',
            production: 'NRwqtLtFfSg0hA',
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
      routesToMatch: [UPLOAD_INVOICES],
      abExperiments: [
        {
          uniqueHashKey: 'UploadInvoiceSenderAddr',
          experimentId: {
            beta: 'NSXliTmcp9pgO5',
            production: 'NSlkvKmQmH5mg3',
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
      routesToMatch: [`${PAYMENTS}/*`],
      abExperiments: [
        {
          uniqueHashKey: 'omni_merchants',
          experimentId: {
            beta: 'MHJsO5gmOwUIVt',
            production: 'MHMXkYtdAeHGmg',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'show-ezetap-txn',
                value: 'on',
              },
              {
                key: 'hide-ezetap-txn',
                value: 'off',
              },
            ],
          },
        },
      ],
    },
    {
      routesToMatch: [
        /\/paymentpages\/batchpaymentpages\/(.*)\/payments#batchpaymentpages/,
        /\/(paymentpages|subscription_buttons|paymentbuttons)\/(.*)\/payments/,
        'payment-handle',
      ],
      abExperiments: [
        {
          uniqueHashKey: 'NcaPaymentFetch',
          experimentId: {
            beta: 'NYeAdZxMitXwPO',
            production: 'NYe7P2aTFDwvZQ',
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
      routesToMatch: ['/invoices'],
      abExperiments: [
        {
          uniqueHashKey: 'inv_create_flow_ux',
          experimentId: {
            beta: 'Pjclx1qmXcKtpU',
            production: 'PjbVSARccRwJIe',
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
      routesToMatch: ['/app-store', /\/rize-marketplace(\/(.)*)?/],
      abExperiments: [
        {
          uniqueHashKey: 'rize_marketplace',
          experimentId: {
            beta: 'NajXNUXMrVJU9J',
            production: 'Nb8H4x6K6yvdX7',
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
      routesToMatch: [/\/payments\/[a-zA-Z0-9_-]+/],
      abExperiments: [
        {
          uniqueHashKey: 'pos_chargeslip',
          experimentId: {
            beta: 'O7dJCkcIy3JlsX',
            production: 'O7dKubRkRw6pSc',
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
      routesToMatch: ['/payment-methods/international-payments'],
      abExperiments: [
        {
          uniqueHashKey: 'userApiDecomp',
          experimentId: {
            beta: 'OJSKkoQ7EjryIh',
            production: 'OM9gNNXJobDkla',
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
      routesToMatch: ['/subscriptions/settings'],
      abExperiments: [
        {
          uniqueHashKey: 'subscriptions_toggle',
          experimentId: {
            beta: 'OQX4MqIo2lXmFR',
            production: 'OQX1qb9G4chANl',
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
      routesToMatch: ['/checkout-settings/*'],
      abExperiments: [
        {
          uniqueHashKey: 'enableCheckoutV2Configuration',
          experimentId: {
            beta: 'OXHA0BO5xgo9Xu',
            production: 'OXH8UPeCbW0hT5',
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
      routesToMatch: [
        '/account-settings',
        '/website-app-settings/api-keys',
        '/api-keys',
        '/keys',
        '/route/accounts',
        '/business-settings/team',
        '/partners/manage-team',
        '/team',
      ],
      abExperiments: [
        {
          uniqueHashKey: 'enable_modular_onboarding_linked_account',
          experimentId: {
            beta: 'PE67MJdIfalxst',
            production: 'PEifQHS5uNeQJE',
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
          uniqueHashKey: 'curlec_paypal_onboarding',
          experimentId: {
            beta: 'PIOvoDyg5mmRXR',
            production: 'PIOmfUioCCNCC0',
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
      routesToMatch: ['/route/*'],
      abExperiments: [
        {
          uniqueHashKey: 'enable_2fa_batch_upload',
          experimentId: {
            beta: 'PYVxFe8ehz8zit',
            production: 'PYakTWLNXcPKEb',
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
      routesToMatch: ['/checkout-settings/*', '/account-settings'],
      abExperiments: [
        {
          uniqueHashKey: 'checkout_editor_v2_preview',
          experimentId: {
            beta: 'P9gNAICjz7D3Qm',
            production: 'OpUCnfumosxEOC',
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
      routesToMatch: ['/checkout-settings/*', '/account-settings'],
      abExperiments: [
        {
          uniqueHashKey: 'checkout_editor_payment_config',
          experimentId: {
            beta: 'Pad9ccMln91SL4',
            production: 'Pad6LZ8wY2CfFE',
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
      routesToMatch: ['/dashboard', '/checkout-settings/*', '/account-settings'],
      abExperiments: [
        {
          uniqueHashKey: 'checkout_festival_theme',
          experimentId: {
            beta: 'PXptT3UNWJcYHC',
            production: 'PV6hoFfucPwsnL',
          },
          defaultVariant: {
            name: 'control',
            variables: [],
          },
        },
      ],
    },
    {
      routesToMatch: [
        '/dashboard/*',
        '/settlements/*',
        '/instantsettlements/*',
        '/routeinstantsettlements/*',
      ],
      abExperiments: [
        {
          uniqueHashKey: 'capital_is_gtm',
          experimentId: {
            beta: 'P3eJWSuYWs63GF',
            production: 'P3eGB6lvwslrul',
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
          uniqueHashKey: 'capital_is_auto_offer',
          experimentId: {
            beta: 'PA3WdOQkqGuvgp',
            production: 'PA3WdOQkqGuvgp',
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
          uniqueHashKey: 'capital_is_smart_settlement',
          experimentId: {
            beta: 'Pjggm3c9pdS5nb',
            production: 'PjfRphousn9tIQ',
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
          uniqueHashKey: 'is_managed_merchant_account',
          experimentId: {
            beta: 'PN6YUhAbo1OhDs',
            production: 'PNTXEM6WDiRrc1',
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
      routesToMatch: ['/business-settings/ticket-support/*'],
      abExperiments: [
        {
          uniqueHashKey: 'support_ticket_pagination',
          experimentId: {
            beta: 'P3RRfp9aSjvJhe',
            production: 'P3ROFFQs4uE6FV',
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
      routesToMatch: ['/international-settings/firs'],
      abExperiments: [
        {
          uniqueHashKey: 'firs_messaging',
          experimentId: {
            beta: 'OtTIJ3pccGg4JA',
            production: 'OtafuXfv3oQav1',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
              {
                key: 'message',
                value: '',
              },
            ],
          },
        },
      ],
    },
    {
      routesToMatch: ['/website-app-settings/api-keys', '/api-keys', '/keys'],
      abExperiments: [
        {
          uniqueHashKey: 'show_v2_website_flow',
          experimentId: {
            beta: 'PCqn0dnUZLArxK',
            production: 'PCqnbpvKAO807T',
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
      routesToMatch: ['/payments-and-refunds-settings/*'],
      abExperiments: [
        {
          uniqueHashKey: 'pre_fund_withdrawal',
          experimentId: {
            beta: 'PHuJNVsGEvWf8Y',
            production: 'PHv47klOrKceo8',
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
      routesToMatch: ['/onboarding/api-keys'],
      abExperiments: [
        {
          uniqueHashKey: 'show_ftux_V_1Point5',
          experimentId: {
            beta: 'PR2RpndGTDzJKE',
            production: 'PR2XlXjhxUoeDT',
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
      routesToMatch: ['/subscriptions/settings'],
      abExperiments: [
        {
          uniqueHashKey: 'sihub_whitelist',
          experimentId: {
            beta: 'PZ0A6VVGb2YpkD',
            production: 'PZ05jhrki1xpqv',
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
      routesToMatch: ['/payment-methods/international-payments'],
      abExperiments: [
        {
          uniqueHashKey: 'intl_settings_page_revamp',
          experimentId: {
            beta: 'PYYFjWr7ov0Vvf',
            production: 'Pg7K1f7G2HtQMN',
          },
          defaultVariant: {
            name: 'variables',
            variables: [
              {
                key: 'result',
                value: 'off',
              },
              {
                key: 'message',
                value: '',
              },
            ],
          },
        },
      ],
    },
  ],
};
