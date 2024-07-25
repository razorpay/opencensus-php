import React from 'react';
import { OnboardingStep } from '../../../MerchantOnboardingConfig';
import { AvailableSteps, AvailableComponents } from 'apps/pos/src/app/types/common';

export const MOCK_ONBOARDING_CONFIG: OnboardingStep[] = [
  {
    slug: AvailableSteps.MERCHANT_REGISTRATION,
    modularKey: null,
    title: 'Adding a New Merchant',
    description: 'Verify your Merchant’s mobile number before getting their KYC verified',
    getStatus: () => 'pending',
    checkIfDisabled: ({ values }) => !!values.merchantId,
    icon: <div>Icon</div>,
    components: [
      {
        slug: AvailableComponents.MOBILE_NUMBER_VERIFY,
        modularKey: null,
        checkIfLandingPossible: () => true,
        view: <div>Merchant Number Verify Component</div>,
        getNextComponent: () => AvailableComponents.MERCHANT_KYC_REDIRECT,
      },
      {
        slug: AvailableComponents.MERCHANT_KYC_REDIRECT,
        modularKey: null,
        checkIfLandingPossible: () => true,
        view: <div>Merchant Number Verify Component</div>,
      },
    ],
  },
  {
    slug: AvailableSteps.MERCHANT_KYC,
    modularKey: null,
    title: 'Merchant KYC',
    description: 'Merchant KYC Description',
    getStatus: () => 'pending',
    checkIfDisabled: ({ values }) => !!values.merchantId,
    icon: <div>Icon</div>,
    customOnClickHandler: () => window.location.assign('easy.razorpay.com'),
    components: [],
  },
  {
    slug: AvailableSteps.DEVICE_SELECTION,
    modularKey: null,
    title: 'Device Selection Step',
    description: 'Select Device',
    getStatus: () => 'pending',
    checkIfDisabled: ({ values }) => !!values.merchantId,
    icon: <div>Icon</div>,
    components: [
      {
        slug: AvailableComponents.MOBILE_NUMBER_VERIFY,
        modularKey: null,
        checkIfLandingPossible: () => true,
        view: <div>Device Selection Step</div>,
      },
    ],
  },
];
