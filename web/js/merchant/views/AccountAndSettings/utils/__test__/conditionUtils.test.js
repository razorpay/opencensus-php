import * as conditionalUtils from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { isOrgFeatureExist } from 'merchant/models/User';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';

jest.mock('merchant/models/User', () => ({
  ...jest.requireActual('merchant/models/User'),
  isOrgFeatureExist: jest.fn(),
}));

const { isFlashCheckoutAllowed } = conditionalUtils;

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
  isWebsiteComplianceFlowEnabled: undefined,
  isFeatureEnabled: jest.fn(),
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
      ])(`should return %s when returns ${userProperty} is %s`, (userPropertyValue, utilOutput) => {
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
        // console.log("🚀 ~ file: conditionUtils.test.js:38 ~ describe ~ findTag", findTag, typeof(findTag), user.findTag())
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
      [false, false, true, false],
      [false, true, true, false],
      [false, false, false, true],
      [true, false, false, false],
    ])(
      'should return %s when isOrgAxis is %s, isOrgFeatureExist returns %s and user.findTag returns %s on passing hide_razorpay_text_link',
      (flag, isOrgAxis, isOrgFeatureExistOutput, findTagOutput) => {
        user.isOrgAxis = isOrgAxis;
        isOrgFeatureExist.mockReturnValueOnce(isOrgFeatureExistOutput);
        user.findTag.mockReturnValueOnce(findTagOutput);
        expect(conditionalUtils.isTrustedBadgeAllowed(user)).toBe(flag);
      },
    );
  });

  describe('isPaymentMethodEnabled', () => {
    test.each([
      [false, false, false, 'test'],
      [false, true, false, 'live'],
      [false, true, true, 'test'],
      [false, false, true, 'live'],
      [true, true, true, 'live'],
    ])(
      'should return %s when isOrgRZP is %s, isInstrumentRequestAllowed returns %s and mode is %s',
      (flag, isOrgRZP, isInstrumentRequestAllowedOutput, mode) => {
        user.isOrgRZP = isOrgRZP;
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
      [false, true, false],
      [false, false, true],
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
});
