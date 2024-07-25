import React from 'react';
import { UserPlusIcon, FileTextIcon, ShoppingCartIcon } from '@razorpay/blade/components';
import { OnboardingStatesType, OnboardingValuesType } from './providers/useOnboardingContext';
import MerchantNumberVerifySalesAssisted from './components/MerchantRegistration/MerchantNumberVerifySalesAssisted';

import MerchantKYC from './components/MerchantKYC';
import {
  OnboardingStepType,
  AvailableSteps,
  OnboardingComponentType,
  AvailableComponents,
} from 'apps/pos/src/app/types/common';

export interface Component {
  slug: OnboardingComponentType;
  modularKey: string | null;
  title?: string;
  description?: string;
  view: JSX.Element;
  checkIfLandingPossible?: () => boolean;
  getNextComponent?: () => OnboardingComponentType | null;
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
    getStatus: () => 'under_review',
    checkIfDisabled: ({ values }) => !values.merchantId,
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
    modularKey: null,
    title: 'Device Selection & Ordering',
    description: 'Help your merchants optimise their transactions with the perfect POS devices',
    getStatus: () => '',
    checkIfDisabled: ({ values }) => !values.merchantId,
    icon: <ShoppingCartIcon />,
    components: [],
  },
];
