import { checkReconSaasEnabled } from './../utils';
import { getReconDuration } from '../Onboarding/constants';
import { getReadableFromKey } from '../Onboarding/utils';

describe('Recon utils', () => {
  test('should return true if recon saas is enabled', () => {
    expect(
      checkReconSaasEnabled({
        abExperiments: { recon_sass_flag: { variables: { turned: 'on' } } },
      }),
    ).toBe(true);
  });

  test('should return false if recon saas is disabled', () => {
    expect(
      checkReconSaasEnabled({
        abExperiments: { recon_sass_flag: { variables: { turned: 'off' } } },
      }),
    ).toBe(false);
  });

  test('should return false if recon saas is not defined', () => {
    expect(
      checkReconSaasEnabled({
        abExperiments: { recon_sass_flag: { variables: { turned: undefined } } },
      }),
    ).toBe(false);
  });

  test('should return recon duration for IN', () => {
    expect(getReconDuration('IN')).toBe('2 hours');
  });

  test('should return recon duration for without any country passed', () => {
    expect(getReconDuration()).toBe('2 hours');
  });

  test('should give output in readable format for file type key', () => {
    const key = 'bank_rrn_file';
    const readable = getReadableFromKey(key);
    expect(readable).toBe('Bank Rrn File');
  });
  test('should give output in readable format for empty file type key', () => {
    const key = 'bank_rrn_file';
    const readable = getReadableFromKey(key);
    expect(readable).toBe('Bank Rrn File');
  });
});
