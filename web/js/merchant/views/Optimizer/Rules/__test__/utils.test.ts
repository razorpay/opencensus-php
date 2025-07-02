import { getCurrency, amountConversionToMinorUnit, amountConversionToMajorUnit } from '../utils';

// Mock the i18nify-js currency functions
jest.mock('@razorpay/i18nify-js/currency', () => ({
  convertToMinorUnit: jest.fn((amount, options) => amount * 100),
  convertToMajorUnit: jest.fn((amount, options) => amount / 100),
}));

describe('Optimizer Rules Utils', () => {
  describe('getCurrency', () => {
    it('should return default currency INR when ruleDetails is undefined', () => {
      expect(getCurrency(undefined)).toBe('INR');
    });

    it('should return default currency INR when ruleDetails is null', () => {
      expect(getCurrency(null)).toBe('INR');
    });

    it('should return default currency INR when precondition is missing', () => {
      const ruleDetails = {};
      expect(getCurrency(ruleDetails)).toBe('INR');
    });

    it('should return default currency INR when operands is missing', () => {
      const ruleDetails = {
        precondition: {}
      };
      expect(getCurrency(ruleDetails)).toBe('INR');
    });

    it('should return default currency INR when operands is not an array', () => {
      const ruleDetails = {
        precondition: {
          operands: null
        }
      };
      expect(getCurrency(ruleDetails)).toBe('INR');
    });

    it('should return default currency INR when operands is empty array', () => {
      const ruleDetails = {
        precondition: {
          operands: []
        }
      };
      expect(getCurrency(ruleDetails)).toBe('INR');
    });

    it('should find currency from comparator operand', () => {
      const ruleDetails = {
        precondition: {
          operands: [
            {
              type: 'comparator',
              operands: [
                { value: '$payment.optimizer_currency' },
                { value: 'USD' }
              ]
            }
          ]
        }
      };
      expect(getCurrency(ruleDetails)).toBe('USD');
    });

    it('should find currency from nested logical operands', () => {
      const ruleDetails = {
        precondition: {
          operands: [
            {
              type: 'logical',
              operands: [
                {
                  type: 'comparator',
                  operands: [
                    { value: '$payment.optimizer_currency' },
                    { value: 'EUR' }
                  ]
                }
              ]
            }
          ]
        }
      };
      expect(getCurrency(ruleDetails)).toBe('EUR');
    });

    it('should return first non-INR currency found', () => {
      const ruleDetails = {
        precondition: {
          operands: [
            {
              type: 'comparator',
              operands: [
                { value: '$payment.optimizer_currency' },
                { value: 'GBP' }
              ]
            },
            {
              type: 'comparator',
              operands: [
                { value: '$payment.optimizer_currency' },
                { value: 'JPY' }
              ]
            }
          ]
        }
      };
      expect(getCurrency(ruleDetails)).toBe('GBP');
    });

    it('should handle operands without proper structure', () => {
      const ruleDetails = {
        precondition: {
          operands: [
            {
              type: 'comparator',
              operands: [
                { value: 'other_field' },
                { value: 'USD' }
              ]
            }
          ]
        }
      };
      expect(getCurrency(ruleDetails)).toBe('INR');
    });
  });

  describe('amountConversionToMinorUnit', () => {
    beforeEach(() => {
      jest.clearAllMocks();
    });

    it('should handle undefined data', () => {
      const result = amountConversionToMinorUnit(undefined);
      expect(result).toBeUndefined();
    });

    it('should handle null data', () => {
      const result = amountConversionToMinorUnit(null);
      expect(result).toBeNull();
    });

    it('should handle data without precondition', () => {
      const data = { someField: 'value' };
      const result = amountConversionToMinorUnit(data);
      expect(result).toEqual(data);
    });

    it('should convert single amount value to minor unit', () => {
      const data = {
        precondition: {
          operands: [
            {
              type: 'comparator',
              operands: [
                { value: '$payment.navigator_amount' },
                { value: '100', type: 'single' }
              ]
            }
          ]
        }
      };

      const result = amountConversionToMinorUnit(data);
      
      expect(result.precondition.operands[0].operands[1].value).toBe('10000');
    });

    it('should convert array amount values (between case) to minor unit', () => {
      const data = {
        precondition: {
          operands: [
            {
              type: 'comparator',
              operands: [
                { value: '$payment.navigator_amount' },
                { value: '100,200', type: 'array' }
              ]
            }
          ]
        }
      };

      const result = amountConversionToMinorUnit(data);
      
      expect(result.precondition.operands[0].operands[1].value).toBe('10000,20000');
    });

    it('should handle nested logical operands', () => {
      const data = {
        precondition: {
          operands: [
            {
              type: 'logical',
              operands: [
                {
                  type: 'comparator',
                  operands: [
                    { value: '$payment.navigator_amount' },
                    { value: '50', type: 'single' }
                  ]
                }
              ]
            }
          ]
        }
      };

      const result = amountConversionToMinorUnit(data);
      
      expect(result.precondition.operands[0].operands[0].operands[1].value).toBe('5000');
    });

    it('should not convert amounts when currency is an array (multiple currencies)', () => {
      const data = {
        precondition: {
          operands: [
            {
              type: 'comparator',
              operands: [
                { value: '$payment.optimizer_currency' },
                { value: 'USD,EUR' }
              ]
            },
            {
              type: 'comparator',
              operands: [
                { value: '$payment.navigator_amount' },
                { value: '100', type: 'single' }
              ]
            }
          ]
        }
      };

      const result = amountConversionToMinorUnit(data);
      
      // Amount should remain unchanged when multiple currencies are present
      expect(result.precondition.operands[1].operands[1].value).toBe('100');
    });

    it('should handle amount conversion with specific currency', () => {
      const data = {
        precondition: {
          operands: [
            {
              type: 'comparator',
              operands: [
                { value: '$payment.optimizer_currency' },
                { value: 'USD' }
              ]
            },
            {
              type: 'comparator',
              operands: [
                { value: '$payment.navigator_amount' },
                { value: '100', type: 'single' }
              ]
            }
          ]
        }
      };

      const result = amountConversionToMinorUnit(data);
      
      expect(result.precondition.operands[1].operands[1].value).toBe('10000');
    });

    it('should handle whitespace in array amount values', () => {
      const data = {
        precondition: {
          operands: [
            {
              type: 'comparator',
              operands: [
                { value: '$payment.navigator_amount' },
                { value: ' 100 , 200 ', type: 'array' }
              ]
            }
          ]
        }
      };

      const result = amountConversionToMinorUnit(data);
      
      expect(result.precondition.operands[0].operands[1].value).toBe('10000,20000');
    });
  });

  describe('amountConversionToMajorUnit', () => {
    beforeEach(() => {
      jest.clearAllMocks();
    });

    it('should handle undefined data', () => {
      const result = amountConversionToMajorUnit(undefined);
      expect(result).toBeUndefined();
    });

    it('should handle null data', () => {
      const result = amountConversionToMajorUnit(null);
      expect(result).toBeNull();
    });

    it('should handle data without precondition', () => {
      const data = { someField: 'value' };
      const result = amountConversionToMajorUnit(data);
      expect(result).toEqual(data);
    });

    it('should convert single amount value to major unit', () => {
      const data = {
        precondition: {
          operands: [
            {
              type: 'comparator',
              operands: [
                { value: '$payment.navigator_amount' },
                { value: '10000', type: 'single' }
              ]
            }
          ]
        }
      };

      const result = amountConversionToMajorUnit(data);
      
      expect(result.precondition.operands[0].operands[1].value).toBe('100');
    });

    it('should convert array amount values (between case) to major unit', () => {
      const data = {
        precondition: {
          operands: [
            {
              type: 'comparator',
              operands: [
                { value: '$payment.navigator_amount' },
                { value: '10000,20000', type: 'array' }
              ]
            }
          ]
        }
      };

      const result = amountConversionToMajorUnit(data);
      
      expect(result.precondition.operands[0].operands[1].value).toBe('100,200');
    });

    it('should handle nested logical operands', () => {
      const data = {
        precondition: {
          operands: [
            {
              type: 'logical',
              operands: [
                {
                  type: 'comparator',
                  operands: [
                    { value: '$payment.navigator_amount' },
                    { value: '5000', type: 'single' }
                  ]
                }
              ]
            }
          ]
        }
      };

      const result = amountConversionToMajorUnit(data);
      
      expect(result.precondition.operands[0].operands[0].operands[1].value).toBe('50');
    });

    it('should not convert amounts when currency is an array (multiple currencies)', () => {
      const data = {
        precondition: {
          operands: [
            {
              type: 'comparator',
              operands: [
                { value: '$payment.optimizer_currency' },
                { value: 'USD,EUR' }
              ]
            },
            {
              type: 'comparator',
              operands: [
                { value: '$payment.navigator_amount' },
                { value: '10000', type: 'single' }
              ]
            }
          ]
        }
      };

      const result = amountConversionToMajorUnit(data);
      
      // Amount should remain unchanged when multiple currencies are present
      expect(result.precondition.operands[1].operands[1].value).toBe('10000');
    });

    it('should handle amount conversion with specific currency', () => {
      const data = {
        precondition: {
          operands: [
            {
              type: 'comparator',
              operands: [
                { value: '$payment.optimizer_currency' },
                { value: 'EUR' }
              ]
            },
            {
              type: 'comparator',
              operands: [
                { value: '$payment.navigator_amount' },
                { value: '10000', type: 'single' }
              ]
            }
          ]
        }
      };

      const result = amountConversionToMajorUnit(data);
      
      expect(result.precondition.operands[1].operands[1].value).toBe('100');
    });
  });

  describe('Edge cases and error handling', () => {
    it('should handle malformed operands structure', () => {
      const data = {
        precondition: {
          operands: [
            {
              type: 'comparator',
              operands: [
                { value: '$payment.navigator_amount' }
                // Missing second operand
              ]
            }
          ]
        }
      };

      expect(() => amountConversionToMinorUnit(data)).not.toThrow();
      expect(() => amountConversionToMajorUnit(data)).not.toThrow();
    });

    it('should handle non-numeric amount values gracefully', () => {
      const data = {
        precondition: {
          operands: [
            {
              type: 'comparator',
              operands: [
                { value: '$payment.navigator_amount' },
                { value: 'invalid_number', type: 'single' }
              ]
            }
          ]
        }
      };

      expect(() => amountConversionToMinorUnit(data)).not.toThrow();
      expect(() => amountConversionToMajorUnit(data)).not.toThrow();
    });

    it('should handle deeply nested logical operands', () => {
      const data = {
        precondition: {
          operands: [
            {
              type: 'logical',
              operands: [
                {
                  type: 'logical',
                  operands: [
                    {
                      type: 'comparator',
                      operands: [
                        { value: '$payment.navigator_amount' },
                        { value: '100', type: 'single' }
                      ]
                    }
                  ]
                }
              ]
            }
          ]
        }
      };

      const result = amountConversionToMinorUnit(data);
      
      expect(result.precondition.operands[0].operands[0].operands[0].operands[1].value).toBe('10000');
    });
  });
});
