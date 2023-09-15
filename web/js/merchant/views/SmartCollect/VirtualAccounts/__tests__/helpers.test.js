import { isAxisBank, isRBLBank } from 'merchant/views/SmartCollect/VirtualAccounts/helpers';

describe('isAxisBank', () => {
  test('isAxisBank returns true for Axis Bank', () => {
    const bankAccount = {
      bank_name: 'Axis Bank',
    };
    expect(isAxisBank(bankAccount)).toBe(true);
  });

  test('isAxisBank returns false for other banks', () => {
    const bankAccount = {
      bank_name: 'Test Bank',
    };
    expect(isAxisBank(bankAccount)).toBe(false);
  });

  test('isAxisBank returns false for null', () => {
    expect(isAxisBank(null)).toBe(false);
  });
});

describe('isRBLBank', () => {
  test('isRBLBank returns true for RBL Bank', () => {
    const bankAccount = {
      bank_name: 'RBL Bank',
    };
    expect(isRBLBank(bankAccount)).toBe(true);
  });

  test('isRBLBank returns false for other banks', () => {
    const bankAccount = {
      bank_name: 'Test Bank',
    };
    expect(isRBLBank(bankAccount)).toBe(false);
  });

  test('isRBLBank returns false for null', () => {
    expect(isRBLBank(null)).toBe(false);
  });
});
