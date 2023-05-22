import { getInitials } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/Profile/views/v2/utils';

describe('getInitials', () => {
  test('should returns the correct initials for a name with multiple parts', () => {
    const name = 'Razorpay merchant';
    const initials = getInitials(name);
    expect(initials).toBe('RM');
  });

  test('should returns the correct initials for a name with a single part', () => {
    const name = 'Merchant';
    const initials = getInitials(name);
    expect(initials).toBe('M');
  });

  test('should returns an empty string for an empty name', () => {
    const name = '';
    const initials = getInitials(name);
    expect(initials).toBe('');
  });
});
