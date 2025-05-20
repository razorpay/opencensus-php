import { ONBOARDING_STEPS } from '../MerchantOnboardingConfig';
import { AvailableSteps } from 'apps/pos/src/app/types/common';

describe('MerchantOnboardingConfig', () => {
  const mockStates = {
    modularConfig: null,
    merchantDetails: {
      business: {
        address: {
          registered: {
            line1: 'Test Address',
          },
        },
      },
      activation: {
        isFormSubmitted: true,
        posActivationStatus: 'PENDING',
      },
    },
  };

  const mockValues = {
    merchantId: '123',
  };

  describe('MERCHANT_REGISTRATIONS_STEP', () => {
    const step = ONBOARDING_STEPS.find(s => s.slug === AvailableSteps.MERCHANT_REGISTRATION);

    test('should return completed status when merchantId exists', () => {
      const status = step?.getStatus({ values: mockValues, states: mockStates });
      expect(status).toBe('completed');
    });

    test('should return pending status when merchantId does not exist', () => {
      const status = step?.getStatus({ values: { merchantId: '' }, states: mockStates });
      expect(status).toBe('pending');
    });

    test('should be disabled when merchantId exists', () => {
      const isDisabled = step?.checkIfDisabled({ values: mockValues, states: mockStates });
      expect(isDisabled).toBe(true);
    });
  });

  describe('DEVICE_SELECTION_STEP', () => {
    const step = ONBOARDING_STEPS.find(s => s.slug === AvailableSteps.DEVICE_SELECTION);

    test('should be disabled when merchant address is not present', () => {
      const statesWithoutAddress = {
        ...mockStates,
        merchantDetails: {
          ...mockStates.merchantDetails,
          business: {
            address: {
              registered: null,
            },
          },
        },
      };

      const isDisabled = step?.checkIfDisabled({ 
        values: mockValues, 
        states: statesWithoutAddress 
      });
      expect(isDisabled).toBe(true);
    });

    test('should be disabled when merchantId is not present', () => {
      const isDisabled = step?.checkIfDisabled({ 
        values: { merchantId: '' }, 
        states: mockStates 
      });
      expect(isDisabled).toBe(true);
    });
  });

  describe('AGREEMENT_SIGNING_STEP', () => {
    const step = ONBOARDING_STEPS.find(s => s.slug === AvailableSteps.AGREEMENT_SIGNING);

    test('should be disabled when form is not submitted', () => {
      const statesWithoutFormSubmission = {
        ...mockStates,
        merchantDetails: {
          ...mockStates.merchantDetails,
          activation: {
            ...mockStates.merchantDetails.activation,
            isFormSubmitted: false,
          },
        },
      };

      const isDisabled = step?.checkIfDisabled({ 
        values: mockValues, 
        states: statesWithoutFormSubmission 
      });
      expect(isDisabled).toBe(true);
    });

    test('should have correct analytics properties', () => {
      expect(step?.clickAnalytics).toEqual({
        eventName: 'Link',
        action: 'Clicked',
        properties: {
          label: 'Image click',
          l1FunnelStage: 'Merchant Onboarding',
          l2FunnelStage: 'Agreement Signing',
          section: 'Merchant Onboarding',
          subSection: 'Agreement Signing',
        },
      });
    });
  });

  describe('ONBOARDING_STEPS', () => {
    test('should contain all required steps in correct order', () => {
      const stepSlugs = ONBOARDING_STEPS.map(step => step.slug);
      expect(stepSlugs).toEqual([
        AvailableSteps.MERCHANT_REGISTRATION,
        AvailableSteps.MERCHANT_KYC,
        AvailableSteps.DEVICE_SELECTION,
        AvailableSteps.PAYMENT_METHODS,
        AvailableSteps.ADDITIONAL_DETAILS,
        AvailableSteps.AGREEMENT_SIGNING,
      ]);
    });

    test('should have required properties for each step', () => {
      ONBOARDING_STEPS.forEach(step => {
        expect(step).toHaveProperty('slug');
        expect(step).toHaveProperty('title');
        expect(step).toHaveProperty('description');
        expect(step).toHaveProperty('icon');
        expect(step).toHaveProperty('getStatus');
        expect(step).toHaveProperty('checkIfDisabled');
        expect(step).toHaveProperty('components');
        expect(Array.isArray(step.components)).toBe(true);
      });
    });
  });
});