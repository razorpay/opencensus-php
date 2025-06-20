import {
  validateName,
  validateDescription,
  validateDiscount,
  validateDenominationType,
  validateExpiryPeriod,
  validateNotNull,
  validateDenominationValues,
  validatePin,
  validateImage,
  validatePrefix,
  validateCardType
} from '../validations';
import { DENOMINATION_TYPE } from '../constants';

describe('validateName', () => {
  test('should return error message when value is empty', () => {
    expect(validateName('')).toBe('Please fill out this field');
    expect(validateName(undefined)).toBe('Please fill out this field');
  });

  test('should return error message when name contains non-alphanumeric characters', () => {
    expect(validateName('Test@123')).toBe('Name should be alphanumeric.');
    expect(validateName('Program!')).toBe('Name should be alphanumeric.');
  });

  test('should return false for valid names', () => {
    expect(validateName('Test Program')).toBe(false);
    expect(validateName('Program123')).toBe(false);
    expect(validateName('P')).toBe(false);
  });
});

describe('validateDescription', () => {
  test('should return error message when description is empty', () => {
    expect(validateDescription('')).toBe('Please fill out this field');
    expect(validateDescription(undefined)).toBe('Please fill out this field');
  });

  test('should return false for valid descriptions', () => {
    expect(validateDescription('This is a valid description')).toBe(false);
  });
});

describe('validateDiscount', () => {
  test('should return error message when discount is empty', () => {
    expect(validateDiscount('')).toBe('Discounts needs to be filled');
  });

  test('should return error message when discount is not a number', () => {
    expect(validateDiscount('abc')).toBe('Discount needs to be a percentage');
  });

  test('should return error message when discount is out of range', () => {
    expect(validateDiscount('101')).toBe('Invalid discount. Enter a value between 1-100% (e.g., 15.75%).');
    expect(validateDiscount('-1')).toBe('Invalid discount. Enter a value between 1-100% (e.g., 15.75%).');
  });

  test('should return false for valid discounts', () => {
    expect(validateDiscount('0')).toBe(false);
    expect(validateDiscount('50')).toBe(false);
    expect(validateDiscount('100')).toBe(false);
    expect(validateDiscount('15.75')).toBe(false);
  });
});

describe('validateDenominationType', () => {
  test('should return error message when denomination type is not selected', () => {
    expect(validateDenominationType('')).toBe('Please select one of the denomination types');
    expect(validateDenominationType(undefined)).toBe('Please select one of the denomination types');
  });

  test('should return false for valid denomination type', () => {
    expect(validateDenominationType(DENOMINATION_TYPE.FIXED.value)).toBe(false);
    expect(validateDenominationType(DENOMINATION_TYPE.CUSTOMIZABLE.value)).toBe(false);
  });
});

describe('validateExpiryPeriod', () => {
  test('should return error message when expiry period is empty', () => {
    expect(validateExpiryPeriod('')).toBe('Please fill this field');
    expect(validateExpiryPeriod(undefined)).toBe('Please fill this field');
  });

  test('should return error message when expiry period is zero', () => {
    expect(validateExpiryPeriod('0')).toBe('Zero values are not allowed');
  });

  test('should return false for valid expiry periods', () => {
    expect(validateExpiryPeriod('1')).toBe(false);
    expect(validateExpiryPeriod('365')).toBe(false);
  });
});

describe('validateNotNull', () => {
  test('should return error message when value is null or undefined', () => {
    expect(validateNotNull('')).toBe('Please fill the field');
    expect(validateNotNull(undefined)).toBe('Please fill the field');
    expect(validateNotNull(null)).toBe('Please fill the field');
  });

  test('should return false for non-null values', () => {
    expect(validateNotNull('value')).toBe(false);
    expect(validateNotNull([])).toBe(false);
    expect(validateNotNull({})).toBe(false);
  });
});

describe('validateDenominationValues', () => {
  test('should return error message when customizable range is incomplete', () => {
    const values = {
      denomination_type: DENOMINATION_TYPE.CUSTOMIZABLE.value,
      denomination_values: { from: '', to: '100' }
    };
    expect(validateDenominationValues(values)).toBe('Please enter the start and end range');

    const values2 = {
      denomination_type: DENOMINATION_TYPE.CUSTOMIZABLE.value,
      denomination_values: { from: '10', to: '' }
    };
    expect(validateDenominationValues(values2)).toBe('Please enter the start and end range');
  });

  test('should return error message when range is invalid', () => {
    const values = {
      denomination_type: DENOMINATION_TYPE.CUSTOMIZABLE.value,
      denomination_values: { from: '100', to: '50' }
    };
    expect(validateDenominationValues(values)).toBe('Invalid range: The ending value must be greater than the starting value.');

    const values2 = {
      denomination_type: DENOMINATION_TYPE.CUSTOMIZABLE.value,
      denomination_values: { from: '100', to: '100' }
    };
    expect(validateDenominationValues(values2)).toBe('Invalid range: The ending value must be greater than the starting value.');
  });

  test('should return error message when fixed values are empty', () => {
    const values = {
      denomination_type: DENOMINATION_TYPE.FIXED.value,
      denomination_values: []
    };
    expect(validateDenominationValues(values)).toBe('Please select atleast one value');
  });

  test('should return false for valid denomination values', () => {
    const customizableValues = {
      denomination_type: DENOMINATION_TYPE.CUSTOMIZABLE.value,
      denomination_values: { from: '10', to: '100' }
    };
    expect(validateDenominationValues(customizableValues)).toBe(false);

    const fixedValues = {
      denomination_type: DENOMINATION_TYPE.FIXED.value,
      denomination_values: [100, 200, 500]
    };
    expect(validateDenominationValues(fixedValues)).toBe(false);
  });
});

describe('validatePin', () => {
  test('should return error message when pin selection is undefined', () => {
    expect(validatePin(undefined)).toBe('Please select one of the fields');
  });

  test('should return false for valid pin selection', () => {
    expect(validatePin(true)).toBe(false);
    expect(validatePin(false)).toBe(false);
  });
});

describe('validateImage', () => {
  test('should return error message when image is not provided', () => {
    expect(validateImage('')).toBe('Please fill out this field');
    expect(validateImage(undefined)).toBe('Please fill out this field');
    expect(validateImage(null)).toBe('Please fill out this field');
  });

  test('should return false for valid image', () => {
    expect(validateImage('image.jpg')).toBe(false);
    expect(validateImage({ url: 'image.jpg' })).toBe(false);
  });
});

describe('validatePrefix', () => {
  test('should return error message when prefix is too long', () => {
    expect(validatePrefix('ABCDE')).toBe('Prefix cannot be more than 4 characters');
  });

  test('should return error message when prefix contains invalid characters', () => {
    expect(validatePrefix('Ab12')).toBe('Only upper case alphabets and numbers are allowed');
    expect(validatePrefix('ab12')).toBe('Only upper case alphabets and numbers are allowed');
    expect(validatePrefix('AB!2')).toBe('Only upper case alphabets and numbers are allowed');
  });

  test('should return false for valid prefixes', () => {
    expect(validatePrefix('')).toBe(false);
    expect(validatePrefix('A')).toBe(false);
    expect(validatePrefix('AB')).toBe(false);
    expect(validatePrefix('ABC')).toBe(false);
    expect(validatePrefix('ABCD')).toBe(false);
    expect(validatePrefix('A123')).toBe(false);
    expect(validatePrefix('1234')).toBe(false);
  });
});

describe('validateCardType', () => {
  test('should return error message when card type is not selected', () => {
    expect(validateCardType('')).toBe('Please select one of the card types');
    expect(validateCardType(undefined)).toBe('Please select one of the card types');
    expect(validateCardType(null)).toBe('Please select one of the card types');
  });

  test('should return false for valid card type', () => {
    expect(validateCardType('numeric')).toBe(false);
    expect(validateCardType('alphanumeric')).toBe(false);
  });
}); 