import {
  STEPS,
  STEPS_INITIAL_STATE,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/activationStates/constants';
import {
  validateIecCode,
  validateIecCodeStep,
  validateInternationalDetailsStep,
  validatePurposeCodeStep,
  validateSteps,
  validateVideoKycStep,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/activationStates/helpers';

import {
  getMockAllSteps,
  getMockIecCodeStep,
  getMockInternationalDetailsStep,
  getMockPurposeCodeStep,
  getMockVideoKycStep,
} from './mocks/helpers';

describe.skip('validatePurposeCodeStep', () => {
  it('should return invalid state when purpose code is not provided', () => {
    const state = getMockPurposeCodeStep();
    const result = validatePurposeCodeStep(state);

    expect(result.isValid).toBe(false);
    expect(result.state.steps?.[STEPS.PURPOSE_CODE]?.validationState.purposeCode).toEqual({
      state: 'error',
      errorText: 'Please select a purpose code',
    });
  });

  it('should return valid state when purpose code is provided', () => {
    const state = getMockPurposeCodeStep('TEST_PURPOSE_CODE');
    const result = validatePurposeCodeStep(state);

    expect(result.isValid).toBe(true);
    expect(result.state.steps?.[STEPS.PURPOSE_CODE].validationState).toEqual(
      STEPS_INITIAL_STATE[STEPS.PURPOSE_CODE].validationState,
    );
  });
});

describe.skip('validateIecCode', () => {
  it('should validate correct IEC code format', () => {
    expect(validateIecCode('ABCD1234EF')).toBe(true);
    expect(validateIecCode('1234567890')).toBe(true);
  });

  it('should fail for invalid IEC code formats', () => {
    expect(validateIecCode('A3')).toBe(false); // too short
    expect(validateIecCode('ABC@123#EF')).toBe(false); // special chars
    expect(validateIecCode('')).toBe(false); // empty
    // @ts-expect-error - Testing edge cases
    expect(validateIecCode(null)).toBe(false); // null
    expect(validateIecCode(undefined)).toBe(false); // undefined
  });

  it('should handle edge cases with spaces', () => {
    expect(validateIecCode('ABCD 1234EF')).toBe(false); // space in middle
    expect(validateIecCode(' ABCD1234EF')).toBe(false); // leading space
    expect(validateIecCode('ABCD1234EF ')).toBe(false); // trailing space
  });

  it('should handle NOT_APPLICABLE', () => {
    expect(validateIecCode('NOT_APPLICABLE')).toBe(true);
  });
});

describe('validateIecCodeStep', () => {
  it('should return true when IEC code is valid', () => {
    const state = getMockIecCodeStep({
      iecCode: 'ABC1234567',
      iecCodeOption: 'yes',
      acceptTnc: 'yes',
    });
    const result = validateIecCodeStep(state);
    expect(result.isValid).toBe(true);
  });

  it('should return false when IEC code is invalid', () => {
    const state = getMockIecCodeStep({
      iecCode: 'ABC123@', // invalid format
      iecCodeOption: 'yes',
      acceptTnc: 'yes',
    });
    const result = validateIecCodeStep(state);
    expect(result.isValid).toBe(false);
    expect(result.state.steps?.[STEPS.IEC_CODE]?.validationState.iecCode).toEqual({
      state: 'error',
      errorText: 'Please enter a valid IEC code',
    });
  });

  it('should return false when IEC code option is not selected', () => {
    const state = getMockIecCodeStep({
      iecCode: 'ABC1234567',
      iecCodeOption: '',
    });
    const result = validateIecCodeStep(state);
    expect(result.isValid).toBe(false);
    expect(result.state.steps?.[STEPS.IEC_CODE]?.validationState.iecCodeOption).toEqual({
      state: 'error',
      errorText: 'Please select an option',
    });
  });

  it('should return false when IEC code is valid and TNC is not accepted', () => {
    const state = getMockIecCodeStep({
      iecCode: 'ABCD1234EF',
      iecCodeOption: 'no',
      acceptTnc: '',
    });
    const result = validateIecCodeStep(state);
    expect(result.isValid).toBe(false);
    expect(result.state.steps?.[STEPS.IEC_CODE]?.validationState.acceptTnc).toEqual({
      state: 'error',
      errorText: 'Please accept the terms and conditions',
    });
  });
});

describe('validateInternationalDetailsStep', () => {
  it('should return true when TNC is accepted', () => {
    const state = getMockInternationalDetailsStep('yes');
    const result = validateInternationalDetailsStep(state);
    expect(result.isValid).toBe(true);
  });

  it('should return false when TNC is not accepted', () => {
    const state = getMockInternationalDetailsStep('');
    const result = validateInternationalDetailsStep(state);
    expect(result.isValid).toBe(false);
    expect(result.state.steps?.[STEPS.INTERNATIONAL_DETAILS]?.validationState.acceptTnc).toEqual({
      state: 'error',
      errorText: 'Please accept the terms and conditions',
    });
  });
});

describe('validateVideoKycStep', () => {
  it('should return true when owner is provided', () => {
    const state = getMockVideoKycStep('Test Owner');
    const result = validateVideoKycStep(state);
    expect(result.isValid).toBe(true);
  });

  it('should return false when owner is not provided', () => {
    const state = getMockVideoKycStep('');
    const result = validateVideoKycStep(state);
    expect(result.isValid).toBe(false);
    expect(result.state.steps?.[STEPS.VIDEO_KYC]?.validationState.owner).toEqual({
      state: 'error',
      errorText: 'Please select an option',
    });
  });
});

describe('validateSteps', () => {
  it('should validate all steps correctly when state is valid', () => {
    const state = getMockAllSteps({
      current: STEPS.PURPOSE_CODE,
      purposeCode: 'TEST_PURPOSE_CODE',
      iecCode: 'ABC1234567',
      iecCodeOption: 'yes',
      acceptTnc: 'yes',
      acceptNotApplicableTnc: 'yes',
    });
    const result = validateSteps(state);
    expect(result.isValid).toBe(true);
    expect(result.state.steps?.[STEPS.PURPOSE_CODE].validationState).toEqual(
      STEPS_INITIAL_STATE[STEPS.PURPOSE_CODE].validationState,
    );
    expect(result.state.steps?.[STEPS.IEC_CODE].validationState).toEqual(
      STEPS_INITIAL_STATE[STEPS.IEC_CODE].validationState,
    );
    expect(result.state.steps?.[STEPS.INTERNATIONAL_DETAILS].validationState).toEqual(
      STEPS_INITIAL_STATE[STEPS.INTERNATIONAL_DETAILS].validationState,
    );
    expect(result.state.steps?.[STEPS.VIDEO_KYC].validationState).toEqual(
      STEPS_INITIAL_STATE[STEPS.VIDEO_KYC].validationState,
    );
  });

  it('should fail validation when purpose code is missing', () => {
    const state = getMockAllSteps({
      current: STEPS.PURPOSE_CODE,
      purposeCode: '',
    });
    const result = validateSteps(state);
    expect(result.isValid).toBe(false);
    expect(result.state.steps?.[STEPS.PURPOSE_CODE]?.validationState.purposeCode).toEqual({
      state: 'error',
      errorText: 'Please select a purpose code',
    });
  });

  it('should fail validation when IEC code is invalid', () => {
    const state = getMockAllSteps({
      current: STEPS.IEC_CODE,
      iecCode: 'ABC123@',
      iecCodeOption: 'yes',
      acceptTnc: 'yes',
    });
    const result = validateSteps(state);
    expect(result.isValid).toBe(false);
    expect(result.state.steps?.[STEPS.IEC_CODE]?.validationState.iecCode).toEqual({
      state: 'error',
      errorText: 'Please enter a valid IEC code',
    });
  });

  it('should fail validation when international details are missing', () => {
    const state = getMockAllSteps({
      current: STEPS.INTERNATIONAL_DETAILS,
      acceptTnc: '',
    });
    const result = validateSteps(state);
    expect(result.isValid).toBe(false);
    expect(result.state.steps?.[STEPS.INTERNATIONAL_DETAILS]?.validationState.acceptTnc).toEqual({
      state: 'error',
      errorText: 'Please accept the terms and conditions',
    });
  });
});
