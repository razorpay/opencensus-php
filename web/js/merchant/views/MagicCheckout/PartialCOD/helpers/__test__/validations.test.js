import {
  validateCreateAdvancedSlab,
  validateBasicSlab,
} from 'merchant/views/MagicCheckout/PartialCOD/helpers/validations';
import { PREPAID_PAYMENY_AMOUNT_ITEM_TYPE } from 'merchant/views/MagicCheckout/PartialCOD/types';

describe('validateCreateAdvancedSlab', () => {
  let mockOnError;

  beforeEach(() => {
    mockOnError = jest.fn();
  });

  test('should return true and no errors for valid input', () => {
    const validSlabData = {
      rules: {
        min_order_amount: 50,
        max_order_amount: 200,
        customer_risk_category: ['low'],
      },
      value: 30,
      type: 'flat',
    };

    const result = validateCreateAdvancedSlab(validSlabData, mockOnError);

    expect(result).toBe(true);
    expect(mockOnError).toHaveBeenCalledWith({
      min_order_amount: '',
      max_order_amount: '',
      customer_risk_category: '',
      value: '',
    });
  });

  test('should return false and set error for invalid min_order_amount', () => {
    const invalidSlabData = {
      rules: {
        min_order_amount: -10,
        max_order_amount: 200,
        customer_risk_category: ['low'],
      },
      value: 30,
      type: 'flat',
    };

    const result = validateCreateAdvancedSlab(invalidSlabData, mockOnError);

    expect(result).toBe(false);
    expect(mockOnError).toHaveBeenCalledWith({
      min_order_amount: 'Enter a valid amount',
      max_order_amount: '',
      customer_risk_category: '',
      value: '',
    });
  });

  test('should return false and sets error for invalid max_order_amount ', () => {
    const invalidSlabData = {
      rules: {
        min_order_amount: 50,
        max_order_amount: 40,
        customer_risk_category: ['low'],
      },
      value: 30,
      type: 'flat',
    };

    const result = validateCreateAdvancedSlab(invalidSlabData, mockOnError);

    expect(result).toBe(false);
    expect(mockOnError).toHaveBeenCalledWith({
      min_order_amount: '',
      max_order_amount: 'Enter a valid amount',
      customer_risk_category: '',
      value: '',
    });
  });

  test('should return false and sets error for invalid customer_risk_category', () => {
    const invalidSlabData = {
      rules: {
        min_order_amount: 50,
        max_order_amount: 200,
        customer_risk_category: [],
      },
      value: 30,
      type: 'flat',
    };

    const result = validateCreateAdvancedSlab(invalidSlabData, mockOnError);

    expect(result).toBe(false);
    expect(mockOnError).toHaveBeenCalledWith({
      min_order_amount: '',
      max_order_amount: '',
      customer_risk_category: 'Select at least one risk category',
      value: '',
    });
  });

  test('should return false and sets error for invalid value', () => {
    const invalidSlabData = {
      rules: {
        min_order_amount: 50,
        max_order_amount: 200,
        customer_risk_category: ['low'],
      },
      value: -10,
      type: 'flat',
    };

    const result = validateCreateAdvancedSlab(invalidSlabData, mockOnError);

    expect(result).toBe(false);
    expect(mockOnError).toHaveBeenCalledWith({
      min_order_amount: '',
      max_order_amount: '',
      customer_risk_category: '',
      value: 'Enter a valid amount',
    });
  });

  test('should return false and set error for percentage type value greater than 50', () => {
    const invalidSlabData = {
      rules: {
        min_order_amount: 50,
        max_order_amount: 200,
        customer_risk_category: ['low'],
      },
      value: 60,
      type: 'percentage',
    };

    const result = validateCreateAdvancedSlab(invalidSlabData, mockOnError);

    expect(result).toBe(false);
    expect(mockOnError).toHaveBeenCalledWith({
      min_order_amount: '',
      max_order_amount: '',
      customer_risk_category: '',
      value: 'Pre-pay amount should not be higher than 50% of order value',
    });
  });

  test('should return false and set error for pre-pay value greater than max_order_amount', () => {
    const invalidSlabData = {
      rules: {
        min_order_amount: 50,
        max_order_amount: 200,
        customer_risk_category: ['low'],
      },
      value: 250,
      type: 'flat',
    };

    const result = validateCreateAdvancedSlab(invalidSlabData, mockOnError);

    expect(result).toBe(false);
    expect(mockOnError).toHaveBeenCalledWith({
      min_order_amount: '',
      max_order_amount: '',
      customer_risk_category: '',
      value: 'Pre-pay amount should not be higher than 2',
    });
  });
});

describe('validateBasicSlab', () => {
  let mockOnError;

  beforeEach(() => {
    mockOnError = jest.fn();
  });

  test('should return true and no error for valid percentage input', () => {
    const value = '30';
    const type = PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.PERCENTAGE;

    const result = validateBasicSlab(value, type, mockOnError);

    expect(result).toBe(true);
    expect(mockOnError).toHaveBeenCalledWith({
      type: '',
      value: '',
    });
  });

  test('should return false for empty value for invalid percentage input', () => {
    const value = '';
    const type = PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.PERCENTAGE;

    const result = validateBasicSlab(value, type, mockOnError);

    expect(result).toBe(false);
    expect(mockOnError).toHaveBeenCalledWith({
      type: '',
      value: 'Please enter a value',
    });
  });

  test('should return false for value greater than 50 for invalid percentage input', () => {
    const value = '60';
    const type = PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.PERCENTAGE;

    const result = validateBasicSlab(value, type, mockOnError);

    expect(result).toBe(false);
    expect(mockOnError).toHaveBeenCalledWith({
      type: '',
      value: 'Pre-pay amount should not be higher than 50% of order value',
    });
  });

  test('should return false for value greater than 100 for invalid percentage input', () => {
    const value = '110';
    const type = PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.PERCENTAGE;

    const result = validateBasicSlab(value, type, mockOnError);

    expect(result).toBe(false);
    expect(mockOnError).toHaveBeenCalledWith({
      type: '',
      value: 'Please enter a valid value',
    });
  });

  test('should return false for value less than or equal to 0 for invalid percentage input', () => {
    const value = '0';
    const type = PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.PERCENTAGE;

    const result = validateBasicSlab(value, type, mockOnError);

    expect(result).toBe(false);
    expect(mockOnError).toHaveBeenCalledWith({
      type: '',
      value: 'Please enter a valid value',
    });
  });

  test('should return true and no errors for valid flat input', () => {
    const value = '100';
    const type = PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT;

    const result = validateBasicSlab(value, type, mockOnError);

    expect(result).toBe(true);
    expect(mockOnError).toHaveBeenCalledWith({
      type: '',
      value: '',
    });
  });

  test('should return false for empty value for invalid flat input', () => {
    const value = '';
    const type = PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT;

    const result = validateBasicSlab(value, type, mockOnError);

    expect(result).toBe(false);
    expect(mockOnError).toHaveBeenCalledWith({
      type: '',
      value: 'Please enter a value',
    });
  });

  test('should return false for value less than or equal to 0 for invalid flat input', () => {
    const value = '0';
    const type = PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT;

    const result = validateBasicSlab(value, type, mockOnError);

    expect(result).toBe(false);
    expect(mockOnError).toHaveBeenCalledWith({
      type: '',
      value: 'Please enter a valid value',
    });
  });

  test('should return false for non-numeric value for invalid flat input', () => {
    const value = 'abc';
    const type = PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT;

    const result = validateBasicSlab(value, type, mockOnError);

    expect(result).toBe(false);
    expect(mockOnError).toHaveBeenCalledWith({
      type: '',
      value: 'Please enter a value',
    });
  });
});
