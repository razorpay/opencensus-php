import {
  CheckCircleIcon,
  FilePlusIcon,
  FileTextIcon,
  ShoppingCartIcon,
  UserPlusIcon,
} from '@razorpay/blade/components';
import React from 'react';
import {
  getDeviceDeploymentStatus,
  isDeviceDeploymentFlowActivated,
} from 'apps/pos/src/app/utils/deviceDeployment';
import AgreementSigning from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/AgreementSigning';
import DeviceConfiguration from './components/DeviceDeployment/DeviceConfiguration';
import DeviceDetailsContainer from './components/DeviceDeployment/DeviceDetails/';
import DeviceMappingManualComponent from './components/DeviceDeployment/DeviceMapping/DeviceMappingManualComponent';
import DeviceMappingScannerComponent from './components/DeviceDeployment/DeviceMapping/DeviceMappingScannerComponent';
import DeviceMappingSuccessComponent from './components/DeviceDeployment/DeviceMappingSuccess';
import DeviceDeploymentList from './components/DeviceDeployment/DevicesList';
import DeviceTesting from './components/DeviceDeployment/DeviceTesting';
import LanguageConfiguration from './components/DeviceDeployment/LanguageConfiguration';
import DeviceConfirmationForSalesAgent from './components/DeviceOrdering/DeviceConfirmation/DeviceConfirmationForSalesAgent';
import DeviceDeliveryAddressForSaleSalesAgent from './components/DeviceOrdering/DeviceDeliveryAddress/DeviceDeliveryAddressForSaleSalesAgent';
import DevicePaymentForPosSalesAgent from './components/DeviceOrdering/DevicePayment/DevicePaymentForPosSalesAgent';
import DeviceSelectionCatalogForPosSalesAgent from './components/DeviceOrdering/DeviceSelectionCatalog/DeviceSelectionCatalogForPosSalesAgent';
import MerchantKYC from './components/MerchantKYC';
import MerchantNumberVerifySalesAssisted from './components/MerchantRegistration/MerchantNumberVerifySalesAssisted';
import { OnboardingStatesType, OnboardingValuesType } from './providers/useOnboardingContext';
import MerchantAdditionalDetails from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/MerchantAdditionalDetails/MerchantAdditionalDetails';
import {
  getAgreementSigningStatus,
  getProgressFromModularStep,
  isDevicePricingAdditionalDetailsCompletedForPosEkyc,
} from 'apps/pos/src/app/utils/modularConfig';
import { getDeviceStepStatus } from 'apps/pos/src/app/utils/deviceSelection';
import { COMPLETED, getAgreementComponentStatus } from 'apps/pos/src/app/utils/agreementSigning';
import { MODULAR_ADDITIONAL_DETAILS_FIELDS } from 'apps/pos/src/app/types/MerchantAdditionalDetails';
import { MODULAR_DEVICE_FIELDS } from 'apps/pos/src/app/types/DeviceSelection';
import { DEVICE_DEPLOYMENT_FIELDS } from 'apps/pos/src/app/types/DeviceDeployment';
import {
  AvailableComponents,
  AvailableSteps,
  OnboardingComponentType,
  OnboardingStepType,
} from 'apps/pos/src/app/types/common';
import { MODULAR_AGREEMENT_FIELDS } from 'apps/pos/src/app/types/AgreementSigning';
import NACHFormEkycContainer from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/MerchantAdditionalDetails/NACHFormEkyc/NACHFormEkycContainer';
import { ClickAnalytics } from './MerchantOnboardingConfig';
import {
  ANALYTICS_ACTIONS,
  ANALYTICS_EVENTS,
  L1_FUNNEL_STAGE,
  L2_FUNNEL_STAGE,
} from 'apps/pos/src/services/analytics/types';

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
  clickAnalytics?: ClickAnalytics;
}

export const ONBOARDING_STEPS_POS_EKYC: OnboardingStep[] = [
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
    description: 'Provide merchant’s business information to start the POS journey .',
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
    modularKey: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    title: 'Device Selection & Ordering',
    description: 'Help your merchants optimise their transactions with the perfect POS devices',
    getStatus: ({ states }) => getDeviceStepStatus({ modularConfig: states.modularConfig }),
    checkIfDisabled: ({ values, states }) =>
      !values.merchantId || !states.merchantDetails?.activation.isFormSubmitted,
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
  },
  {
    slug: AvailableSteps.ADDITIONAL_DETAILS,
    modularKey: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_STEP,
    title: 'NACH and Additional Details',
    description: 'Add compliance details to complete your merchant profile',
    getStatus: ({ states }) => {
      return getProgressFromModularStep({
        modularConfig: states.modularConfig,
        step: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_STEP,
      });
    },
    checkIfDisabled: ({ values, states }) =>
      !values.merchantId || !states.merchantDetails?.activation.isFormSubmitted,
    checkIfCompleted: ({ states }) =>
      getProgressFromModularStep({
        modularConfig: states.modularConfig,
        step: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_STEP,
      }) === 'completed',
    icon: <FilePlusIcon />,
    components: [
      {
        slug: AvailableComponents.NACH_FORM,
        modularKey: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_COMPONENT,
        view: <NACHFormEkycContainer />,
        getNextComponent: () => AvailableComponents.ADDITIONAL_DETAILS,
      },
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
    description: 'Merchant’s T&C with Razorpay',
    getStatus: ({ states }) => getAgreementSigningStatus({ modularConfig: states.modularConfig }),
    checkIfDisabled: ({ states, values }) =>
      !values.merchantId ||
      !isDevicePricingAdditionalDetailsCompletedForPosEkyc({
        modularConfig: states.modularConfig,
      }),
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
  },
  {
    slug: AvailableSteps.DEVICE_DEPLOYMENT,
    modularKey: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_STEP,
    title: 'Device Deployment',
    description: 'Provide merchant’s business information to start the POS journey.',
    getStatus: ({ states }) => getDeviceDeploymentStatus({ modularConfig: states.modularConfig }),
    checkIfDisabled: ({ states, values }) =>
      !values.merchantId ||
      !getAgreementComponentStatus(states.modularConfig) ||
      !isDeviceDeploymentFlowActivated(states.modularConfig),
    checkIfCompleted: ({ states }) =>
      getProgressFromModularStep({
        modularConfig: states.modularConfig,
        step: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_STEP,
      }) === 'completed',
    icon: <CheckCircleIcon />,
    clickAnalytics: {
      eventName: ANALYTICS_EVENTS.LINK,
      action: ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Image click',
        l1FunnelStage: L1_FUNNEL_STAGE.MERCHANT_ONBOARDING,
        l2FunnelStage: L2_FUNNEL_STAGE.DEVICE_DEPLOYMENT,
        section: 'Device Deployment',
        subSection: 'Device Deployment',
      },
    },
    components: [
      {
        slug: AvailableComponents.DEVICE_DEPLOYMENT_LIST,
        modularKey: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_COMPONENT,
        checkIfLandingPossible: () => true,
        view: <DeviceDeploymentList />,
      },
      {
        slug: AvailableComponents.DEVICE_CONFIGURATION,
        modularKey: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_COMPONENT,
        checkIfLandingPossible: () => true,
        view: <DeviceConfiguration />,
      },
      {
        slug: AvailableComponents.LANGUAGE_CONFIGURATION,
        modularKey: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_COMPONENT,
        checkIfLandingPossible: () => true,
        view: <LanguageConfiguration />,
      },
      {
        slug: AvailableComponents.DEVICE_TESTING,
        modularKey: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_COMPONENT,
        checkIfLandingPossible: () => true,
        view: <DeviceTesting />,
      },
      {
        slug: AvailableComponents.DEVICE_DETAILS,
        modularKey: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_COMPONENT,
        view: <DeviceDetailsContainer />,
      },
      {
        slug: AvailableComponents.DEVICE_MAPPING_SCANNER,
        modularKey: DEVICE_DEPLOYMENT_FIELDS.DEVICE_MAPPING_BY_SCANNING_COMPONENT,
        view: <DeviceMappingScannerComponent />,
      },
      {
        slug: AvailableComponents.DEVICE_MAPPING_MANUAL,
        modularKey: DEVICE_DEPLOYMENT_FIELDS.DEVICE_MAPPING_BY_SCANNING_COMPONENT,
        view: <DeviceMappingManualComponent />,
      },
      {
        slug: AvailableComponents.DEVICE_MAPPING_SUCCESS,
        modularKey: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_COMPONENT,
        view: <DeviceMappingSuccessComponent />,
      },
    ],
  },
];
