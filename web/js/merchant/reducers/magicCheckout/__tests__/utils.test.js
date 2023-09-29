import {
  updateEventConfigs,
  addAccount,
  deleteAccountConfig,
} from 'merchant/reducers/magicCheckout/analyticsSettings/utils';
import {
  eventConfigs,
  stateWithBackendConfigs,
  stateWithEmptyConfigs,
  stateWithFrontendConfigs,
  addedConfigs,
} from 'merchant/reducers/magicCheckout/__tests__/mocks/fixtures';

describe('testing updateEventConfigs utility function', () => {
  test('should assign integartion method as frontend if no analytics account is available', () => {
    const updatedConfigs = updateEventConfigs(eventConfigs, stateWithEmptyConfigs);

    expect(updatedConfigs.ga4.accounts[0].integration_method).toBe('frontend');
    expect(updatedConfigs.ga4.events.purchase).toBe(true);
  });

  test('should update only events if analytics account is available', () => {
    const updatedConfigs = updateEventConfigs(eventConfigs, stateWithBackendConfigs);

    expect(updatedConfigs.ga4.accounts[0].integration_method).not.toBe('frontend');
    expect(updatedConfigs.ga4.events.purchase).toBe(true);
  });
});

describe('testing addAccount utility function', () => {
  test('should update the existing analytics account if integration method is frontend', () => {
    const updatedConfigs = addAccount(addedConfigs, stateWithFrontendConfigs);
    expect(updatedConfigs.ga4.accounts.length).toBe(1);
    expect(updatedConfigs.ga4.accounts[0].integration_method).not.toBe('frontend');
    expect(updatedConfigs.ga4.accounts[0].integration_method).toBe('backend');
  });

  test('should add a new account if there is an existing analytics account with integration method as backend', () => {
    const updatedConfigs = addAccount(addedConfigs, stateWithBackendConfigs);
    expect(updatedConfigs.ga4.accounts.length).toBe(2);
    expect(updatedConfigs.ga4.accounts[0].measurement_id).toBe('test001');
    expect(updatedConfigs.ga4.accounts[1].measurement_id).toBe('test002');
  });
});

describe('testing deleting account config util', () => {
  test('should return the account config if an empty array', () => {
    const updatedConfig = deleteAccountConfig('123', [], 'google_ads');
    expect(updatedConfig.length).toBe(0);
  });

  test('should return the account config with integration method as frontend if account configs has only one account ', () => {
    const updatedConfig = deleteAccountConfig('123', [{ id: '123' }], 'google_ads');
    expect(updatedConfig.length).not.toBe(0);
    expect(updatedConfig[0].integration_method).toBe('frontend');
  });
});
