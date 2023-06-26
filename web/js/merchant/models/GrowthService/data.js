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
      production: 'HpP3cspZ3AcuV2',
    },
    home: {
      dev: 'HTdu8cC7FJEIHC',
      beta: 'HTdu8cC7FJEIHC',
      stage: 'HTdu8cC7FJEIHC',
      production: 'HpP3cspZ3AcuV2',
    },
    '/dashboard': {
      dev: 'HTdu8cC7FJEIHC',
      beta: 'HTdu8cC7FJEIHC',
      stage: 'HTdu8cC7FJEIHC',
      production: 'HpP3cspZ3AcuV2',
    },
    '/payments': {
      dev: 'IUaC3BpMmsMI2A',
      beta: 'IUaC3BpMmsMI2A',
      stage: 'IUaC3BpMmsMI2A',
      production: 'IUaTjGosLMxwHF',
    },
    '/settlements': {
      dev: 'IUaDYoZPVkmx8F',
      beta: 'IUaDYoZPVkmx8F',
      stage: 'IUaDYoZPVkmx8F',
      production: 'IUaU0WNV5we4Gj',
    },
    '/affordability/widget': {
      dev: 'L7zp8I03kDXAcG',
      beta: 'L7zp8I03kDXAcG',
      stage: 'L7zp8I03kDXAcG',
      production: 'L7zkfTu5Mwxg8a',
    },
    '/invoices': {
      dev: 'IUaEJoC102u2MY',
      beta: 'IUaEJoC102u2MY',
      stage: 'IUaEJoC102u2MY',
      production: 'IUaUPbpq4u89ZO',
    },
    '/paymentlinks': {
      dev: 'IUaFCF3xi8B5aj',
      beta: 'IUaFCF3xi8B5aj',
      stage: 'IUaFCF3xi8B5aj',
      production: 'IUaUgQA3iTRYBt',
    },
    '/paymentpages': {
      dev: 'IUaFl6ClE3CyCc',
      beta: 'IUaFl6ClE3CyCc',
      stage: 'IUaFl6ClE3CyCc',
      production: 'IUaUvIJtwrOXqg',
    },
    '/stores/products': {
      dev: 'IUaGWo5r2SBZst',
      beta: 'IUaGWo5r2SBZst',
      stage: 'IUaGWo5r2SBZst',
      production: 'IUaV9sQYsKE8PX',
    },
    '/paymentbuttons': {
      dev: 'IUaHAE8m0BVaxg',
      beta: 'IUaHAE8m0BVaxg',
      stage: 'IUaHAE8m0BVaxg',
      production: 'IUaVRJ3uI18czJ',
    },
    '/route/payments': {
      dev: 'IUaJDiIuC3Ma5N',
      beta: 'IUaJDiIuC3Ma5N',
      stage: 'IUaJDiIuC3Ma5N',
      production: 'IUaVr2gSHbw6v1',
    },
    '/subscriptions': {
      dev: 'IUaJkRqdyoYArL',
      beta: 'IUaJkRqdyoYArL',
      stage: 'IUaJkRqdyoYArL',
      production: 'IUaW6AAEZmcbnD',
    },
    '/qr_codes': {
      dev: 'IUaKIZbM3ytwlz',
      beta: 'IUaKIZbM3ytwlz',
      stage: 'IUaKIZbM3ytwlz',
      production: 'IUaWIpskrvdFP2',
    },
    '/smartcollect/virtualaccounts': {
      dev: 'IUaLUkKau2dl29',
      beta: 'IUaLUkKau2dl29',
      stage: 'IUaLUkKau2dl29',
      production: 'IUaWYvMsEbgQiC',
    },
    '/customers': {
      dev: 'IUaM60NSKGqnDl',
      beta: 'IUaM60NSKGqnDl',
      stage: 'IUaM60NSKGqnDl',
      production: 'IUaWp3TH7ppWW2',
    },
    '/offers': {
      dev: 'IUaMYGpvSygfIH',
      beta: 'IUaMYGpvSygfIH',
      stage: 'IUaMYGpvSygfIH',
      production: 'IUaX1E61elMmNg',
    },
    '/checkout-rewards': {
      dev: 'IUaNRiUJnEfx1R',
      beta: 'IUaNRiUJnEfx1R',
      stage: 'IUaNRiUJnEfx1R',
      production: 'IUaXIyfL0ASQp1',
    },
    '/capital/loans/apply': {
      dev: 'IUaObMY3yXgx4H',
      beta: 'IUaObMY3yXgx4H',
      stage: 'IUaObMY3yXgx4H',
      production: 'IUaXYSKLAxNTeZ',
    },
    '/capital/cash-advance/apply': {
      dev: 'IUaPBJVqZtAImJ',
      beta: 'IUaPBJVqZtAImJ',
      stage: 'IUaPBJVqZtAImJ',
      production: 'IUaXuV8iZbFgky',
    },
    '/reports': {
      dev: 'IUaPaPexx0avd9',
      beta: 'IUaPaPexx0avd9',
      stage: 'IUaPaPexx0avd9',
      production: 'IUaY7X3lSY4lv6',
    },
    '/config': {
      dev: 'IUaQ0XzWCgbVIt',
      beta: 'IUaQ0XzWCgbVIt',
      stage: 'IUaQ0XzWCgbVIt',
      production: 'IUaYKXvc1LZBhQ',
    },
    '/webhooks': {
      dev: 'IUaQbhno9K0l8v',
      beta: 'IUaQbhno9K0l8v',
      stage: 'IUaQbhno9K0l8v',
      production: 'IUaYYg6KskPVD4',
    },
    '/website-app-settings/webhooks': {
      dev: 'IUaQbhno9K0l8v',
      beta: 'IUaQbhno9K0l8v',
      stage: 'IUaQbhno9K0l8v',
      production: 'IUaYYg6KskPVD4',
    },
    '/keys': {
      dev: 'IUaR9YPtVKsZCa',
      beta: 'IUaR9YPtVKsZCa',
      stage: 'IUaR9YPtVKsZCa',
      production: 'IUaYlo9q0VNBiQ',
    },
    '/website-app-settings/keys': {
      dev: 'IUaR9YPtVKsZCa',
      beta: 'IUaR9YPtVKsZCa',
      stage: 'IUaR9YPtVKsZCa',
      production: 'IUaYlo9q0VNBiQ',
    },
    '/partners': {
      dev: 'KYvkH86N9Sy9A1',
      beta: 'KYvkH86N9Sy9A1',
      stage: 'KYvkH86N9Sy9A1',
      production: 'KYvhzKIY0r6zhJ',
    },
    '/reminders': {
      dev: 'IUaRTtAP1X04ub',
      beta: 'IUaRTtAP1X04ub',
      stage: 'IUaRTtAP1X04ub',
      production: 'IUaZ1RoGho5Qrr',
    },
    '/payment-methods': {
      dev: 'IUaSEhwWpxdzIZ',
      beta: 'IUaSEhwWpxdzIZ',
      stage: 'IUaSEhwWpxdzIZ',
      production: 'IUaZC0Vjcujv3n',
    },
    '/pricing-plans': {
      dev: 'LGi7Vl94BsVXu9',
      beta: 'LGi7Vl94BsVXu9',
      stage: 'LGi7Vl94BsVXu9',
      production: 'LBWmAotEpITeXL',
    },
    '/pricing/pricing-plans': {
      dev: 'LGi7Vl94BsVXu9',
      beta: 'LGi7Vl94BsVXu9',
      stage: 'LGi7Vl94BsVXu9',
      production: 'LBWmAotEpITeXL',
    },
    gsExclusiveOffer: {
      dev: 'IiQPZ3bmxiHyoq',
      beta: 'HTdu8cC7FJEIHC',
      stage: 'HTdu8cC7FJEIHC',
      production: 'HpP3cspZ3AcuV2',
    },
    gs_login_card: {
      dev: 'Il7nf6M5YIxTIo',
      beta: 'Il7nf6M5YIxTIo',
      stage: 'Il7nf6M5YIxTIo',
      production: 'IlAgclzcAgy3Vf',
    },
    '/razorpayx': {
      dev: 'LIzxAJvJlqWT0m',
      beta: 'LIzxAJvJlqWT0m',
      stage: 'LIzxAJvJlqWT0m',
      production: 'LLrHYG5dbSsYCg',
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
      production: 'IMN6odavPeZlSu',
    },
    home: {
      dev: 'ILpOiPdxsl62QN',
      beta: 'ILpOiPdxsl62QN',
      stage: 'ILpOiPdxsl62QN',
      axis: 'ILpOiPdxsl62QN',
      production: 'IMN6odavPeZlSu',
    },
    '/dashboard': {
      dev: 'ILpOiPdxsl62QN',
      beta: 'ILpOiPdxsl62QN',
      stage: 'ILpOiPdxsl62QN',
      production: 'IMN6odavPeZlSu',
    },
    '/payments': {
      dev: 'IUb1kW61O1a1sn',
      beta: 'IUb1kW61O1a1sn',
      stage: 'IUb1kW61O1a1sn',
      production: 'IUbWdEiYB6IxSB',
    },
    '/settlements': {
      dev: 'IUb2EU3ls3pQLg',
      beta: 'IUb2EU3ls3pQLg',
      stage: 'IUb2EU3ls3pQLg',
      production: 'IUbXroZ4IPnvkR',
    },
    '/invoices': {
      dev: 'IUb5o9jQC6R1hD',
      beta: 'IUb5o9jQC6R1hD',
      stage: 'IUb5o9jQC6R1hD',
      production: 'IUbYLs6dyCRWhh',
    },
    '/paymentlinks': {
      dev: 'IUb6CfTLkVm8s7',
      beta: 'IUb6CfTLkVm8s7',
      stage: 'IUb6CfTLkVm8s7',
      production: 'IUbYVElJZVNx5x',
    },
    '/paymentpages': {
      dev: 'IUb6eyS4S1zwfR',
      beta: 'IUb6eyS4S1zwfR',
      stage: 'IUb6eyS4S1zwfR',
      production: 'IUbYeNUVHsQLM6',
    },
    '/stores/products': {
      dev: 'IUb7DoNEIlAkKS',
      beta: 'IUb7DoNEIlAkKS',
      stage: 'IUb7DoNEIlAkKS',
      production: 'IUbYn1kthGbK0x',
    },
    '/paymentbuttons': {
      dev: 'IUb7ijf9pXz3Q0',
      beta: 'IUb7ijf9pXz3Q0',
      stage: 'IUb7ijf9pXz3Q0',
      production: 'IUbYwjH0lHvjPm',
    },
    '/route/payments': {
      dev: 'IUb8YwQK2N7dfm',
      beta: 'IUb8YwQK2N7dfm',
      stage: 'IUb8YwQK2N7dfm',
      production: 'IUbZ5CbI3TeBx1',
    },
    '/subscriptions': {
      dev: 'IUb90sQ9cmNMhv',
      beta: 'IUb90sQ9cmNMhv',
      stage: 'IUb90sQ9cmNMhv',
      production: 'IUbZDiBID4fXIw',
    },
    '/qr_codes': {
      dev: 'IUb9g3PdI2PGmp',
      beta: 'IUb9g3PdI2PGmp',
      stage: 'IUb9g3PdI2PGmp',
      production: 'IUbZOOi4EU6bvu',
    },
    '/smartcollect/virtualaccounts': {
      dev: 'IUbADeNSX35iuU',
      beta: 'IUbADeNSX35iuU',
      stage: 'IUbADeNSX35iuU',
      production: 'IUbZZkLAJ5XnKG',
    },
    '/customers': {
      dev: 'IUbAeUDtGRZrOG',
      beta: 'IUbAeUDtGRZrOG',
      stage: 'IUbAeUDtGRZrOG',
      production: 'IUbZk3MqXMfgKh',
    },
    '/offers': {
      dev: 'IUbB9ZsTW5w20b',
      beta: 'IUbB9ZsTW5w20b',
      stage: 'IUbB9ZsTW5w20b',
      production: 'IUbZsR2rdNx29o',
    },
    '/checkout-rewards': {
      dev: 'IUbBsS8RXrjU8H',
      beta: 'IUbBsS8RXrjU8H',
      stage: 'IUbBsS8RXrjU8H',
      production: 'IUba0UJJyPGfeO',
    },
    '/capital/loans/apply': {
      dev: 'IUbCPobTU3JHED',
      beta: 'IUbCPobTU3JHED',
      stage: 'IUbCPobTU3JHED',
      production: 'IUba9EcskyTQmQ',
    },
    '/capital/cash-advance/apply': {
      dev: 'IUbCxRZ2bffOZl',
      beta: 'IUbCxRZ2bffOZl',
      stage: 'IUbCxRZ2bffOZl',
      production: 'IUbb8B9ToT7j9h',
    },
    '/reports': {
      dev: 'IUbDdKRISX7Xwq',
      beta: 'IUbDdKRISX7Xwq',
      stage: 'IUbDdKRISX7Xwq',
      production: 'IUbbSOgci9PkPz',
    },
    '/config': {
      dev: 'IUbDwb4z5eLdHn',
      beta: 'IUbDwb4z5eLdHn',
      stage: 'IUbDwb4z5eLdHn',
      production: 'IUbbcfgEzt1ZLz',
    },
    '/webhooks': {
      dev: 'IUbERxCXGMKSxs',
      beta: 'IUbERxCXGMKSxs',
      stage: 'IUbERxCXGMKSxs',
      production: 'IUbbuXtxtNKVwo',
    },
    '/keys': {
      dev: 'IUbF3iX5UE62gt',
      beta: 'IUbF3iX5UE62gt',
      stage: 'IUbF3iX5UE62gt',
      production: 'IUbc5MehL8Xz3t',
    },
    '/reminders': {
      dev: 'IUbFX1AMs3qcqC',
      beta: 'IUbFX1AMs3qcqC',
      stage: 'IUbFX1AMs3qcqC',
      production: 'IUbcXRg6xj117A',
    },
    '/partners': {
      dev: 'KYvkH86N9Sy9A1',
      beta: 'KYvkH86N9Sy9A1',
      stage: 'KYvkH86N9Sy9A1',
      production: 'KYvhzKIY0r6zhJ',
    },
    '/payment-methods': {
      dev: 'IUbFrNg7pJxXMN',
      beta: 'IUbFrNg7pJxXMN',
      stage: 'IUbFrNg7pJxXMN',
      production: 'IUbcK1TFmKE1Dd',
    },
    '/pricing-plans': {
      dev: 'LGi7Vl94BsVXu9',
      beta: 'LGi7Vl94BsVXu9',
      stage: 'LGi7Vl94BsVXu9',
      production: 'LBWmAotEpITeXL',
    },
    '/pricing/pricing-plans': {
      dev: 'LGi7Vl94BsVXu9',
      beta: 'LGi7Vl94BsVXu9',
      stage: 'LGi7Vl94BsVXu9',
      production: 'LBWmAotEpITeXL',
    },
    gsExclusiveOffer: {
      dev: 'IiQPZ3bmxiHyoq',
      beta: 'HTdu8cC7FJEIHC',
      stage: 'HTdu8cC7FJEIHC',
      production: 'IMN6odavPeZlSu',
    },
    gs_login_card: {
      dev: 'Il7nf6M5YIxTIo',
      beta: 'Il7nf6M5YIxTIo',
      stage: 'Il7nf6M5YIxTIo',
      production: 'IlAgclzcAgy3Vf',
    },
    '/razorpayx': {
      dev: 'LIzxAJvJlqWT0m',
      beta: 'LIzxAJvJlqWT0m',
      stage: 'LIzxAJvJlqWT0m',
      production: 'LLrHYG5dbSsYCg',
    },
  },
};

export const routeToRouteNameMap = {
  '/dashboard': 'home',
  '/payments': 'transactions',
  '/settlements': 'settlements',
  '/affordability/widget': 'affordability-widget',
  '/invoices': 'invoices',
  '/paymentlinks': 'payment-links',
  '/paymentpages': 'payment-pages',
  '/stores/products': 'stores',
  '/paymentbuttons': 'payment-buttons',
  '/route/payments': 'route',
  '/subscriptions': 'subscriptions',
  '/qr_codes': 'qr_codes',
  '/smartcollect/virtualaccounts': 'smart-collect',
  '/customers': 'customers',
  '/offers': 'offers',
  '/checkout-rewards': 'checkout-rewards',
  '/capital/loans/apply': 'loans',
  '/capital/cash-advance/apply': 'cash-advance',
  '/reports': 'reports',
  '/config': 'settings-config',
  '/webhooks': 'settings-webhooks',
  '/website-app-settings/webhooks': 'settings-webhooks',
  '/website-app-settings/keys': 'settings-webhooks',
  '/keys': 'settings-keys',
  '/partners': 'partners',
  '/reminders': 'settings-reminders',
  '/payment-methods': 'settings-payment-methods',
  '/pricing-plans': 'pricing-plans',
  '/pricing/pricing-plans': 'pricing-plans',
  '/razorpayx': 'x-banking-widget',
};

export const eventToGrowthEventTypeMap = {
  'dashboard.click.notification.card.viewed': 'IMPRESSION',
  'merchant_dashboard.impression_banner': 'IMPRESSION',
  'merchant_dashboard.display_offer_for_you': 'IMPRESSION',
  'login.non_login_card.shown': 'IMPRESSION',
  carousel_banner_notification1: 'IMPRESSION',
  'merchant_dashboard.click_close.initiated': 'IMPRESSION', // pricing bundle -  close
  'dashboard.appswitcher.app_shown': 'IMPRESSION',
  'merchant_dashboard.not_interested.initiated': 'DISMISSAL', // pricing bundle - not interested
  'merchant_dashboard.subscription_checkout.success': 'DISMISSAL', // pricing bundle - payment success
  'merchant_dashboard.banner_close': 'DISMISSAL',
  carousel_banner_not_interested: 'DISMISSAL',
};

export const assetNames = {
  ANNOUNCEMENT: 'ANNOUNCEMENT',
  JSON_SCHEMA: 'JSON_SCHEMA',
  PRICING_BUNDLE: 'PRICING_BUNDLE',
  BANNER: 'BANNER',
  EXCLUSIVE_OFFER: 'EXCLUSIVE_OFFER',
  BANNER_CAROUSEL_ITEM: 'BANNER_CAROUSEL_ITEM',
  MODAL: 'GS_MODAL',
  X_BANKING_WIDGET: 'X_BANKING_WIDGET',
};

export const namespace = 'PG_DASHBOARD';

const trackingDataSchema = yup
  .object()
  .optional()
  .default(undefined)
  .shape({
    campaign: yup.string().required().strict(true),
    campaign_description: yup.string().optional().strict(true),
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
  [assetNames.PRICING_BUNDLE]: yup.object().shape({
    featureIdOrder: yup.array().of(yup.string()).required().strict(true),
    featureIdToFeatureCopyMap: yup.object().required().strict(true).shape({
      f1: yup.string(),
      f2: yup.string(),
      f3: yup.string(),
      f4: yup.string(),
      f5: yup.string(),
      f6: yup.string(),
      f7: yup.string(),
      f8: yup.string(),
      f9: yup.string(),
    }),
    header: yup
      .object()
      .required()
      .strict(true)
      .shape({
        icon: yup
          .object()
          .required()
          .strict(true)
          .shape({
            alt: yup.string().required().strict(true),
            src: yup.string().required().strict(true),
          }),
        pillText: yup.string().required().strict(true),
        title: yup.string().required().strict(true),
      }),
    heroImage: yup
      .object()
      .required()
      .strict(true)
      .shape({
        alt: yup.string().required().strict(true),
        src: yup.string().required().strict(true),
      }),
    pricingPlans: yup
      .array()
      .required()
      .strict(true)
      .of(
        yup
          .object()
          .required()
          .strict(true)
          .shape({
            annualPrice: yup.number().required().strict(true),
            button: yup
              .object()
              .required()
              .strict(true)
              .shape({
                label: yup.string().required().strict(true),
                variant: yup.string().required().strict(true),
              }),
            description: yup.string().optional().strict(true),
            f1: yup.object().required().strict(true).shape({
              annual: yup.string(),
              monthly: yup.string(),
            }),
            f2: yup.object().required().strict(true).shape({
              annual: yup.string(),
              monthly: yup.string(),
            }),
            f3: yup.object().required().strict(true).shape({
              annual: yup.string(),
              monthly: yup.string(),
            }),
            f4: yup.object().required().strict(true).shape({
              annual: yup.string(),
              monthly: yup.string(),
            }),
            f5: yup.object().required().strict(true).shape({
              annual: yup.string(),
              monthly: yup.string(),
            }),
            f6: yup.object().required().strict(true).shape({
              annual: yup.string(),
              monthly: yup.string(),
            }),
            f7: yup.object().required().strict(true).shape({
              annual: yup.string(),
              monthly: yup.string(),
            }),
            f8: yup.object().required().strict(true).shape({
              annual: yup.string(),
              monthly: yup.string(),
            }),
            f9: yup.object().required().strict(true).shape({
              annual: yup.string(),
              monthly: yup.string(),
            }),
            icon: yup
              .object()
              .required()
              .strict(true)
              .shape({
                alt: yup.string().required().strict(true),
                src: yup.string().required().strict(true),
              }),
            id: yup.string().required().strict(true),
            isRecommended: yup.boolean(),
            monthlyPrice: yup.number().required().strict(true),
            notIncludedFeatureOfferings: yup.array().of(yup.string()),
            title: yup.string().required().strict(true),
          }),
      ),
    theme: yup.string().required().strict(true),
    tracking_data: trackingDataSchema,
  }),
};
