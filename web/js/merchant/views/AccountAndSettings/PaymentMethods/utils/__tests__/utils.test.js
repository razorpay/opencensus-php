import { isInternationalLeafItemDisabled } from 'merchant/views/AccountAndSettings/PaymentMethods/utils';

describe('isInternationalLeafItemDisabled', () => {
  test('should return false if slug is not in the list', () => {
    const leafList = { slug: 'other' };
    const user = { international: true };

    const result = isInternationalLeafItemDisabled({ leafList, user });

    expect(result).toBe(false);
  });

  test('should return false if slug is in the list but user is international', () => {
    const leafList = { slug: 'localcurrencytransfer' };
    const user = { international: true };

    const result = isInternationalLeafItemDisabled({ leafList, user });

    expect(result).toBe(false);
  });

  test('should return true if slug is in the list and user is not international', () => {
    const leafList = { slug: 'localcurrencytransfer' };
    const user = { international: false };

    const result = isInternationalLeafItemDisabled({ leafList, user });

    expect(result).toBe(true);
  });

  test('should return false if slug is moneysaverexportaccount and user is international', () => {
    const leafList = { slug: 'moneysaverexportaccount' };
    const user = { international: true };

    const result = isInternationalLeafItemDisabled({ leafList, user });

    expect(result).toBe(false);
  });
});
