import { AvailableSteps } from 'apps/pos/src/app/types/common';
import {
  ANALYTICS_ACTIONS,
  ANALYTICS_EVENTS,
  L1_FUNNEL_STAGE,
  L2_FUNNEL_STAGE,
} from 'apps/pos/src/services/analytics/types';

export const getMockUseOnboardingContext = (icon) => ({
  values: {
    isNewOnboarding: false,
    merchantId: 'PhjLpFaE7tz4cA',
    step: AvailableSteps.DEVICE_SELECTION,
    onboardingSteps: [
      {
        getStatus: () => 'completed',
        checkIfDisabled: () => false,
        clickAnalytics: {
          eventName: ANALYTICS_EVENTS.LINK,
          action: ANALYTICS_ACTIONS.CLICKED,
          properties: {
            label: 'Image click',
            l1FunnelStage: L1_FUNNEL_STAGE.MERCHANT_ONBOARDING,
            l2FunnelStage: L2_FUNNEL_STAGE.AGREEMENT_SIGNING,
            section: 'Merchant Onboarding',
            subSection: 'Agreement Signing',
          },
        },
        slug: 'merchantRegistration',
        modularKey: null,
        title: 'Adding a New Merchant',
        description: 'Verify your Merchant’s mobile number before getting their KYC verified',
        icon,
        components: [
          {
            slug: 'mobileNumberVerify',
            modularKey: null,
            view: {
              key: null,
              ref: null,
              props: {},
              _owner: null,
            },
          },
        ],
      },
      {
        getStatus: () => 'kyc_completed',
        checkIfDisabled: () => false,
        clickAnalytics: {
          eventName: ANALYTICS_EVENTS.LINK,
          action: ANALYTICS_ACTIONS.CLICKED,
          properties: {
            label: 'Image click',
            l1FunnelStage: L1_FUNNEL_STAGE.MERCHANT_ONBOARDING,
            l2FunnelStage: L2_FUNNEL_STAGE.AGREEMENT_SIGNING,
            section: 'Merchant Onboarding',
            subSection: 'Agreement Signing',
          },
        },
        slug: 'merchantKyc',
        modularKey: null,
        title: 'Merchant KYC',
        description: 'Provide merchant’s business information to start the POS journey .',
        icon,
        components: [
          {
            slug: 'merchantKycRedirect',
            modularKey: null,
            view: {
              key: null,
              ref: null,
              props: {},
              _owner: null,
            },
          },
        ],
      },
      {
        getStatus: () => 'completed',
        checkIfDisabled: () => false,
        clickAnalytics: {
          eventName: ANALYTICS_EVENTS.LINK,
          action: ANALYTICS_ACTIONS.CLICKED,
          properties: {
            label: 'Image click',
            l1FunnelStage: L1_FUNNEL_STAGE.MERCHANT_ONBOARDING,
            l2FunnelStage: L2_FUNNEL_STAGE.AGREEMENT_SIGNING,
            section: 'Merchant Onboarding',
            subSection: 'Agreement Signing',
          },
        },
        slug: 'deviceSelection',
        modularKey: 'device_selection_step',
        title: 'Device Selection & Ordering',
        description: 'Help your merchants optimise their transactions with the perfect POS devices',
        icon,
        components: [
          {
            slug: 'deviceSelectionCatalog',
            modularKey: 'device_catalogue_component',
            view: {
              key: null,
              ref: null,
              props: {},
              _owner: null,
            },
            title: 'Choose Suitable Devices for your merchant',
          },
          {
            slug: 'deviceCart',
            modularKey: 'device_cart_component',
            view: {
              key: null,
              ref: null,
              props: {},
              _owner: null,
            },
            title: 'Order Confirmation',
          },
          {
            slug: 'deviceDeliveryAddress',
            modularKey: 'device_delivery_address_component',
            view: {
              key: null,
              ref: null,
              props: {},
              _owner: null,
            },
            title: 'Delivery Address',
          },
          {
            slug: 'devicePayment',
            modularKey: 'device_payment',
            view: {
              key: null,
              ref: null,
              props: {},
              _owner: null,
            },
            title: 'Device Payment',
            isFullScreenLayout: true,
          },
        ],
      },
      {
        getStatus: () => 'pending',
        checkIfDisabled: () => false,
        clickAnalytics: {
          eventName: ANALYTICS_EVENTS.LINK,
          action: ANALYTICS_ACTIONS.CLICKED,
          properties: {
            label: 'Image click',
            l1FunnelStage: L1_FUNNEL_STAGE.MERCHANT_ONBOARDING,
            l2FunnelStage: L2_FUNNEL_STAGE.AGREEMENT_SIGNING,
            section: 'Merchant Onboarding',
            subSection: 'Agreement Signing',
          },
        },
        slug: 'paymentMethods',
        modularKey: 'pricing_step',
        title: 'Payment Methods & Service Selection',
        description: 'Choose the methods, MDRs and any VAS needed by the merchant',
        icon,
        components: [
          {
            slug: 'vasForm',
            modularKey: 'pricing_step',
            view: {
              key: null,
              ref: null,
              props: {},
              _owner: null,
            },
            title: '',
          },
          {
            slug: 'brandEmiForm',
            modularKey: 'brand_emi_form',
            view: {
              key: null,
              ref: null,
              props: {
                brandEmi: true,
              },
              _owner: null,
            },
            title: '',
          },
          {
            slug: 'addedBrandInfo',
            modularKey: 'added_brand_info',
            view: {
              key: null,
              ref: null,
              props: {
                addedBrands: true,
              },
              _owner: null,
            },
            title: '',
          },
          {
            slug: 'nachForm',
            modularKey: 'pricing_step',
            view: {
              key: null,
              ref: null,
              props: {
                nach: true,
              },
              _owner: null,
            },
            title: '',
          },
        ],
      },
      {
        getStatus: () => 'pending',
        checkIfDisabled: () => false,
        clickAnalytics: {
          eventName: ANALYTICS_EVENTS.LINK,
          action: ANALYTICS_ACTIONS.CLICKED,
          properties: {
            label: 'Image click',
            l1FunnelStage: L1_FUNNEL_STAGE.MERCHANT_ONBOARDING,
            l2FunnelStage: L2_FUNNEL_STAGE.AGREEMENT_SIGNING,
            section: 'Merchant Onboarding',
            subSection: 'Agreement Signing',
          },
        },
        slug: 'additionalDetails',
        modularKey: 'additional_details_step',
        title: 'Additional Details',
        description: 'Add miscellaneous information to complete your merchant profile',
        icon,
        components: [
          {
            slug: 'merchantAdditionalDetails',
            modularKey: 'additional_details_component',
            view: {
              key: null,
              ref: null,
              props: {},
              _owner: null,
            },
          },
        ],
      },
      {
        getStatus: () => 'pending',
        checkIfDisabled: () => true,
        clickAnalytics: {
          eventName: ANALYTICS_EVENTS.LINK,
          action: ANALYTICS_ACTIONS.CLICKED,
          properties: {
            label: 'Image click',
            l1FunnelStage: L1_FUNNEL_STAGE.MERCHANT_ONBOARDING,
            l2FunnelStage: L2_FUNNEL_STAGE.AGREEMENT_SIGNING,
            section: 'Merchant Onboarding',
            subSection: 'Agreement Signing',
          },
        },
        slug: 'agreementSigning',
        modularKey: 'agreement_step',
        title: 'Agreement Signing',
        description: 'Merchant’s T&C and Pricing Agreement with Razorpay',
        icon,
        components: [
          {
            slug: 'agreementMode',
            modularKey: 'agreement_component',
            view: {
              key: null,
              ref: null,
              props: {},
              _owner: null,
            },
          },
        ],
      },
    ],
  },
  states: {
    isModularLoading: false,
    isUpdateModularLoading: false,
    isModularFetchError: false,
    isRefetching: false,
    modularConfig: null,
    isPosEkycAgent: false,
  },
  handlers: {
    getOnboardingProgress: () => ({ totalSteps: 6, totalCompletedSteps: 2 }),
    handleStepClick: () => ({}),
    updateModularConfig: () => ({}),
    getFirstComponentOfStep: () => ({}),
    getStepConfigStepSlug: () => ({}),
    getComponentConfigFromStep: () => ({}),
  },
});
