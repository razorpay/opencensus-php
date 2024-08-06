import React from 'react';
import {
  UserPlusIcon,
  FileTextIcon,
  ShoppingCartIcon,
  CheckCircleIcon,
  FilePlusIcon,
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
import {
  OnboardingStepType,
  AvailableSteps,
  OnboardingComponentType,
  AvailableComponents,
} from 'apps/pos/src/app/types/common';
import {
  getProgressFromModularStep,
  isDevicePricingAdditionalDetailsCompleted,
} from 'apps/pos/src/app/utils/modularConfig';
import { getDeviceStepStatus } from 'apps/pos/src/app/utils/deviceSelection';
import { getAgreementStepStatus } from 'apps/pos/src/app/utils/agreementSigning';
import { MODULAR_ADDITIONAL_DETAILS_FIELDS } from 'apps/pos/src/app/types/MerchantAdditionalDetails';
import { MODULAR_AGREEMENT_FIELDS } from 'apps/pos/src/app/types/AgreementSigning';

export interface Component {
  slug: OnboardingComponentType;
  modularKey: string | null;
  title?: string;
  description?: string;
  view: JSX.Element;
  checkIfLandingPossible?: () => boolean;
  getNextComponent?: () => OnboardingComponentType | null;
  isFullScreenLayout?: boolean;
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
}

export const ONBOARDING_STEPS: OnboardingStep[] = [
  {
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
  },
  {
    slug: AvailableSteps.MERCHANT_KYC,
    modularKey: null,
    title: 'Merchant KYC',
    description: 'Provide merchant’s business information to start the POS jounrey .',
    getStatus: ({ states }) => {
      if (states?.merchantDetails?.activation?.isFormSubmitted) return 'kyc_completed';
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
  },
  {
    slug: AvailableSteps.DEVICE_SELECTION,
    modularKey: 'device_selection_step',
    title: 'Device Selection & Ordering',
    description: 'Help your merchants optimise their transactions with the perfect POS devices',
    getStatus: ({ states }) => getDeviceStepStatus({ modularConfig: states.modularConfig }),
    checkIfDisabled: ({ values }) => !values.merchantId,
    checkIfCompleted: ({ states }) =>
      getProgressFromModularStep({
        modularConfig: states.modularConfig,
        step: 'device_selection_step',
      }) === 'completed',
    icon: <ShoppingCartIcon />,
    components: [
      {
        slug: AvailableComponents.DEVICE_SELECTION_CATALOG,
        modularKey: 'device_catalogue_component',
        checkIfLandingPossible: () => true,
        view: <DeviceSelectionCatalogForPosSalesAgent />,
        title: 'Choose Suitable Devices for your merchant',
        getNextComponent: () => AvailableComponents.DEVICE_CART,
      },
      {
        slug: AvailableComponents.DEVICE_CART,
        modularKey: 'device_cart_component',
        checkIfLandingPossible: () => true,
        view: <DeviceConfirmationForSalesAgent />,
        title: 'Order Confirmation',
        getNextComponent: () => AvailableComponents.DEVICE_DELIVERY_ADDRESS,
      },
      {
        slug: AvailableComponents.DEVICE_DELIVERY_ADDRESS,
        modularKey: 'device_delivery_address_component',
        checkIfLandingPossible: () => true,
        view: <DeviceDeliveryAddressForSaleSalesAgent />,
        title: 'Delivery Address',
        getNextComponent: () => AvailableComponents.DEVICE_PAYMENT,
      },
      {
        slug: AvailableComponents.DEVICE_PAYMENT,
        modularKey: 'device_payment',
        checkIfLandingPossible: () => true,
        view: <DevicePaymentForPosSalesAgent />,
        title: 'Device Payment',
        isFullScreenLayout: true,
      },
    ],
  },
  {
    slug: AvailableSteps.ADDITIONAL_DETAILS,
    modularKey: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_STEP,
    title: 'Additional Details',
    description: 'Provide merchant’s business information to start the POS journey ',
    getStatus: ({ states }) => {
      return getProgressFromModularStep({
        modularConfig: states.modularConfig,
        step: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_STEP,
      });
    },
    checkIfDisabled: ({ values }) => !values.merchantId,
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
  },
  {
    slug: AvailableSteps.AGREEMENT_SIGNING,
    modularKey: MODULAR_AGREEMENT_FIELDS.AGREEMENT_STEP,
    title: 'Agreement Signing',
    description: 'Merchant’s T&C and Pricing Agreement with Razorpay',
    getStatus: ({ states }) => getAgreementStepStatus({ modularConfig: states.modularConfig }),
    checkIfDisabled: ({ states, values }) =>
      !values.merchantId ||
      !isDevicePricingAdditionalDetailsCompleted({ modularConfig: states.modularConfig }),
    checkIfCompleted: ({ states }) =>
      getProgressFromModularStep({
        modularConfig: states.modularConfig,
        step: MODULAR_AGREEMENT_FIELDS.AGREEMENT_STEP,
      }) === 'completed',
    icon: <CheckCircleIcon />,
    components: [
      {
        slug: AvailableComponents.AGREEMENT_SIGNING,
        modularKey: MODULAR_AGREEMENT_FIELDS.AGREEMENT_COMPONENT,
        checkIfLandingPossible: () => true,
        view: <AgreementSigning />,
      },
    ],
  },
];
