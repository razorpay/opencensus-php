import rolesList from 'merchant/helpers/permissions/roles-list';
import { isOrgFeatureExist } from 'merchant/models/User';
import { ATTR_DETAILS } from 'merchant/views/Account/constants';
import * as conditionalUtils from 'merchant/views/AccountAndSettings/utils/conditionUtils';

jest.mock('merchant/models/User', () => ({
  ...jest.requireActual('merchant/models/User'),
  isOrgFeatureExist: jest.fn(),
}));

const { isFlashCheckoutAllowed, accountAccessHoverDescription } = conditionalUtils;

const getUser = () => ({
  isAllowedView: jest.fn(),
  isOrgAllowedFunctionality: jest.fn(),
  findTag: jest.fn(),
  contact_mobile: undefined,
  isWhatsappNotificationEnabled: jest.fn(),
  role: undefined,
  activation_status: undefined,
  user: {
    contact_mobile: undefined,
    isINCountry: true,
  },
  isOrgAxis: undefined,
  isOrgRZP: undefined,
  isInstrumentRequestAllowed: jest.fn(),
  isInstrumentRequestHidden: undefined,
  isWebsiteComplianceFlowEnabled: undefined,
  isFeatureEnabled: jest.fn(),
  international: undefined,
});
describe('Condition Utils', () => {
  let user = getUser();

  beforeEach(() => {
    // clear old mocks
    user = getUser();
  });

  const testUtilWhichUsesSingleUserFunc = (
    utilName,
    userProperty,
    { reverse = false, extraTestMessage = '' } = {},
  ) => {
    describe(utilName, () => {
      const extraConfig = { isConfigTagEnabled: jest.fn() };
      test.each([
        [reverse, false],
        [!reverse, true],
      ])(
        `should return %s when user.${userProperty} returns %s ${extraTestMessage}`,
        (userKeyOutput, utilOutput) => {
          user[userProperty].mockReturnValueOnce(userKeyOutput);
          expect(conditionalUtils[utilName](user, extraConfig)).toBe(utilOutput);
        },
      );
    });
  };

  const testUtilWhichUsesSingleUserObj = (utilName, userProperty, isBoolean) => {
    describe(utilName, () => {
      test.each([
        [false, isBoolean ? false : undefined],
        [true, isBoolean ? true : 'john doe'],
      ])(`should return %s when ${userProperty} is %s`, (userPropertyValue, utilOutput) => {
        expect(conditionalUtils[utilName]({ [userProperty]: !!utilOutput })).toBe(
          !!userPropertyValue,
        );
      });
    });
  };

  testUtilWhichUsesSingleUserFunc('isConfigurationViewAllowed', 'isAllowedView', {
    extraTestMessage: 'on passing configuration',
  });

  testUtilWhichUsesSingleUserFunc('isProfileViewAllowed', 'isAllowedView', {
    extraTestMessage: 'on passing profile',
  });

  describe.each([
    ['isSkipMandatorySummaryPageAllowed', 'isSkipMandatorySummaryPageAllowed'],
    ['isReminderEnabled', 'isReminderEnabled'],
    ['isSettlementsAllowed', 'isSettlementsAllowed'],
  ])('%s', (util, extraTestMessage) => {
    test.each([
      [false, true],
      [true, false],
    ])(
      `should return %s when isConfigTagEnabled returns %s on passing to conditional ${extraTestMessage}`,
      (utilOutput, configTagOutput) => {
        const extraConfig = { isConfigTagEnabled: () => configTagOutput };
        expect(conditionalUtils[util](extraConfig)).toBe(utilOutput);
      },
    );
  });
  testUtilWhichUsesSingleUserObj('isSmsNotificationEnabled', 'contact_mobile');

  describe('isFlashCheckoutAllowed', () => {
    test.each([
      [false, false, true],
      [false, true, true],
      [false, false, false],
      [true, true, false],
    ])(
      'should return %s when user.isOrgAllowedFunctionality returns %s and isConfigTagEnabled returns %s',
      (flag, isOrgAllowedFunctionality, configTag) => {
        user.isOrgAllowedFunctionality.mockReturnValueOnce(isOrgAllowedFunctionality);
        const extraConfig = { isConfigTagEnabled: () => configTag };
        expect(isFlashCheckoutAllowed(user, extraConfig)).toBe(flag);
      },
    );
  });

  describe('isWhatsappNotificationEnabled', () => {
    test.each([
      [true, false, true, 'test-contact-mobile', 'activated', rolesList.OWNER],
      [true, false, true, 'test-contact-mobile', 'activated', rolesList.ADMIN],
      [false, true, true, 'test-contact-mobile', 'activated', rolesList.ADMIN],
      [false, false, false, undefined, 'activated', rolesList.ADMIN],
      [false, false, false, undefined, 'activated-test', rolesList.MANAGER],
    ])(
      `should return %s when user.isWhatsappNotificationEnabled returns %s, user.isOrgAllowedFunctionality returns %s, user.contact_mobile returns %s, isConfigTagEnabled returns %s and user.role returns %s on passing WhatsappNotification`,
      (
        output,
        configTag,
        isWhatsappNotificationEnabled,
        contact_mobile,
        activationStatus,
        role,
      ) => {
        user.isWhatsappNotificationEnabled.mockReturnValueOnce(isWhatsappNotificationEnabled);
        user.user = {
          contact_mobile,
        };
        const extraConfig = { isConfigTagEnabled: () => configTag };
        user.role = role;
        user.activation_status = activationStatus;
        expect(conditionalUtils.isWhatsappNotificationEnabled(user, extraConfig)).toBe(output);
      },
    );
  });

  describe('isTrustedBadgeAllowed', () => {
    test.each([
      [false, false, false, true, false],
      [false, false, true, false, false],
      [false, false, false, false, true],
      [false, true, false, false, false],
      [true, true, false, false, false],
    ])(
      'should return %s when user.isAllowedView returns %s, isOrgAxis is %s, isOrgFeatureExist returns %s and isConfigTagEnabled returns %s on passing hide_razorpay_text_link',
      (flag, isAllowedViewOutput, isOrgAxis, isOrgFeatureExistOutput, configTagOutput) => {
        user.isAllowedView.mockReturnValueOnce(isAllowedViewOutput);
        user.isOrgAxis = isOrgAxis;
        isOrgFeatureExist.mockReturnValueOnce(isOrgFeatureExistOutput);
        const extraConfig = { isConfigTagEnabled: () => configTagOutput };
        expect(conditionalUtils.isTrustedBadgeAllowed(user, extraConfig)).toBe(flag);
      },
    );
  });

  describe('isPaymentMethodEnabled', () => {
    beforeEach(() => {
      user = {
        isOrgRZP: false,
        isINCountry: false,
        isInstrumentRequestAllowed: jest.fn().mockReturnValue(false),
        isInstrumentRequestHidden: false,
      };
    });

    test.each([
      // Expected result, isOrgRZP, isINCountry, isInstrumentRequestAllowed, isInstrumentRequestHidden, mode
      [false, false, false, false, false, 'test'],
      [false, false, true, false, false, 'live'],
      [false, true, true, true, false, 'test'],
      [false, false, false, true, false, 'live'],
      [true, true, true, true, true, 'live'],
      [true, true, true, true, false, 'live'],
      [true, false, true, true, true, 'live'],
      [false, true, false, true, false, 'test'],
    ])(
      'should return %s when isOrgRZP is %s, isINCountry is %s, isInstrumentRequestAllowed returns %s, isInstrumentRequestHidden is %s, and mode is %s',
      (
        expectedResult,
        isOrgRZP,
        isINCountry,
        isInstrumentRequestAllowedOutput,
        isInstrumentRequestHiddenOutput,
        mode,
      ) => {
        user.isOrgRZP = isOrgRZP;
        user.isINCountry = isINCountry;
        user.isInstrumentRequestAllowed.mockReturnValueOnce(isInstrumentRequestAllowedOutput);
        user.isInstrumentRequestHidden = isInstrumentRequestHiddenOutput;

        expect(conditionalUtils.isPaymentMethodEnabled(user, mode)).toBe(expectedResult);
      },
    );
  });

  describe('shouldShowFeeBearerSelfServe', () => {
    test.each([
      [false, false, false, rolesList.ADMIN, false, false, false, false],
      [true, false, true, true, rolesList.OWNER, true, false, false],
      [false, true, true, true, rolesList.OWNER, true, false, false],
      [true, true, true, true, rolesList.OWNER, true, false, true],
    ])(
      'should return %s when allowCFBInternational is %s, isOrgRZP is %s, isAccepted is %s, role is %s returns %s, isFeeBearerSelfServeOn is %s, isPayPalEnabled is %s and international is %s',
      (
        flag,
        allowCFBInternational,
        isOrgRZP,
        isAccepted,
        role,
        isFeeBearerSelfServeOn,
        isPayPalEnabled,
        international,
        // eslint-disable-next-line max-params
      ) => {
        expect(
          conditionalUtils.shouldShowFeeBearerSelfServe({
            user: {
              isOrgRZP,
              isAccepted,
              role,
              isFeeBearerSelfServeOn,
              isPayPalEnabled,
              international,
            },
            allowCFBInternational,
          }),
        ).toBe(flag);
      },
    );
  });

  testUtilWhichUsesSingleUserFunc('isApiKeyEnabled', 'isAllowedView', {
    extraTestMessage: 'on passing api_keys',
  });
  testUtilWhichUsesSingleUserFunc('isWebhookEnabled', 'isAllowedView', {
    extraTestMessage: 'on passing webhooks',
  });

  testUtilWhichUsesSingleUserFunc('isApplicationEnabled', 'isAllowedView', {
    extraTestMessage: 'on passing applications',
  });

  describe('isWebsiteDetailsEnabled', () => {
    test.each([
      [false, false, false, false],
      [false, true, false, false],
      [false, true, false, true],
      [true, true, true, false],
    ])(
      'should return %s when isWebsiteComplianceFlowEnabled is %s, isWebsiteSectionsApplicable is %s and isConfigTagEnabled returns %s on passing WebsiteAppDetails tag',
      (flag, isWebsiteComplianceFlowEnabled, isWebsiteSectionsApplicable, configTagOutput) => {
        const extraConfig = { isConfigTagEnabled: () => configTagOutput };
        user.isWebsiteComplianceFlowEnabled = isWebsiteComplianceFlowEnabled;
        expect(
          conditionalUtils.isWebsiteDetailsEnabled({
            user,
            websiteSectionDetailsData: {
              data: {
                isWebsiteSectionsApplicable,
              },
            },
            extraConfig,
          }),
        ).toBe(flag);
      },
    );
  });

  describe('isGstDetailsEnabled', () => {
    test.each([
      [false, false, false],
      [false, true, true],
      [true, true, false],
    ])(
      'should return %s when user.isAllowedView returns %s, and isConfigTagEnabled returns %s on passing profile_gst and Gst tags',
      (flag, isAllowedViewOutput, configTagOutput) => {
        user.isAllowedView.mockReturnValueOnce(isAllowedViewOutput);
        const extraConfig = { isConfigTagEnabled: () => configTagOutput };
        expect(conditionalUtils.isGstDetailsEnabled(user, extraConfig)).toBe(flag);
      },
    );
  });

  testUtilWhichUsesSingleUserObj('isAccountDetailsEnabled', 'isActivated', true);
  testUtilWhichUsesSingleUserObj('isTeamManagementAllowed', 'isAllowedTeamManagement', true);

  describe('isSupportTicketEnabled', () => {
    test.each([
      [false, false, false, false, false],
      [false, false, true, true, false],
      [false, true, false, false, true],
      [true, true, true, false, false],
    ])(
      'should return %s when user.isAdminOrOwner is %s, isFdTicketsEnabled is %s, isComdelApiEnabled is %s and isConfigTagEnabled returns %s on passing SupportHistory',
      (flag, isAdminOrOwner, isFdTicketsEnabled, isComdelApiEnabled, configTagOutput) => {
        user.isAdminOrOwner = isAdminOrOwner;
        user.isFdTicketsEnabled = isFdTicketsEnabled;
        user.isComdelApiEnabled = isComdelApiEnabled;
        const extraConfig = { isConfigTagEnabled: () => configTagOutput };
        expect(conditionalUtils.isSupportTicketEnabled(user, extraConfig)).toBe(flag);
      },
    );
  });

  describe.each([
    ['isBalancesEnabled', 'add_funds and Balances'],
    ['isCreditsEnabled', 'Credits'],
  ])('%s', (util, extraTestMessage) => {
    test.each([
      [false, false, false],
      [false, false, true],
      [false, true, true],
      [true, true, false],
    ])(
      `should return %s when user.isAllowedView returns %s and isConfigTagEnabled returns %s on passing ${extraTestMessage}`,
      (flag, isAllowedViewOutput, configTagOutput) => {
        user.isAllowedView.mockReturnValueOnce(isAllowedViewOutput);
        const extraConfig = { isConfigTagEnabled: () => configTagOutput };
        expect(conditionalUtils[util](user, extraConfig)).toBe(flag);
      },
    );
  });

  describe('isPaymentCaptureAndRefundEnabled', () => {
    test.each([
      [true, "doesn't exist", "doesn't exist"],
      [true, "doesn't exist", 'exists'],
      [true, 'exists', "doesn't exist"],
      [false, 'exists', 'exists'],
    ])(
      `should return %s when PaymentCapture %s and Refunds %s`,
      (flag, paymentCapture, refunds) => {
        const extraConfig = { isConfigTagEnabled: jest.fn() };
        extraConfig.isConfigTagEnabled.mockImplementation((tag) => {
          if (tag === 'account.payment_capture' && paymentCapture === 'exists') {
            return true;
          } else if (tag === 'refunds.refund' && refunds === 'exists') {
            return true;
          }
          return false;
        });

        expect(conditionalUtils.isPaymentCaptureAndRefundEnabled(extraConfig)).toBe(flag);
      },
    );
  });
  testUtilWhichUsesSingleUserFunc('isFailedPaymentRetryEnabled', 'isFeatureEnabled');

  describe('isBankAccountDetailsAllowed', () => {
    test.each([
      [false, true, true],
      [true, false, false],
    ])(
      'should return %s when isOrgFeatureExist hide_settlement_details returns %s and isConfigTagEnabled BankAccount returns %s',
      (flag, isOrgFeatureExistOutput, configTagOutput) => {
        isOrgFeatureExist.mockReturnValueOnce(isOrgFeatureExistOutput);
        const extraConfig = { isConfigTagEnabled: () => configTagOutput };
        expect(conditionalUtils.isBankAccountDetailsAllowed(extraConfig)).toBe(flag);
      },
    );
  });

  describe('shouldShowFIRCSection', () => {
    const extraConfig = { isConfigTagEnabled: jest.fn() };

    test('should "return true" when user.international is true', () => {
      const shouldShowFIRCSection = conditionalUtils.shouldShowFIRCSection(
        {
          ...user,
          international: true,
        },
        extraConfig,
      );
      expect(shouldShowFIRCSection).toBe(true);
    });

    test('should return "true" when isConfigTagEnabled is return false', () => {
      extraConfig.isConfigTagEnabled.mockReturnValue(false);

      user.findTag.mockReturnValue(true);

      const shouldShowFIRCSection = conditionalUtils.shouldShowFIRCSection(user, extraConfig);
      expect(shouldShowFIRCSection).toBe(true);
    });

    test('should return "false" when isConfigTagEnabled is true', () => {
      extraConfig.isConfigTagEnabled.mockReturnValue(true);

      user.findTag.mockReturnValue(true);
      const shouldShowFIRCSection = conditionalUtils.shouldShowFIRCSection(user, extraConfig);
      expect(shouldShowFIRCSection).toBe(false);
    });
  });

  testUtilWhichUsesSingleUserFunc('shouldShowFIRCSection', 'findTag', {
    extraTestMessage: 'on passing opgsp_import_flow tag',
  });

  testUtilWhichUsesSingleUserFunc('isExporterRewardsEnabled', 'isFeatureEnabled', {
    extraTestMessage: 'on passing intl_exporter_rewards feature',
  });

  testUtilWhichUsesSingleUserFunc('exporterRewardsOnboardingStatus', 'isFeatureEnabled', {
    reverse: true,
    extraTestMessage: 'on passing intl_exporter_rewards_tnc feature',
  });

  describe('Different hovering text of account access, based on org and user access', () => {
    test('user has access and org is non-i18n', () => {
      const userInfo = {
        has_key_access: true,
        isOrgCurlec: false,
      };

      expect(accountAccessHoverDescription(userInfo)).toEqual(
        ATTR_DETAILS.access_user_account.desc,
      );
    });

    test('user do not have access and org is non-i18n', () => {
      const userInfo = {
        has_key_access: false,
        isOrgCurlec: false,
      };

      expect(accountAccessHoverDescription(userInfo)).toEqual(
        ATTR_DETAILS.restricted_access_user_account.desc,
      );
    });

    test('user has access and org is i18n', () => {
      const userInfo = {
        has_key_access: true,
        isOrgCurlec: true,
      };

      expect(accountAccessHoverDescription(userInfo)).toEqual(
        ATTR_DETAILS.curlec_access_user_account.desc,
      );
    });

    test('user do not have access and org is i18n', () => {
      const userInfo = {
        has_key_access: false,
        isOrgCurlec: true,
      };

      expect(accountAccessHoverDescription(userInfo)).toEqual(
        ATTR_DETAILS.curlec_restricted_access_user_account.desc,
      );
    });
  });

  describe('shouldShowTeamInvitations', () => {
    test('should return true when user has invitations', () => {
      const shouldShowTeamInvitations = conditionalUtils.shouldShowTeamInvitations({
        ...user,
        user: {
          invitations: [
            {
              id: '253693',
              merchant_id: 'djwfjeSTzODQ',
            },
          ],
        },
      });
      expect(shouldShowTeamInvitations).toBe(true);
    });

    test('should return false when user does not have invitations', () => {
      const shouldShowTeamInvitations = conditionalUtils.shouldShowTeamInvitations({
        ...user,
        user: {
          invitations: [],
        },
      });
      expect(shouldShowTeamInvitations).toBe(false);
    });

    test('should return false when user does not have invitations key', () => {
      const shouldShowTeamInvitations = conditionalUtils.shouldShowTeamInvitations({
        ...user,
      });
      expect(shouldShowTeamInvitations).toBe(false);
    });
  });

  describe('isEmailNotificationEnabled', () => {
    test('should return true only when user role is owner or admin', () => {
      for (const rolekey of Object.keys(rolesList)) {
        const role = rolekey[rolesList];
        expect(
          conditionalUtils.isEmailNotificationEnabled({
            ...user,
            role,
          }),
        ).toBe(['owner', 'admin'].includes(role));
      }
    });
  });

  describe('isWhatsAppAccountSetupEnabled', () => {
    test('should return false only when user is belong to Singapore', () => {
      expect(
        conditionalUtils.isEmailNotificationEnabled({
          user: {
            ...user,
            isSGCountry: true,
          },
        }),
      ).toBeFalsy();
    });
  });
});
