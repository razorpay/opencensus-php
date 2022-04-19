import * as yup from 'yup';

/*

Structure:
orgName: {
  routeName: {
    environmentType: 'channenID'
  }
}
*/
export const routeToChannelIDMap = {
  rzp: {
    default: {
      dev: 'HTdu8cC7FJEIHC',
      beta: 'HTdu8cC7FJEIHC',
      stage: 'HTdu8cC7FJEIHC',
      production: 'HpP3cspZ3AcuV2',
    },
    HomeCarouselBanner: {
      dev: 'Ifyxxu4bAk99Ev',
      beta: 'Ifyxxu4bAk99Ev',
      stage: 'Ifyxxu4bAk99Ev',
      production: 'IfyyNE821PHDlt',
    },
    home: {
      dev: 'HTdu8cC7FJEIHC',
      beta: 'HTdu8cC7FJEIHC',
      stage: 'HTdu8cC7FJEIHC',
      production: 'HpP3cspZ3AcuV2',
    },
    '/app/dashboard': {
      dev: 'HTdu8cC7FJEIHC',
      beta: 'HTdu8cC7FJEIHC',
      stage: 'HTdu8cC7FJEIHC',
      production: 'HpP3cspZ3AcuV2',
    },
    '/app/payments': {
      dev: 'IUaC3BpMmsMI2A',
      beta: 'IUaC3BpMmsMI2A',
      stage: 'IUaC3BpMmsMI2A',
      production: 'IUaTjGosLMxwHF',
    },
    '/app/settlements': {
      dev: 'IUaDYoZPVkmx8F',
      beta: 'IUaDYoZPVkmx8F',
      stage: 'IUaDYoZPVkmx8F',
      production: 'IUaU0WNV5we4Gj',
    },
    '/app/invoices': {
      dev: 'IUaEJoC102u2MY',
      beta: 'IUaEJoC102u2MY',
      stage: 'IUaEJoC102u2MY',
      production: 'IUaUPbpq4u89ZO',
    },
    '/app/paymentlinks': {
      dev: 'IUaFCF3xi8B5aj',
      beta: 'IUaFCF3xi8B5aj',
      stage: 'IUaFCF3xi8B5aj',
      production: 'IUaUgQA3iTRYBt',
    },
    '/app/paymentpages': {
      dev: 'IUaFl6ClE3CyCc',
      beta: 'IUaFl6ClE3CyCc',
      stage: 'IUaFl6ClE3CyCc',
      production: 'IUaUvIJtwrOXqg',
    },
    '/app/stores/products': {
      dev: 'IUaGWo5r2SBZst',
      beta: 'IUaGWo5r2SBZst',
      stage: 'IUaGWo5r2SBZst',
      production: 'IUaV9sQYsKE8PX',
    },
    '/app/paymentbuttons': {
      dev: 'IUaHAE8m0BVaxg',
      beta: 'IUaHAE8m0BVaxg',
      stage: 'IUaHAE8m0BVaxg',
      production: 'IUaVRJ3uI18czJ',
    },
    '/app/route/payments': {
      dev: 'IUaJDiIuC3Ma5N',
      beta: 'IUaJDiIuC3Ma5N',
      stage: 'IUaJDiIuC3Ma5N',
      production: 'IUaVr2gSHbw6v1',
    },
    '/app/subscriptions': {
      dev: 'IUaJkRqdyoYArL',
      beta: 'IUaJkRqdyoYArL',
      stage: 'IUaJkRqdyoYArL',
      production: 'IUaW6AAEZmcbnD',
    },
    '/app/qr_codes': {
      dev: 'IUaKIZbM3ytwlz',
      beta: 'IUaKIZbM3ytwlz',
      stage: 'IUaKIZbM3ytwlz',
      production: 'IUaWIpskrvdFP2',
    },
    '/app/smartcollect/virtualaccounts': {
      dev: 'IUaLUkKau2dl29',
      beta: 'IUaLUkKau2dl29',
      stage: 'IUaLUkKau2dl29',
      production: 'IUaWYvMsEbgQiC',
    },
    '/app/customers': {
      dev: 'IUaM60NSKGqnDl',
      beta: 'IUaM60NSKGqnDl',
      stage: 'IUaM60NSKGqnDl',
      production: 'IUaWp3TH7ppWW2',
    },
    '/app/offers': {
      dev: 'IUaMYGpvSygfIH',
      beta: 'IUaMYGpvSygfIH',
      stage: 'IUaMYGpvSygfIH',
      production: 'IUaX1E61elMmNg',
    },
    '/app/checkout-rewards': {
      dev: 'IUaNRiUJnEfx1R',
      beta: 'IUaNRiUJnEfx1R',
      stage: 'IUaNRiUJnEfx1R',
      production: 'IUaXIyfL0ASQp1',
    },
    '/app/capital/loans/apply': {
      dev: 'IUaObMY3yXgx4H',
      beta: 'IUaObMY3yXgx4H',
      stage: 'IUaObMY3yXgx4H',
      production: 'IUaXYSKLAxNTeZ',
    },
    '/app/capital/cash-advance/apply': {
      dev: 'IUaPBJVqZtAImJ',
      beta: 'IUaPBJVqZtAImJ',
      stage: 'IUaPBJVqZtAImJ',
      production: 'IUaXuV8iZbFgky',
    },
    '/app/reports': {
      dev: 'IUaPaPexx0avd9',
      beta: 'IUaPaPexx0avd9',
      stage: 'IUaPaPexx0avd9',
      production: 'IUaY7X3lSY4lv6',
    },
    '/app/config': {
      dev: 'IUaQ0XzWCgbVIt',
      beta: 'IUaQ0XzWCgbVIt',
      stage: 'IUaQ0XzWCgbVIt',
      production: 'IUaYKXvc1LZBhQ',
    },
    '/app/webhooks': {
      dev: 'IUaQbhno9K0l8v',
      beta: 'IUaQbhno9K0l8v',
      stage: 'IUaQbhno9K0l8v',
      production: 'IUaYYg6KskPVD4',
    },
    '/app/keys': {
      dev: 'IUaR9YPtVKsZCa',
      beta: 'IUaR9YPtVKsZCa',
      stage: 'IUaR9YPtVKsZCa',
      production: 'IUaYlo9q0VNBiQ',
    },
    '/app/reminders': {
      dev: 'IUaRTtAP1X04ub',
      beta: 'IUaRTtAP1X04ub',
      stage: 'IUaRTtAP1X04ub',
      production: 'IUaZ1RoGho5Qrr',
    },
    '/app/payment-methods': {
      dev: 'IUaSEhwWpxdzIZ',
      beta: 'IUaSEhwWpxdzIZ',
      stage: 'IUaSEhwWpxdzIZ',
      production: 'IUaZC0Vjcujv3n',
    },
    gsExclusiveOffer: {
      dev: 'IiQPZ3bmxiHyoq',
      beta: 'HTdu8cC7FJEIHC',
      stage: 'HTdu8cC7FJEIHC',
      production: 'IiQPZ3bmxiHyoq',
    },
    gs_login_card: {
      dev: 'Il7nf6M5YIxTIo',
      beta: 'Il7nf6M5YIxTIo',
      stage: 'Il7nf6M5YIxTIo',
      production: 'IlAgclzcAgy3Vf',
    },
  },
  banking: {
    default: {
      dev: 'ILpOiPdxsl62QN',
      beta: 'ILpOiPdxsl62QN',
      stage: 'ILpOiPdxsl62QN',
      axis: 'ILpOiPdxsl62QN',
      production: 'IMN6odavPeZlSu',
    },
    HomeCarouselBanner: {
      dev: 'Ifyxxu4bAk99Ev',
      beta: 'Ifyxxu4bAk99Ev',
      stage: 'Ifyxxu4bAk99Ev',
      production: 'IfyyNE821PHDlt',
    },
    home: {
      dev: 'ILpOiPdxsl62QN',
      beta: 'ILpOiPdxsl62QN',
      stage: 'ILpOiPdxsl62QN',
      axis: 'ILpOiPdxsl62QN',
      production: 'IMN6odavPeZlSu',
    },
    '/app/dashboard': {
      dev: 'ILpOiPdxsl62QN',
      beta: 'ILpOiPdxsl62QN',
      stage: 'ILpOiPdxsl62QN',
      production: 'IMN6odavPeZlSu',
    },
    '/app/payments': {
      dev: 'IUb1kW61O1a1sn',
      beta: 'IUb1kW61O1a1sn',
      stage: 'IUb1kW61O1a1sn',
      production: 'IUbWdEiYB6IxSB',
    },
    '/app/settlements': {
      dev: 'IUb2EU3ls3pQLg',
      beta: 'IUb2EU3ls3pQLg',
      stage: 'IUb2EU3ls3pQLg',
      production: 'IUbXroZ4IPnvkR',
    },
    '/app/invoices': {
      dev: 'IUb5o9jQC6R1hD',
      beta: 'IUb5o9jQC6R1hD',
      stage: 'IUb5o9jQC6R1hD',
      production: 'IUbYLs6dyCRWhh',
    },
    '/app/paymentlinks': {
      dev: 'IUb6CfTLkVm8s7',
      beta: 'IUb6CfTLkVm8s7',
      stage: 'IUb6CfTLkVm8s7',
      production: 'IUbYVElJZVNx5x',
    },
    '/app/paymentpages': {
      dev: 'IUb6eyS4S1zwfR',
      beta: 'IUb6eyS4S1zwfR',
      stage: 'IUb6eyS4S1zwfR',
      production: 'IUbYeNUVHsQLM6',
    },
    '/app/stores/products': {
      dev: 'IUb7DoNEIlAkKS',
      beta: 'IUb7DoNEIlAkKS',
      stage: 'IUb7DoNEIlAkKS',
      production: 'IUbYn1kthGbK0x',
    },
    '/app/paymentbuttons': {
      dev: 'IUb7ijf9pXz3Q0',
      beta: 'IUb7ijf9pXz3Q0',
      stage: 'IUb7ijf9pXz3Q0',
      production: 'IUbYwjH0lHvjPm',
    },
    '/app/route/payments': {
      dev: 'IUb8YwQK2N7dfm',
      beta: 'IUb8YwQK2N7dfm',
      stage: 'IUb8YwQK2N7dfm',
      production: 'IUbZ5CbI3TeBx1',
    },
    '/app/subscriptions': {
      dev: 'IUb90sQ9cmNMhv',
      beta: 'IUb90sQ9cmNMhv',
      stage: 'IUb90sQ9cmNMhv',
      production: 'IUbZDiBID4fXIw',
    },
    '/app/qr_codes': {
      dev: 'IUb9g3PdI2PGmp',
      beta: 'IUb9g3PdI2PGmp',
      stage: 'IUb9g3PdI2PGmp',
      production: 'IUbZOOi4EU6bvu',
    },
    '/app/smartcollect/virtualaccounts': {
      dev: 'IUbADeNSX35iuU',
      beta: 'IUbADeNSX35iuU',
      stage: 'IUbADeNSX35iuU',
      production: 'IUbZZkLAJ5XnKG',
    },
    '/app/customers': {
      dev: 'IUbAeUDtGRZrOG',
      beta: 'IUbAeUDtGRZrOG',
      stage: 'IUbAeUDtGRZrOG',
      production: 'IUbZk3MqXMfgKh',
    },
    '/app/offers': {
      dev: 'IUbB9ZsTW5w20b',
      beta: 'IUbB9ZsTW5w20b',
      stage: 'IUbB9ZsTW5w20b',
      production: 'IUbZsR2rdNx29o',
    },
    '/app/checkout-rewards': {
      dev: 'IUbBsS8RXrjU8H',
      beta: 'IUbBsS8RXrjU8H',
      stage: 'IUbBsS8RXrjU8H',
      production: 'IUba0UJJyPGfeO',
    },
    '/app/capital/loans/apply': {
      dev: 'IUbCPobTU3JHED',
      beta: 'IUbCPobTU3JHED',
      stage: 'IUbCPobTU3JHED',
      production: 'IUba9EcskyTQmQ',
    },
    '/app/capital/cash-advance/apply': {
      dev: 'IUbCxRZ2bffOZl',
      beta: 'IUbCxRZ2bffOZl',
      stage: 'IUbCxRZ2bffOZl',
      production: 'IUbb8B9ToT7j9h',
    },
    '/app/reports': {
      dev: 'IUbDdKRISX7Xwq',
      beta: 'IUbDdKRISX7Xwq',
      stage: 'IUbDdKRISX7Xwq',
      production: 'IUbbSOgci9PkPz',
    },
    '/app/config': {
      dev: 'IUbDwb4z5eLdHn',
      beta: 'IUbDwb4z5eLdHn',
      stage: 'IUbDwb4z5eLdHn',
      production: 'IUbbcfgEzt1ZLz',
    },
    '/app/webhooks': {
      dev: 'IUbERxCXGMKSxs',
      beta: 'IUbERxCXGMKSxs',
      stage: 'IUbERxCXGMKSxs',
      production: 'IUbbuXtxtNKVwo',
    },
    '/app/keys': {
      dev: 'IUbF3iX5UE62gt',
      beta: 'IUbF3iX5UE62gt',
      stage: 'IUbF3iX5UE62gt',
      production: 'IUbc5MehL8Xz3t',
    },
    '/app/reminders': {
      dev: 'IUbFX1AMs3qcqC',
      beta: 'IUbFX1AMs3qcqC',
      stage: 'IUbFX1AMs3qcqC',
      production: 'IUbcXRg6xj117A',
    },
    '/app/payment-methods': {
      dev: 'IUbFrNg7pJxXMN',
      beta: 'IUbFrNg7pJxXMN',
      stage: 'IUbFrNg7pJxXMN',
      production: 'IUbcK1TFmKE1Dd',
    },
    gsExclusiveOffer: {
      dev: 'IiQPZ3bmxiHyoq',
      beta: 'HTdu8cC7FJEIHC',
      stage: 'HTdu8cC7FJEIHC',
      production: 'IiQPZ3bmxiHyoq',
    },
    gs_login_card: {
      dev: 'Il7nf6M5YIxTIo',
      beta: 'Il7nf6M5YIxTIo',
      stage: 'Il7nf6M5YIxTIo',
      production: 'IlAgclzcAgy3Vf',
    },
  },
};

export const routeToRouteNameMap = {
  '/app/dashboard': 'home',
  '/app/payments': 'transactions',
  'app/settlements': 'settlements',
  'app/invoices': 'invoices',
  'app/paymentlinks': 'payment-links',
  'app/paymentpages': 'payment-pages',
  '/app/stores/products': 'stores',
  '/app/paymentbuttons': 'payment-buttons',
  '/app/route/payments': 'route',
  '/app/subscriptions': 'subscriptions',
  'app/qr_codes': 'qr_codes',
  '/app/smartcollect/virtualaccounts': 'smart-collect',
  '/app/customers': 'customers',
  '/app/offers': 'offers',
  '/app/checkout-rewards': 'checkout-rewards',
  '/app/capital/loans/apply': 'loans',
  '/app/capital/cash-advance/apply': 'cash-advance',
  '/app/reports': 'reports',
  '/app/config': 'settings-config',
  '/app/webhooks': 'settings-webhooks',
  '/app/keys': 'settings-keys',
  '/app/reminders': 'settings-reminders',
  '/app/payment-methods': 'settings-payment-methods',
};

export const assetNames = {
  ANNOUNCEMENT: 'ANNOUNCEMENT',
  BANNER: 'BANNER',
  EXCLUSIVE_OFFER: 'EXCLUSIVE_OFFER',
  BANNER_CAROUSEL_ITEM: 'BANNER_CAROUSEL_ITEM',
  MODAL: 'GS_MODAL',
};

const trackingDataSchema = yup
  .object()
  .optional()
  .default(undefined)
  .shape({
    campaign: yup.string().required().strict(true),
    campaign_description: yup.string().required().strict(true),
    sub_campaign: yup.string().optional().strict(true),
    sub_campaign_description: yup.string().optional().strict(true),
    campaign_id: yup.string().optional().strict(true),
    sub_campaign_id: yup.string().optional().strict(true),
    meta: yup
      .object()
      .optional()
      .shape({
        product_feature: yup.string().optional().strict(true),
      }),
  });

const urlTest = yup
  .mixed()
  .required()
  .test('checkString', 'error: error in checking string', (text) => typeof text === 'string');

export const growthAssetSchema = {
  [assetNames.ANNOUNCEMENT]: yup.object().shape({
    title: yup.string().required().strict(true),
    description: yup.string().required().strict(true),
    icon: yup.string().required().strict(true),
    id: yup.string().required().strict(true),
    start_ts: yup.number().strict(true).required(),
    end_ts: yup.number().strict(true).required(),
    buttons: yup
      .array()
      .required()
      .of(
        yup.object().shape({
          type: yup.string().required().strict(true),
          label: yup.string().required().strict(true),
          url: urlTest,
        }),
      ),
    l2_content: yup
      .object()
      .optional()
      .default(undefined)
      .shape({
        content: yup.string().required(),
        buttons: yup
          .array()
          .optional()
          .default(undefined)
          .of(
            yup.object().shape({
              type: yup.string().required().strict(true),
              label: yup.string().required().strict(true),
              url: urlTest,
            }),
          ),
      }),
    tracking_data: trackingDataSchema,
  }),
  [assetNames.BANNER]: yup.object().shape({
    title: yup.string().required().strict(true),
    id: yup.string().required().strict(true),
    className: yup.string().optional().strict(true),
    override_priority: yup.boolean().required().strict(true),
    dismissible: yup.boolean().required().strict(true),
    buttons: yup
      .array()
      .optional()
      .default(undefined)
      .of(
        yup.object().shape({
          type: yup.string().required().strict(true),
          label: yup.string().required().strict(true),
          url: yup.string().optional().strict(true),
          id: yup.string().optional().strict(true),
          style: yup.string().optional().strict(true),
        }),
      ),
    content: yup
      .object()
      .required()
      .shape({
        description: yup.string().required().strict(true),
        type: yup.string().optional().strict(true),
      }),
    text_link: yup
      .object()
      .default(undefined)
      .optional()
      .shape({
        url: yup.string().required().strict(true),
        text: yup.string().optional().strict(true),
      }),
    tracking_data: trackingDataSchema,
  }),
};
