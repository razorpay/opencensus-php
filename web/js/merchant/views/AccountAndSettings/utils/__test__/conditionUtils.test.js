import * as conditionalUtils from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { isOrgFeatureExist } from 'merchant/models/User';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import { ATTR_DETAILS } from 'merchant/views/Account/constants';

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
      test.each([
        [reverse, false],
        [!reverse, true],
      ])(
        `should return %s when user.${userProperty} returns %s ${extraTestMessage}`,
        (userKeyOutput, utilOutput) => {
          user[userProperty].mockReturnValueOnce(userKeyOutput);
          expect(conditionalUtils[utilName](user)).toBe(utilOutput);
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

  testUtilWhichUsesSingleUserFunc('isSkipMandatorySummaryPageAllowed', 'findTag', {
    extraTestMessage: 'on passing flashcheckout',
    reverse: true,
  });
  testUtilWhichUsesSingleUserObj('isSmsNotificationEnabled', 'contact_mobile');

  describe('isFlashCheckoutAllowed', () => {
    test.each([
      [false, false, true],
      [false, true, true],
      [false, false, false],
      [true, true, false],
    ])(
      'should return %s when user.isOrgAllowedFunctionality returns %s and user.findTag returns %s',
      (flag, isOrgAllowedFunctionality, findTag) => {
        user.isOrgAllowedFunctionality.mockReturnValueOnce(isOrgAllowedFunctionality);
        user.findTag.mockReturnValueOnce(findTag);
        expect(isFlashCheckoutAllowed(user)).toBe(flag);
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
      `should return %s when user.isWhatsappNotificationEnabled returns %s, user.isOrgAllowedFunctionality returns %s, user.contact_mobile returns %s, user.findTag returns %s and user.role returns %s on passing WhatsappNotification`,
      (output, findTag, isWhatsappNotificationEnabled, contact_mobile, activationStatus, role) => {
        user.isWhatsappNotificationEnabled.mockReturnValueOnce(isWhatsappNotificationEnabled);
        user.user = {
          contact_mobile,
        };
        user.findTag.mockReturnValueOnce(findTag);
        user.role = role;
        user.activation_status = activationStatus;
        expect(conditionalUtils.isWhatsappNotificationEnabled(user)).toBe(output);
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
      'should return %s when user.isAllowedView returns %s, isOrgAxis is %s, isOrgFeatureExist returns %s and user.findTag returns %s on passing hide_razorpay_text_link',
      (flag, isAllowedViewOutput, isOrgAxis, isOrgFeatureExistOutput, findTagOutput) => {
        user.isAllowedView.mockReturnValueOnce(isAllowedViewOutput);
        user.isOrgAxis = isOrgAxis;
        isOrgFeatureExist.mockReturnValueOnce(isOrgFeatureExistOutput);
        user.findTag.mockReturnValueOnce(findTagOutput);
        expect(conditionalUtils.isTrustedBadgeAllowed(user)).toBe(flag);
      },
    );
  });

  describe('isPaymentMethodEnabled', () => {
    test.each([
      [false, false, false, false, 'test'],
      [false, true, false, false, 'live'],
      [false, true, true, true, 'test'],
      [false, false, true, false, 'live'],
      [true, true, true, false, 'live'],
    ])(
      'should return %s when isOrgRZP is %s, isInstrumentRequestAllowed returns %s and mode is %s',
      (flag, isOrgRZP, isInstrumentRequestAllowedOutput, isInstrumentRequestHiddenOutput, mode) => {
        user.isOrgRZP = isOrgRZP;
        user.isInstrumentRequestHidden = isInstrumentRequestHiddenOutput;
        user.isInstrumentRequestAllowed.mockReturnValueOnce(isInstrumentRequestAllowedOutput);
        expect(conditionalUtils.isPaymentMethodEnabled(user, mode)).toBe(flag);
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
      'should return %s when isWebsiteComplianceFlowEnabled is %s, isWebsiteSectionsApplicable is %s and user.findTag returns %s on passing WebsiteAppDetails tag',
      (flag, isWebsiteComplianceFlowEnabled, isWebsiteSectionsApplicable, findTagOutput) => {
        user.findTag.mockReturnValueOnce(findTagOutput);
        user.isWebsiteComplianceFlowEnabled = isWebsiteComplianceFlowEnabled;
        expect(
          conditionalUtils.isWebsiteDetailsEnabled({
            user,
            websiteSectionDetailsData: {
              data: {
                isWebsiteSectionsApplicable,
              },
            },
          }),
        ).toBe(flag);
      },
    );
  });

  describe('isGstDetailsEnabled', () => {
    test.each([
      [false, false, false, false],
      [false, true, true, false],
      [false, true, false, true],
      [true, true, false, false],
    ])(
      'should return %s when user.isAllowedView returns %s, isUnregisteredBusiness is %s and user.findTag returns %s on passing profile_gst and Gst tags',
      (flag, isAllowedViewOutput, isUnregisteredBusiness, findTagOutput) => {
        user.isAllowedView.mockReturnValueOnce(isAllowedViewOutput);
        user.findTag.mockReturnValueOnce(findTagOutput);
        user.isUnregisteredBusiness = isUnregisteredBusiness;
        expect(conditionalUtils.isGstDetailsEnabled(user)).toBe(flag);
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
      'should return %s when user.isAdminOrOwner is %s, isFdTicketsEnabled is %s, isComdelApiEnabled is %s and user.findTag returns %s on passing SupportHistory',
      (flag, isAdminOrOwner, isFdTicketsEnabled, isComdelApiEnabled, findTagOutput) => {
        user.isAdminOrOwner = isAdminOrOwner;
        user.isFdTicketsEnabled = isFdTicketsEnabled;
        user.isComdelApiEnabled = isComdelApiEnabled;
        user.findTag.mockReturnValueOnce(findTagOutput);
        expect(conditionalUtils.isSupportTicketEnabled(user)).toBe(flag);
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
      `should return %s when user.isAllowedView returns %s and user.findTag returns %s on passing ${extraTestMessage}`,
      (flag, isAllowedViewOutput, findTagOutput) => {
        user.isAllowedView.mockReturnValueOnce(isAllowedViewOutput);
        user.findTag.mockReturnValueOnce(findTagOutput);
        expect(conditionalUtils[util](user)).toBe(flag);
      },
    );
  });

  testUtilWhichUsesSingleUserFunc('isReminderEnabled', 'findTag', {
    reverse: true,
    extraTestMessage: 'on passing Reminders',
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
        user.findTag.mockImplementation((tag) => {
          if (
            tag === HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentCapture &&
            paymentCapture === 'exists'
          ) {
            return true;
          } else if (tag === HIDDEN_INTERNATIONAL_FEATURES_TAGS.Refunds && refunds === 'exists') {
            return true;
          }
          return false;
        });

        expect(conditionalUtils.isPaymentCaptureAndRefundEnabled(user)).toBe(flag);
      },
    );
  });
  testUtilWhichUsesSingleUserFunc('isFailedPaymentRetryEnabled', 'isFeatureEnabled');

  describe('isBankAccountDetailsAllowed', () => {
    test.each([
      [false, true, true],
      [true, false, false],
    ])(
      'should return %s when isOrgFeatureExist hide_settlement_details returns %s and user.findTag BankAccount returns %s',
      (flag, isOrgFeatureExistOutput, findTagOutput) => {
        isOrgFeatureExist.mockReturnValueOnce(isOrgFeatureExistOutput);
        user.findTag.mockReturnValueOnce(findTagOutput);
        expect(conditionalUtils.isBankAccountDetailsAllowed(user)).toBe(flag);
      },
    );
  });

  testUtilWhichUsesSingleUserFunc('isSettlementsAllowed', 'findTag', {
    reverse: true,
    extraTestMessage: 'on passing Settlements',
  });

  describe('shouldShowFIRCSection', () => {
    test('should return true when user.international is true', () => {
      const shouldShowFIRCSection = conditionalUtils.shouldShowFIRCSection({
        ...user,
        international: true,
      });
      expect(shouldShowFIRCSection).toBe(true);
    });
  });

  testUtilWhichUsesSingleUserFunc('shouldShowFIRCSection', 'findTag', {
    extraTestMessage: 'on passing opgsp_import_flow tag',
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
});
