import React from 'react';
import { ONBOARDING_STEPS_POS_EKYC } from '../MerchantOnboardingConfigPosEkyc';
import * as merchantActivation from 'apps/pos/src/app/utils/merchantActivation';

// Mock AgreementSigning to break circular dependency
jest.mock('../components/AgreementSigning', () => ({
  __esModule: true,
  default: () => <div>Mocked AgreementSigning</div>,
}));

jest.mock('apps/pos/src/app/utils/deviceSelection', () => ({
  getDeviceStepStatus: jest.fn().mockReturnValue('pending'),
}));

jest.mock('apps/pos/src/app/utils/modularConfig', () => ({
  getProgressFromModularStep: jest.fn().mockReturnValue('pending'),
  isAddressPresent: jest.fn().mockReturnValue(true),
  isDevicePricingAdditionalDetailsCompletedForPosEkyc: jest.fn().mockReturnValue(true),
  isPosEnabledForMerchant: jest.fn().mockReturnValue(true),
  getAgreementSigningStatus: jest.fn().mockReturnValue('pending'),
  getStepsFromModularConfig: jest.fn().mockReturnValue([]),
}));

jest.mock('apps/pos/src/app/utils/deviceDeployment', () => ({
  getDeviceDeploymentStatus: jest.fn().mockReturnValue('pending'),
  isDeviceDeploymentFlowActivated: jest.fn().mockReturnValue(true),
}));

jest.mock('apps/pos/src/app/utils/agreementSigning', () => ({
  COMPLETED: 'completed',
  getAgreementComponentStatus: jest.fn().mockReturnValue(true),
}));

jest.mock('apps/pos/src/app/utils/merchantActivation', () => ({
  checkIfKycComplete: jest.fn().mockReturnValue(false),
}));

describe('ONBOARDING_STEPS_POS_EKYC config', () => {
  const mockStates = {
    modularConfig: {
      milestones: {},
    },
    merchantDetails: {
      business: {
        address: {
          registered: { line1: 'Test Address' },
        },
      },
      activation: {
        isFormSubmitted: true,
        posActivationStatus: 'PENDING',
      },
    },
  };

  test('should contain all required steps in correct order', () => {
    const stepSlugs = ONBOARDING_STEPS_POS_EKYC.map((step) => step.slug);
    expect(stepSlugs).toEqual([
      'merchantRegistration',
      'merchantKyc',
      'deviceSelection',
      'additionalDetails',
      'agreementSigning',
      'deviceDeployment',
    ]);
  });

  describe('MERCHANT_REGISTRATION_STEP', () => {
    const step = ONBOARDING_STEPS_POS_EKYC.find((s) => s.slug === 'merchantRegistration');
    test('getStatus returns correct value', () => {
      expect(step.getStatus({ values: { merchantId: '123' } })).toBe('completed');
      expect(step.getStatus({ values: { merchantId: '' } })).toBe('pending');
    });
    test('checkIfDisabled returns correct value', () => {
      expect(step.checkIfDisabled({ values: { merchantId: '123' } })).toBe(true);
      expect(step.checkIfDisabled({ values: { merchantId: '' } })).toBe(false);
    });
    test('checkIfCompleted returns correct value', () => {
      expect(step.checkIfCompleted({ values: { merchantId: '123' } })).toBe(true);
      expect(step.checkIfCompleted({ values: { merchantId: '' } })).toBe(false);
    });
  });

  describe('MERCHANT_KYC', () => {
    const step = ONBOARDING_STEPS_POS_EKYC.find((s) => s.slug === 'merchantKyc');

    const kycCompletedMockStates = {
      modularConfig: {
        milestones: {},
      },
      merchantDetails: {
        business: {
          address: {
            registered: { line1: 'Test Address' },
          },
        },
        activation: {
          isFormSubmitted: true,
        },
      },
    };

    test('getStatus returns correct pending value', () => {
      expect(step.getStatus({ states: mockStates })).toBe('pending');
    });
    test('getStatus returns correct kyc_completed value', () => {
      jest.spyOn(merchantActivation, 'checkIfKycComplete').mockReturnValue(true);
      expect(step.getStatus({ states: kycCompletedMockStates })).toBe('kyc_completed');
    });
    test('checkIfDisabled returns correct value', () => {
      expect(step.checkIfDisabled({ values: { merchantId: '' } })).toBe(true);
      expect(step.checkIfDisabled({ values: { merchantId: '123' } })).toBe(false);
    });
    test('checkIfCompleted returns correct value', () => {
      expect(step.checkIfCompleted({ states: mockStates })).toBe(true);
    });
  });

  describe('DEVICE_SELECTION_STEP', () => {
    const step = ONBOARDING_STEPS_POS_EKYC.find((s) => s.slug === 'deviceSelection');
    test('getStatus returns correct value', () => {
      expect(step.getStatus({ states: mockStates })).toBe('pending');
    });
    test('checkIfDisabled returns correct value', () => {
      expect(step.checkIfDisabled({ values: { merchantId: '' }, states: mockStates })).toBe(true);
    });
    test('checkIfCompleted returns correct value', () => {
      expect(step.checkIfCompleted({ states: mockStates })).toBe(false);
    });
  });

  describe('DEVICE_SELECTION_STEP components', () => {
    const step = ONBOARDING_STEPS_POS_EKYC.find((s) => s.slug === 'deviceSelection');

    test('should test checkIfLandingPossible for DEVICE_SELECTION_CATALOG component', () => {
      expect(step).toBeDefined();
      const catalogComponent = step?.components.find((c) => c.slug === 'deviceSelectionCatalog');
      expect(catalogComponent).toBeDefined();
      expect(catalogComponent?.checkIfLandingPossible()).toBe(true);
    });

    test('should test getNextComponent for DEVICE_SELECTION_CATALOG component', () => {
      expect(step).toBeDefined();
      const catalogComponent = step?.components.find((c) => c.slug === 'deviceSelectionCatalog');
      expect(catalogComponent).toBeDefined();
      expect(catalogComponent?.getNextComponent()).toBe('deviceCart');
    });

    test('should test checkIfLandingPossible for DEVICE_CART component', () => {
      expect(step).toBeDefined();
      const cartComponent = step?.components.find((c) => c.slug === 'deviceCart');
      expect(cartComponent).toBeDefined();
      expect(cartComponent?.checkIfLandingPossible()).toBe(true);
    });

    test('should test getNextComponent for DEVICE_CART component', () => {
      expect(step).toBeDefined();
      const cartComponent = step?.components.find((c) => c.slug === 'deviceCart');
      expect(cartComponent).toBeDefined();
      expect(cartComponent?.getNextComponent()).toBe('deviceDeliveryAddress');
    });

    test('should test checkIfLandingPossible for DEVICE_DELIVERY_ADDRESS component', () => {
      expect(step).toBeDefined();
      const addressComponent = step?.components.find((c) => c.slug === 'deviceDeliveryAddress');
      expect(addressComponent).toBeDefined();
      expect(addressComponent?.checkIfLandingPossible()).toBe(true);
    });

    test('should test getNextComponent for DEVICE_DELIVERY_ADDRESS component', () => {
      expect(step).toBeDefined();
      const addressComponent = step?.components.find((c) => c.slug === 'deviceDeliveryAddress');
      expect(addressComponent).toBeDefined();
      expect(addressComponent?.getNextComponent()).toBe('devicePayment');
    });

    test('should test checkIfLandingPossible for DEVICE_PAYMENT component', () => {
      expect(step).toBeDefined();
      const paymentComponent = step?.components.find((c) => c.slug === 'devicePayment');
      expect(paymentComponent).toBeDefined();
      expect(paymentComponent?.checkIfLandingPossible()).toBe(true);
    });
  });

  describe('ADDITIONAL_DETAILS_STEP', () => {
    const step = ONBOARDING_STEPS_POS_EKYC.find((s) => s.slug === 'additionalDetails');
    test('getStatus returns correct value', () => {
      expect(step.getStatus({ states: mockStates })).toBe('pending');
    });
    test('checkIfDisabled returns correct value', () => {
      expect(step.checkIfDisabled({ values: { merchantId: '' }, states: mockStates })).toBe(true);
    });
    test('checkIfCompleted returns correct value', () => {
      expect(step.checkIfCompleted({ states: mockStates })).toBe(false);
    });
  });

  describe('AGREEMENT_SIGNING_STEP', () => {
    const step = ONBOARDING_STEPS_POS_EKYC.find((s) => s.slug === 'agreementSigning');
    test('getStatus returns correct value', () => {
      expect(step.getStatus({ states: mockStates })).toBe('pending');
    });
    test('checkIfDisabled returns correct value', () => {
      expect(step.checkIfDisabled({ values: { merchantId: '' }, states: mockStates })).toBe(true);
    });
    test('checkIfCompleted returns correct value', () => {
      expect(step.checkIfCompleted({ states: mockStates })).toBe(false);
    });
  });

  describe('DEVICE_DEPLOYMENT_STEP', () => {
    const step = ONBOARDING_STEPS_POS_EKYC.find((s) => s.slug === 'deviceDeployment');
    test('getStatus returns correct value', () => {
      expect(step.getStatus({ states: mockStates })).toBe('pending');
    });
    test('checkIfDisabled returns correct value', () => {
      expect(step.checkIfDisabled({ values: { merchantId: '' }, states: mockStates })).toBe(true);
    });
    test('checkIfCompleted returns correct value', () => {
      expect(step.checkIfCompleted({ states: mockStates })).toBe(false);
    });
  });

  describe('DEVICE_DEPLOYMENT_STEP components', () => {
    const step = ONBOARDING_STEPS_POS_EKYC.find((s) => s.slug === 'deviceDeployment');

    test('should test checkIfLandingPossible for DEVICE_DEPLOYMENT_LIST component', () => {
      expect(step).toBeDefined();
      const deploymentListComponent = step?.components.find(
        (c) => c.slug === 'deviceDeploymentList',
      );
      expect(deploymentListComponent).toBeDefined();
      expect(deploymentListComponent?.checkIfLandingPossible()).toBe(true);
    });

    test('should test checkIfLandingPossible for DEVICE_CONFIGURATION component', () => {
      expect(step).toBeDefined();
      const configComponent = step?.components.find((c) => c.slug === 'deviceConfiguration');
      expect(configComponent).toBeDefined();
      expect(configComponent?.checkIfLandingPossible()).toBe(true);
    });

    test('should test checkIfLandingPossible for LANGUAGE_CONFIGURATION component', () => {
      expect(step).toBeDefined();
      const langComponent = step?.components.find((c) => c.slug === 'languageConfiguration');
      expect(langComponent).toBeDefined();
      expect(langComponent?.checkIfLandingPossible()).toBe(true);
    });

    test('should test checkIfLandingPossible for DEVICE_TESTING component', () => {
      expect(step).toBeDefined();
      const testingComponent = step?.components.find((c) => c.slug === 'deviceTesting');
      expect(testingComponent).toBeDefined();
      expect(testingComponent?.checkIfLandingPossible()).toBe(true);
    });
  });
});
