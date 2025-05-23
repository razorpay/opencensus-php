import { ONBOARDING_STEPS } from '../MerchantOnboardingConfig';
import { AvailableSteps, AvailableComponents } from 'apps/pos/src/app/types/common';
import * as merchantActivation from 'apps/pos/src/app/utils/merchantActivation';
import * as modularConfigUtils from 'apps/pos/src/app/utils/modularConfig';
import * as deviceSelectionUtils from 'apps/pos/src/app/utils/deviceSelection';
import { render } from 'apps/pos/src/services/test/test-utils';

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
    const step = ONBOARDING_STEPS.find((s) => s.slug === AvailableSteps.MERCHANT_REGISTRATION);

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

    test('should return true if merchantId is available', () => {
      const isCompleted = step?.checkIfCompleted({
        values: { merchantId: '123' },
        states: mockStates,
      });
      expect(isCompleted).toBe(true);
    });
  });

  describe('MERCHANT_KYC', () => {
    const MERCHANT_KYC = ONBOARDING_STEPS.find((s) => s.slug === AvailableSteps.MERCHANT_KYC);

    afterEach(() => {
      jest.clearAllMocks();
    });

    test('getStatus: returns "kyc_completed" if no posActivationStatus but KYC is complete', () => {
      jest.spyOn(merchantActivation, 'checkIfKycComplete').mockReturnValue(true);
      const states = { merchantDetails: { activation: {} } };
      expect(MERCHANT_KYC.getStatus({ states })).toBe('kyc_completed');
    });

    test('getStatus: returns posActivationStatus in lowercase if present', () => {
      const states = { merchantDetails: { activation: { posActivationStatus: 'IN_PROGRESS' } } };
      expect(MERCHANT_KYC.getStatus({ states })).toBe('in_progress');
    });

    test('getStatus: returns "pending" if no posActivationStatus but KYC is pending', () => {
      jest.spyOn(merchantActivation, 'checkIfKycComplete').mockReturnValue(false);
      const states = { merchantDetails: { activation: {} } };
      expect(MERCHANT_KYC.getStatus({ states })).toBe('pending');
    });

    test('checkIfDisabled: returns true if merchantId is falsy, false if truthy', () => {
      expect(MERCHANT_KYC.checkIfDisabled({ values: { merchantId: null } })).toBe(true);
      expect(MERCHANT_KYC.checkIfDisabled({ values: { merchantId: undefined } })).toBe(true);
      expect(MERCHANT_KYC.checkIfDisabled({ values: { merchantId: '' } })).toBe(true);
      expect(MERCHANT_KYC.checkIfDisabled({ values: { merchantId: '123' } })).toBe(false);
    });

    test('checkIfCompleted: returns true if isFormSubmitted is truthy, false otherwise', () => {
      expect(
        MERCHANT_KYC.checkIfCompleted({
          states: { merchantDetails: { activation: { isFormSubmitted: true } } },
        }),
      ).toBe(true);
      expect(
        MERCHANT_KYC.checkIfCompleted({
          states: { merchantDetails: { activation: { isFormSubmitted: false } } },
        }),
      ).toBe(false);
      expect(MERCHANT_KYC.checkIfCompleted({ states: { merchantDetails: {} } })).toBe(false);
    });
  });

  describe('DEVICE_SELECTION_STEP', () => {
    const DEVICE_SELECTION_STEP = ONBOARDING_STEPS.find(
      (s) => s.slug === AvailableSteps.DEVICE_SELECTION,
    );

    afterEach(() => {
      jest.clearAllMocks();
    });

    test('getStatus: returns correct status from getDeviceStepStatus', () => {
      jest.spyOn(deviceSelectionUtils, 'getDeviceStepStatus').mockReturnValue('in_progress');
      const states = { modularConfig: {} };
      expect(DEVICE_SELECTION_STEP.getStatus({ states })).toBe('in_progress');
    });

    test('checkIfDisabled: returns true if merchantId is falsy', () => {
      const states = { merchantDetails: { business: { address: { registered: {} } } } };
      expect(DEVICE_SELECTION_STEP.checkIfDisabled({ values: { merchantId: '' }, states })).toBe(
        true,
      );
    });

    test('checkIfDisabled: returns true if address is not present', () => {
      jest.spyOn(modularConfigUtils, 'isAddressPresent').mockReturnValue(false);
      const states = { merchantDetails: { business: { address: { registered: null } } } };
      expect(DEVICE_SELECTION_STEP.checkIfDisabled({ values: { merchantId: '123' }, states })).toBe(
        true,
      );
    });

    test('checkIfDisabled: returns true if isPosEnabledForMerchant returns false', () => {
      jest.spyOn(modularConfigUtils, 'isAddressPresent').mockReturnValue(true);
      jest.spyOn(modularConfigUtils, 'isPosEnabledForMerchant').mockReturnValue(false);
      const states = { merchantDetails: { business: { address: { registered: {} } } } };
      expect(DEVICE_SELECTION_STEP.checkIfDisabled({ values: { merchantId: '123' }, states })).toBe(
        true,
      );
    });

    test('checkIfDisabled: returns false if all checks pass', () => {
      jest.spyOn(modularConfigUtils, 'isAddressPresent').mockReturnValue(true);
      jest.spyOn(modularConfigUtils, 'isPosEnabledForMerchant').mockReturnValue(true);
      const states = { merchantDetails: { business: { address: { registered: {} } } } };
      expect(DEVICE_SELECTION_STEP.checkIfDisabled({ values: { merchantId: '123' }, states })).toBe(
        false,
      );
    });

    test('checkIfCompleted: returns true if progress is completed', () => {
      jest.spyOn(modularConfigUtils, 'getProgressFromModularStep').mockReturnValue('completed');
      const states = { modularConfig: {} };
      expect(DEVICE_SELECTION_STEP.checkIfCompleted({ states })).toBe(true);
    });

    test('checkIfCompleted: returns false if progress is not completed', () => {
      jest.spyOn(modularConfigUtils, 'getProgressFromModularStep').mockReturnValue('pending');
      const states = { modularConfig: {} };
      expect(DEVICE_SELECTION_STEP.checkIfCompleted({ states })).toBe(false);
    });

    test('components: getNextComponent returns correct slugs', () => {
      const catalogComp = DEVICE_SELECTION_STEP.components.find(
        (c) => c.slug === AvailableComponents.DEVICE_SELECTION_CATALOG,
      );
      expect(catalogComp.getNextComponent()).toBe(AvailableComponents.DEVICE_CART);

      const cartComp = DEVICE_SELECTION_STEP.components.find(
        (c) => c.slug === AvailableComponents.DEVICE_CART,
      );
      expect(cartComp.getNextComponent()).toBe(AvailableComponents.DEVICE_DELIVERY_ADDRESS);

      const addressComp = DEVICE_SELECTION_STEP.components.find(
        (c) => c.slug === AvailableComponents.DEVICE_DELIVERY_ADDRESS,
      );
      expect(addressComp.getNextComponent()).toBe(AvailableComponents.DEVICE_PAYMENT_METHODS);
    });

    test('components: checkIfLandingPossible returns correct boolean', () => {
      const catalogComp = DEVICE_SELECTION_STEP.components.find(
        (c) => c.slug === AvailableComponents.DEVICE_SELECTION_CATALOG,
      );
      expect(catalogComp.checkIfLandingPossible()).toBe(false);
      expect(catalogComp.checkIfLandingPossible(true)).toBe(true);

      const cartComp = DEVICE_SELECTION_STEP.components.find(
        (c) => c.slug === AvailableComponents.DEVICE_CART,
      );
      expect(cartComp.checkIfLandingPossible()).toBe(false);
      expect(cartComp.checkIfLandingPossible(true)).toBe(true);
    });
  });

  describe('PAYMENTS_METHOD_STEP', () => {
    const PAYMENTS_METHOD_STEP = ONBOARDING_STEPS.find(
      (s) => s.slug === AvailableSteps.PAYMENT_METHODS,
    );

    const paymentMethodsComponent = PAYMENTS_METHOD_STEP.components.find(
      (c) => c.slug === AvailableComponents.PAYMENT_METHODS,
    );

    const brandEmiFormComponent = PAYMENTS_METHOD_STEP.components.find(
      (c) => c.slug === AvailableComponents.BRAND_EMI_FORM,
    );
    const addedBrandInfoComponent = PAYMENTS_METHOD_STEP.components.find(
      (c) => c.slug === AvailableComponents.ADDED_BRAND_INFO,
    );
    const nachFormComponent = PAYMENTS_METHOD_STEP.components.find(
      (c) => c.slug === AvailableComponents.NACH_FORM,
    );

    afterEach(() => {
      jest.clearAllMocks();
    });

    test('getStatus: returns correct progress from modularConfig', () => {
      jest.spyOn(modularConfigUtils, 'getProgressFromModularStep').mockReturnValue('completed');
      const states = {
        modularConfig: {},
        merchantDetails: { business: { address: { registered: {} } } },
      };
      expect(PAYMENTS_METHOD_STEP.getStatus({ states })).toBe('completed');
    });

    test('checkIfDisabled: returns true if merchantId is falsy', () => {
      const states = { merchantDetails: { business: { address: { registered: {} } } } };
      expect(PAYMENTS_METHOD_STEP.checkIfDisabled({ values: { merchantId: '' }, states })).toBe(
        true,
      );
    });

    test('checkIfDisabled: returns true if address is not present', () => {
      jest.spyOn(modularConfigUtils, 'isAddressPresent').mockReturnValue(false);
      const states = { merchantDetails: { business: { address: { registered: null } } } };
      expect(PAYMENTS_METHOD_STEP.checkIfDisabled({ values: { merchantId: '123' }, states })).toBe(
        true,
      );
    });

    test('checkIfCompleted: returns true if progress is completed', () => {
      jest.spyOn(modularConfigUtils, 'getProgressFromModularStep').mockReturnValue('completed');
      const states = {
        modularConfig: {},
        merchantDetails: { business: { address: { registered: {} } } },
      };
      expect(PAYMENTS_METHOD_STEP.checkIfCompleted({ states })).toBe(true);
    });

    test('checkIfCompleted: returns false if progress is not completed', () => {
      jest.spyOn(modularConfigUtils, 'getProgressFromModularStep').mockReturnValue('pending');
      const states = {
        modularConfig: {},
        merchantDetails: { business: { address: { registered: {} } } },
      };
      expect(PAYMENTS_METHOD_STEP.checkIfCompleted({ states })).toBe(false);
    });

    test('components: has correct structure and renders PaymentMethods', () => {
      const comp = PAYMENTS_METHOD_STEP.components[0];
      expect(comp.slug).toBe(AvailableComponents.PAYMENT_METHODS);
      expect(typeof comp.checkIfLandingPossible).toBe('function');
      // Optionally, render and check the view
      const { getByText } = render(comp.view);
      expect(getByText('Select Onboarding Model')).toBeInTheDocument();
    });

    test('PAYMENT_METHODS: checkIfLandingPossible and getNextComponent', () => {
      expect(paymentMethodsComponent.checkIfLandingPossible()).toBe(false);
      expect(paymentMethodsComponent.checkIfLandingPossible(false)).toBe(false);
      expect(paymentMethodsComponent.checkIfLandingPossible(true)).toBe(true);
      expect(paymentMethodsComponent.checkIfLandingPossible('some value')).toBe(true);
      expect(paymentMethodsComponent.getNextComponent()).toBe(AvailableComponents.BRAND_EMI_FORM);
    });

    test('BRAND_EMI_FORM: checkIfLandingPossible and getNextComponent', () => {
      expect(brandEmiFormComponent.checkIfLandingPossible()).toBe(false);
      expect(brandEmiFormComponent.checkIfLandingPossible(false)).toBe(false);
      expect(brandEmiFormComponent.checkIfLandingPossible(true)).toBe(true);
      expect(brandEmiFormComponent.getNextComponent()).toBe(AvailableComponents.ADDED_BRAND_INFO);
    });

    test('ADDED_BRAND_INFO: checkIfLandingPossible and getNextComponent', () => {
      expect(addedBrandInfoComponent.checkIfLandingPossible()).toBe(false);
      expect(addedBrandInfoComponent.checkIfLandingPossible(false)).toBe(false);
      expect(addedBrandInfoComponent.checkIfLandingPossible(true)).toBe(true);
      expect(addedBrandInfoComponent.getNextComponent()).toBe(AvailableComponents.NACH_FORM);
    });

    test('NACH_FORM: checkIfLandingPossible', () => {
      expect(nachFormComponent.checkIfLandingPossible()).toBe(false);
      expect(nachFormComponent.checkIfLandingPossible(false)).toBe(false);
      expect(nachFormComponent.checkIfLandingPossible(true)).toBe(true);
    });
  });

  describe('ADDITIONAL_DETAILS_STEP', () => {
    const ADDITIONAL_DETAILS_STEP = ONBOARDING_STEPS.find(
      (s) => s.slug === AvailableSteps.ADDITIONAL_DETAILS,
    );

    afterEach(() => {
      jest.clearAllMocks();
    });

    test('getStatus: returns correct progress from modularConfig', () => {
      jest.spyOn(modularConfigUtils, 'getProgressFromModularStep').mockReturnValue('completed');
      const states = {
        modularConfig: {},
        merchantDetails: { business: { address: { registered: {} } } },
      };
      expect(ADDITIONAL_DETAILS_STEP.getStatus({ states })).toBe('completed');
    });

    test('checkIfDisabled: returns true if merchantId is falsy', () => {
      const states = { merchantDetails: { business: { address: { registered: {} } } } };
      expect(ADDITIONAL_DETAILS_STEP.checkIfDisabled({ values: { merchantId: '' }, states })).toBe(
        true,
      );
    });

    test('checkIfDisabled: returns true if address is not present', () => {
      jest.spyOn(modularConfigUtils, 'isAddressPresent').mockReturnValue(false);
      const states = { merchantDetails: { business: { address: { registered: null } } } };
      expect(
        ADDITIONAL_DETAILS_STEP.checkIfDisabled({ values: { merchantId: '123' }, states }),
      ).toBe(true);
    });

    test('checkIfCompleted: returns true if progress is completed', () => {
      jest.spyOn(modularConfigUtils, 'getProgressFromModularStep').mockReturnValue('completed');
      const states = {
        modularConfig: {},
        merchantDetails: { business: { address: { registered: {} } } },
      };
      expect(ADDITIONAL_DETAILS_STEP.checkIfCompleted({ states })).toBe(true);
    });

    test('checkIfCompleted: returns false if progress is not completed', () => {
      jest.spyOn(modularConfigUtils, 'getProgressFromModularStep').mockReturnValue('pending');
      const states = {
        modularConfig: {},
        merchantDetails: { business: { address: { registered: {} } } },
      };
      expect(ADDITIONAL_DETAILS_STEP.checkIfCompleted({ states })).toBe(false);
    });
  });

  describe('AGREEMENT_SIGNING_STEP', () => {
    const AGREEMENT_SIGNING_STEP = ONBOARDING_STEPS.find(
      (s) => s.slug === AvailableSteps.AGREEMENT_SIGNING,
    );

    afterEach(() => {
      jest.clearAllMocks();
    });

    test('getStatus: returns correct status from getAgreementSigningStatus', () => {
      jest.spyOn(modularConfigUtils, 'getAgreementSigningStatus').mockReturnValue('completed');
      const states = { modularConfig: {} };
      expect(AGREEMENT_SIGNING_STEP.getStatus({ states })).toBe('completed');
    });

    test('checkIfDisabled: returns true if merchantId is falsy', () => {
      const states = { merchantDetails: { activation: { isFormSubmitted: true } } };
      expect(AGREEMENT_SIGNING_STEP.checkIfDisabled({ values: { merchantId: '' }, states })).toBe(
        true,
      );
    });

    test('checkIfDisabled: returns true if isFormSubmitted is false', () => {
      const states = { merchantDetails: { activation: { isFormSubmitted: false } } };
      expect(
        AGREEMENT_SIGNING_STEP.checkIfDisabled({ values: { merchantId: '123' }, states }),
      ).toBe(true);
    });

    test('checkIfDisabled: returns true if isDevicePricingAdditionalDetailsCompleted is false', () => {
      jest
        .spyOn(modularConfigUtils, 'isDevicePricingAdditionalDetailsCompleted')
        .mockReturnValue(false);
      const states = {
        modularConfig: {},
        merchantDetails: { activation: { isFormSubmitted: true } },
      };
      expect(
        AGREEMENT_SIGNING_STEP.checkIfDisabled({ values: { merchantId: '123' }, states }),
      ).toBe(true);
    });

    test('checkIfDisabled: returns true if isPosEnabledForMerchant is false', () => {
      jest
        .spyOn(modularConfigUtils, 'isDevicePricingAdditionalDetailsCompleted')
        .mockReturnValue(true);
      jest.spyOn(modularConfigUtils, 'isPosEnabledForMerchant').mockReturnValue(false);
      const states = {
        modularConfig: {},
        merchantDetails: { activation: { isFormSubmitted: true } },
      };
      expect(
        AGREEMENT_SIGNING_STEP.checkIfDisabled({ values: { merchantId: '123' }, states }),
      ).toBe(true);
    });

    test('checkIfDisabled: returns false if all checks pass', () => {
      jest
        .spyOn(modularConfigUtils, 'isDevicePricingAdditionalDetailsCompleted')
        .mockReturnValue(true);
      jest.spyOn(modularConfigUtils, 'isPosEnabledForMerchant').mockReturnValue(true);
      const states = {
        modularConfig: {},
        merchantDetails: { activation: { isFormSubmitted: true } },
      };
      expect(
        AGREEMENT_SIGNING_STEP.checkIfDisabled({ values: { merchantId: '123' }, states }),
      ).toBe(false);
    });

    test('checkIfCompleted: returns true if agreement signing is COMPLETED', () => {
      jest.spyOn(modularConfigUtils, 'getAgreementSigningStatus').mockReturnValue('completed');
      const states = { modularConfig: {} };
      expect(AGREEMENT_SIGNING_STEP.checkIfCompleted({ states })).toBe(true);
    });

    test('checkIfCompleted: returns false if agreement signing is not COMPLETED', () => {
      jest.spyOn(modularConfigUtils, 'getAgreementSigningStatus').mockReturnValue('pending');
      const states = { modularConfig: {} };
      expect(AGREEMENT_SIGNING_STEP.checkIfCompleted({ states })).toBe(false);
    });

    test('clickAnalytics: has correct analytics properties', () => {
      expect(AGREEMENT_SIGNING_STEP.clickAnalytics).toEqual({
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
      const stepSlugs = ONBOARDING_STEPS.map((step) => step.slug);
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
      ONBOARDING_STEPS.forEach((step) => {
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
