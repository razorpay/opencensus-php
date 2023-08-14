import {
  extractVirtualAccountDetails,
  extractPurposeCodeError,
} from 'merchant/reducers/b2bExports/helpers';

describe('extractVirtualAccountDetails', () => {
  test('should extract virtual account details correctly', () => {
    const data = {
      accounts: [
        { id: 1, name: 'Account 1' },
        { id: 2, name: 'Account 2' },
      ],
      status: 'activated',
      reason: 'Success',
    };

    const result = extractVirtualAccountDetails(data);

    expect(result.reason).toBe('Success');
    expect(result.accounts).toEqual([
      { id: 1, name: 'Account 1' },
      { id: 2, name: 'Account 2' },
    ]);
    expect(result.accountsDeactivated).toBe(false);
    expect(result.isIneligiblePurposeCodeModalOpen).toBe(false);
  });

  test('should handle empty data correctly', () => {
    const data = {};

    const result = extractVirtualAccountDetails(data);

    expect(result.reason).toBe('');
    expect(result.accounts).toEqual([]);
    expect(result.accountsDeactivated).toBe(false);
    expect(result.isIneligiblePurposeCodeModalOpen).toBe(false);
  });

  test('should handle accounts as an array correctly', () => {
    const data = [
      { id: 1, name: 'Account 1' },
      { id: 2, name: 'Account 2' },
    ];

    const result = extractVirtualAccountDetails(data);

    expect(result.reason).toBe('');
    expect(result.accounts).toEqual([
      { id: 1, name: 'Account 1' },
      { id: 2, name: 'Account 2' },
    ]);
    expect(result.accountsDeactivated).toBe(false);
    expect(result.isIneligiblePurposeCodeModalOpen).toBe(false);
  });

  test('should set isIneligiblePurposeCodeModalOpen to true when accounts are deactivated', () => {
    const data = {
      accounts: [],
      status: 'deactivated',
      reason: 'Account deactivated',
    };

    const result = extractVirtualAccountDetails(data);

    expect(result.reason).toBe('Account deactivated');
    expect(result.accounts).toEqual([]);
    expect(result.accountsDeactivated).toBe(true);
    expect(result.isIneligiblePurposeCodeModalOpen).toBe(true);
  });

  test('should show valid status if accounts are empty', () => {
    const data = {
      accounts: [],
    };

    const result = extractVirtualAccountDetails(data);

    expect(result.reason).toBe('');
    expect(result.accounts).toEqual([]);
    expect(result.accountsDeactivated).toBe(false);
    expect(result.isIneligiblePurposeCodeModalOpen).toBe(false);
  });
});

describe('extractPurposeCodeError', () => {
  it('should return an object with isIneligiblePurposeCodeModalOpen as false and error as empty string if errors is falsy', () => {
    const errors = null;
    const result = extractPurposeCodeError(errors);
    expect(result).toEqual({ isIneligiblePurposeCodeModalOpen: false, error: null });
  });

  it('should return an object with isIneligiblePurposeCodeModalOpen as true and error as errorCode if errors is truthy and B2B_PURPOSE_CODE_INELIGIBLE_ERROR_KEY is not set', () => {
    const errors = 'Purpose code is not eligible';
    const result = extractPurposeCodeError(errors);
    expect(result).toEqual({
      isIneligiblePurposeCodeModalOpen: true,
      error: 'Purpose code is not eligible',
    });
  });

  it('should return an object with isIneligiblePurposeCodeModalOpen as true and error as errorCode if errors is an array and the first element contains the error message', () => {
    const errors = ['Purpose code is not eligible', 'Some other error'];
    const result = extractPurposeCodeError(errors);
    expect(result).toEqual({
      isIneligiblePurposeCodeModalOpen: true,
      error: 'Purpose code is not eligible',
    });
  });

  test('should return the correct output when an ineligible purpose code error is provided', () => {
    const errors = ['Purpose code is not eligible'];
    const result = extractPurposeCodeError(errors);
    expect(result).toEqual({
      isIneligiblePurposeCodeModalOpen: true,
      error: 'Purpose code is not eligible',
    });
  });

  test('should return the correct output when multiple errors are provided', () => {
    const errors = ['Some error', 'Another error'];
    const result = extractPurposeCodeError(errors);
    expect(result).toEqual({
      isIneligiblePurposeCodeModalOpen: false,
      error: 'Some error',
    });
  });

  test('should return the correct output when a single error is provided', () => {
    const errors = 'Some error';
    const result = extractPurposeCodeError(errors);
    expect(result).toEqual({ isIneligiblePurposeCodeModalOpen: false, error: 'Some error' });
  });
});
