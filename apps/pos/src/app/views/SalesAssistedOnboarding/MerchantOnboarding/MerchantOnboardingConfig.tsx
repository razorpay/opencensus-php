import React from 'react';
import {
  UserPlusIcon,
  FileTextIcon,
  ShoppingCartIcon,
  CheckCircleIcon,
  FilePlusIcon,
  CoinsIcon,
} from '@razorpay/blade/components';
import { OnboardingStatesType, OnboardingValuesType } from './providers/useOnboardingContext';
import DeviceDeliveryAddressForSaleSalesAgent from './components/DeviceOrdering/DeviceDeliveryAddress/DeviceDeliveryAddressForSaleSalesAgent';
import DeviceSelectionCatalogForPosSalesAgent from './components/DeviceOrdering/DeviceSelectionCatalog/DeviceSelectionCatalogForPosSalesAgent';
import DeviceConfirmationForSalesAgent from './components/DeviceOrdering/DeviceConfirmation/DeviceConfirmationForSalesAgent';
import MerchantNumberVerifySalesAssisted from './components/MerchantRegistration/MerchantNumberVerifySalesAssisted';
import DevicePaymentForPosSalesAgent from './components/DeviceOrdering/DevicePayment/DevicePaymentForPosSalesAgent';

import MerchantKYC from './components/MerchantKYC';
import AgreementSigning from './components/AgreementSigning';
import MerchantAdditionalDetails from './components/MerchantAdditionalDetails';
import PaymentMethods from './components/PaymentMethods';
import {
  OnboardingStepType,
  AvailableSteps,
  OnboardingComponentType,
  AvailableComponents,
} from 'apps/pos/src/app/types/common';
import {
  getAgreementSigningStatus,
  getProgressFromModularStep,
  isAddressPresent,
  isDevicePricingAdditionalDetailsCompleted,
  isPosEnabledForMerchant,
} from 'apps/pos/src/app/utils/modularConfig';
import { getDeviceStepStatus } from 'apps/pos/src/app/utils/deviceSelection';
import { MODULAR_DEVICE_FIELDS } from 'apps/pos/src/app/types/DeviceSelection';
import { COMPLETED } from 'apps/pos/src/app/utils/agreementSigning';
import { MODULAR_ADDITIONAL_DETAILS_FIELDS } from 'apps/pos/src/app/types/MerchantAdditionalDetails';
import { MODULAR_AGREEMENT_FIELDS } from 'apps/pos/src/app/types/AgreementSigning';
import { checkIfKycComplete } from 'apps/pos/src/app/utils/merchantActivation';
import {
  ANALYTICS_ACTIONS,
  ANALYTICS_EVENTS,
  EventProperties,
  L1_FUNNEL_STAGE,
  L2_FUNNEL_STAGE,
} from 'apps/pos/src/services/analytics/types';

export interface Component {
  slug: OnboardingComponentType;
  modularKey: string | null;
  title?: string;
  description?: string;
  view: JSX.Element;
  checkIfLandingPossible?: (flag?: boolean) => boolean;
  getNextComponent?: () => OnboardingComponentType | null;
  isFullScreenLayout?: boolean;
}

export interface ClickAnalytics {
  eventName: ANALYTICS_EVENTS;
  action: ANALYTICS_ACTIONS;
  properties: EventProperties;
}

interface MethodArgs {
  states: OnboardingStatesType;
  values: OnboardingValuesType;
}

export interface OnboardingStep {
  slug: OnboardingStepType;
  modularKey: string | null;
  title: string;
  description: string;
  icon: JSX.Element;
  getStatus: (args: MethodArgs) => string;
  checkIfDisabled: (args: MethodArgs) => boolean;
  customOnClickHandler?: () => void;
  checkIfCompleted?: (args: MethodArgs) => boolean;
  components: Component[];
  clickAnalytics?: ClickAnalytics;
}

const MERCHANT_REGISTRATIONS_STEP = {
  slug: AvailableSteps.MERCHANT_REGISTRATION,
  modularKey: null,
  title: 'Adding a New Merchant',
  description: 'Verify your Merchant’s mobile number before getting their KYC verified',
  getStatus: ({ values }) => (!values.merchantId ? 'pending' : 'completed'),
  checkIfDisabled: ({ values }) => !!values.merchantId,
  checkIfCompleted: ({ values }) => !!values.merchantId,
  icon: <UserPlusIcon />,
  components: [
    {
      slug: AvailableComponents.MOBILE_NUMBER_VERIFY,
      modularKey: null,
      checkIfLandingPossible: () => true,
      view: <MerchantNumberVerifySalesAssisted />,
    },
  ],
};

const MERCHANT_KYC = {
  slug: AvailableSteps.MERCHANT_KYC,
  modularKey: null,
  title: 'Merchant KYC',
  description: 'Provide merchant’s business information to start the POS journey .',
  getStatus: ({ states }) => {
    const { merchantDetails } = states;
    const posActivationStatus = merchantDetails?.activation?.posActivationStatus;
    if (
      !posActivationStatus &&
      merchantDetails &&
      checkIfKycComplete({ merchant: merchantDetails })
    ) {
      return 'kyc_completed';
    }

    if (posActivationStatus) return posActivationStatus.toLowerCase();

    return 'pending';
  },
  checkIfDisabled: ({ values }) => !values.merchantId,
  checkIfCompleted: ({ states }) => !!states.merchantDetails?.activation.isFormSubmitted,
  icon: <FileTextIcon />,
  components: [
    {
      slug: AvailableComponents.MERCHANT_KYC_REDIRECT,
      modularKey: null,
      checkIfLandingPossible: () => true,
      view: <MerchantKYC />,
    },
  ],
};

const DEVICE_SELECTION_STEP = {
  slug: AvailableSteps.DEVICE_SELECTION,
  modularKey: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
  title: 'Device Selection & Ordering',
  description: 'Help your merchants optimise their transactions with the perfect POS devices',
  getStatus: ({ states }) => getDeviceStepStatus({ modularConfig: states.modularConfig }),
  checkIfDisabled: ({ values, states }) =>
    !values.merchantId ||
    !isAddressPresent(states.merchantDetails?.business?.address?.registered) ||
    !isPosEnabledForMerchant({ states }),
  checkIfCompleted: ({ states }) =>
    getProgressFromModularStep({
      modularConfig: states.modularConfig,
      step: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    }) === 'completed',
  icon: <ShoppingCartIcon />,
  components: [
    {
      slug: AvailableComponents.DEVICE_SELECTION_CATALOG,
      modularKey: MODULAR_DEVICE_FIELDS.DEVICE_CATALOG_COMPONENT,
      checkIfLandingPossible: () => true,
      view: <DeviceSelectionCatalogForPosSalesAgent />,
      title: 'Choose Suitable Devices for your merchant',
      getNextComponent: () => AvailableComponents.DEVICE_CART,
    },
    {
      slug: AvailableComponents.DEVICE_CART,
      modularKey: MODULAR_DEVICE_FIELDS.DEVICE_CART_COMPONENT,
      checkIfLandingPossible: () => true,
      view: <DeviceConfirmationForSalesAgent />,
      title: 'Order Confirmation',
      getNextComponent: () => AvailableComponents.DEVICE_DELIVERY_ADDRESS,
    },
    {
      slug: AvailableComponents.DEVICE_DELIVERY_ADDRESS,
      modularKey: MODULAR_DEVICE_FIELDS.DEVICE_DELIVERY_ADDRESS_COMPONENT,
      checkIfLandingPossible: () => true,
      view: <DeviceDeliveryAddressForSaleSalesAgent />,
      title: 'Delivery Address',
      getNextComponent: () => AvailableComponents.DEVICE_PAYMENT,
    },
    {
      slug: AvailableComponents.DEVICE_PAYMENT,
      modularKey: MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_COMPONENT,
      checkIfLandingPossible: () => true,
      view: <DevicePaymentForPosSalesAgent />,
      title: 'Device Payment',
      isFullScreenLayout: true,
    },
  ],
};

const PAYMENTS_METHOD_STEP = {
  slug: AvailableSteps.PAYMENT_METHODS,
  modularKey: 'pricing_step',
  title: 'Payment Methods & Service Selection',
  description: 'Choose the methods, MDRs and any VAS needed by the merchant',
  getStatus: ({ states }) => {
    return getProgressFromModularStep({
      modularConfig: states.modularConfig,
      step: 'pricing_step',
    });
  },
  checkIfDisabled: ({ values, states }) => {
    return (
      !values.merchantId ||
      !isAddressPresent(states.merchantDetails?.business?.address?.registered) ||
      !isPosEnabledForMerchant({ states })
    );
  },
  checkIfCompleted: ({ states }) =>
    getProgressFromModularStep({
      modularConfig: states.modularConfig,
      step: 'pricing_step',
    }) === 'completed',
  icon: <CoinsIcon />,
  components: [
    {
      slug: AvailableComponents.PAYMENT_METHODS,
      modularKey: 'pricing_step',
      checkIfLandingPossible: (flag?: boolean) => !!flag,
      view: <PaymentMethods />,
      title: '',
      getNextComponent: () => AvailableComponents.BRAND_EMI_FORM,
    },
    {
      slug: AvailableComponents.BRAND_EMI_FORM,
      modularKey: 'brand_emi_form',
      checkIfLandingPossible: (flag?: boolean) => !!flag,
      view: <PaymentMethods brandEmi />,
      title: '',
      getNextComponent: () => AvailableComponents.ADDED_BRAND_INFO,
    },
    {
      slug: AvailableComponents.ADDED_BRAND_INFO,
      modularKey: 'added_brand_info',
      checkIfLandingPossible: (flag?: boolean) => !!flag,
      view: <PaymentMethods addedBrands />,
      title: '',
      getNextComponent: () => AvailableComponents.NACH_FORM,
    },
    {
      slug: AvailableComponents.NACH_FORM,
      modularKey: 'pricing_step',
      checkIfLandingPossible: (flag?: boolean) => !!flag,
      view: <PaymentMethods nach />,
      title: '',
    },
  ],
};

const ADDITIONAL_DETAILS_STEP = {
  slug: AvailableSteps.ADDITIONAL_DETAILS,
  modularKey: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_STEP,
  title: 'Additional Details',
  description: 'Add miscellaneous information to complete your merchant profile',
  getStatus: ({ states }) => {
    return getProgressFromModularStep({
      modularConfig: states.modularConfig,
      step: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_STEP,
    });
  },
  checkIfDisabled: ({ values, states }) =>
    !values.merchantId ||
    !isAddressPresent(states.merchantDetails?.business?.address?.registered) ||
    !isPosEnabledForMerchant({ states }),
  checkIfCompleted: ({ states }) =>
    getProgressFromModularStep({
      modularConfig: states.modularConfig,
      step: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_STEP,
    }) === 'completed',
  icon: <FilePlusIcon />,
  components: [
    {
      slug: AvailableComponents.ADDITIONAL_DETAILS,
      modularKey: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_COMPONENT,
      checkIfLandingPossible: () => true,
      view: <MerchantAdditionalDetails />,
    },
  ],
};

const AGREEMENT_SIGNING_STEP = {
  slug: AvailableSteps.AGREEMENT_SIGNING,
  modularKey: MODULAR_AGREEMENT_FIELDS.AGREEMENT_STEP,
  title: 'Agreement Signing',
  description: 'Merchant’s T&C and Pricing Agreement with Razorpay',
  getStatus: ({ states }) => getAgreementSigningStatus({ modularConfig: states.modularConfig }),
  checkIfDisabled: ({ states, values }) =>
    !values.merchantId ||
    !states.merchantDetails?.activation.isFormSubmitted ||
    !isDevicePricingAdditionalDetailsCompleted({ modularConfig: states.modularConfig }) ||
    !isPosEnabledForMerchant({ states }),
  checkIfCompleted: ({ states }) =>
    getAgreementSigningStatus({ modularConfig: states.modularConfig }) === COMPLETED,
  icon: <CheckCircleIcon />,
  components: [
    {
      slug: AvailableComponents.AGREEMENT_SIGNING,
      modularKey: MODULAR_AGREEMENT_FIELDS.AGREEMENT_COMPONENT,
      checkIfLandingPossible: () => true,
      view: <AgreementSigning />,
    },
  ],
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
};

export const ONBOARDING_STEPS: OnboardingStep[] = [
  MERCHANT_REGISTRATIONS_STEP,
  MERCHANT_KYC,
  DEVICE_SELECTION_STEP,
  PAYMENTS_METHOD_STEP,
  ADDITIONAL_DETAILS_STEP,
  AGREEMENT_SIGNING_STEP,
];
