import { modalConfig } from 'merchant/views/AccountAndSettings/common/components/ModalForm/ModalConfig';

describe('modalConfig', () => {
  const user = {
    user: {
      email: 'test@example.com',
    },
  };

  test('should return config for display_name', () => {
    const config = modalConfig({ user }).display_name;
    expect(config.title).toBe('Edit display name');
    expect(config.label).toBe('Enter new display name');
    expect(config.errorText).toBe('Invalid display name');
  });

  test('should return config for name', () => {
    const config = modalConfig({ user }).name;
    expect(config.title).toBe('Edit profile name');
    expect(config.label).toBe('Enter new profile name');
    expect(config.errorText).toBe('Invalid profile name');
  });

  test('should return config for email', () => {
    const config = modalConfig({ user }).email;
    expect(config.title).toBe('Edit account email');
    expect(config.label).toBe('Enter new account email');
    expect(config.errorText).toBe('Invalid account email');
  });

  test('should return config for contact_mobile', () => {
    const config = modalConfig({ user }).contact_mobile;
    expect(config.title).toBe('Edit mobile number');
    expect(config.label).toBe('Enter new mobile number');
    expect(config.errorText).toBe('Invalid mobile number');
  });

  test('should validate email correctly', () => {
    const config = modalConfig({ user }).email;
    const isValid = config.isValid?.('test@example.com');
    expect(isValid).toBe(true);
  });

  test('should validate mobile number correctly', () => {
    const config = modalConfig({ user }).contact_mobile;
    const isValid = config.isValid?.('1234567890');
    expect(isValid).toBe(true);
  });

  test('should validate user name correctly', () => {
    const config = modalConfig({ user }).name;
    const isValid = config.isValid?.('shiv');
    expect(isValid).toBe(true);
  });
});
