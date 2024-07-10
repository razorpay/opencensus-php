import { User } from 'common/typings';
import { checkIfSignUpViaEasyOnboarding } from 'common/utils/activation';

describe('Activation utils test', () => {
  test('should return true if sign up campaign is  assisted onboarding in user object', () => {
    const mockUser = {
      user: {
        signup_campaign: 'assisted_onboarding',
      },
    };
    const isSignedUpViaEasy = checkIfSignUpViaEasyOnboarding(mockUser as User);
    expect(isSignedUpViaEasy).toBe(true);
  });

  test('should return true if sign up campaign is assisted onboarding in user', () => {
    const mockUser = {
      user: {
        signup_campaign: 'easy_onboarding',
      },
    };
    const isSignedUpViaEasy = checkIfSignUpViaEasyOnboarding(mockUser as User);
    expect(isSignedUpViaEasy).toBe(true);
  });

  test('should return false if sign up campaign is not assisted onboarding  and easy in user object', () => {
    const mockUser = {
      user: {
        signup_campaign: 'some_random_onboarding',
      },
    };
    const isSignedUpViaEasy = checkIfSignUpViaEasyOnboarding(mockUser as User);
    expect(isSignedUpViaEasy).toBe(false);
  });
});
