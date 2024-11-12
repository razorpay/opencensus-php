import { Modules } from 'common/constant/enums';
import User from 'common/typings/User';
import * as LocalStorageService from 'common/utils/localStorage';
import { isExperimentActive } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { PERMISSIONS } from 'merchant/helpers/permissions/constant';
import { initIsActionAllowed, isRBACExperimentEnabled } from 'merchant/helpers/permissions/utils';
import { isBillMeMerchant } from 'merchant/utils/omniUtils';
import {
  AdditionalContextInterface,
  SectionCardInterface,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import {
  BankAccountSettlementFields,
  BankAccountSettlementTitles,
  BusinessSettingsFields,
  BusinessSettingsTitles,
  CheckoutSettingsFields,
  CheckoutSettingsTitles,
  InternationalSettingsFields,
  InternationalSettingsTitles,
  Checkout_V2_SettingsFields,
  Checkout_V2_SettingTitles,
  NotificationSettingsFields,
  NotificationSettingsTitles,
  PaymentMethodsFields,
  PaymentMethodsTitles,
  PaymentRefundsFields,
  PaymentRefundsTitles,
  PricingFields,
  PricingTitles,
  RewardGrowth,
  SectionCardDataFields,
  WebsiteAppSettingsFields,
  WebsiteAppSettingsTitles,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import {
  isAccountDetailsEnabled,
  isApiKeyEnabled,
  isApplicationEnabled,
  isBalancesEnabled,
  isBankAccountDetailsAllowed,
  isConfigurationViewAllowed,
  isCreditsEnabled,
  isEmailNotificationEnabled,
  isFailedPaymentRetryEnabled,
  isFlashCheckoutAllowed,
  isGstDetailsEnabled,
  isPaymentCaptureAndRefundEnabled,
  isPaymentMethodEnabled,
  isProfileViewAllowed,
  isReminderEnabled,
  isSkipMandatorySummaryPageAllowed,
  isSmsNotificationEnabled,
  isSupportTicketEnabled,
  isTeamManagementAllowed,
  isTrustedBadgeAllowed,
  isWebhookEnabled,
  isWebsiteDetailsEnabled,
  isWhatsappNotificationEnabled,
  shouldShowFIRCSection,
  shouldShowFeeBearerSelfServe,
  shouldShowTeamInvitations,
  isWhatsAppAccountSetupEnabled,
  isCustomerSupportDetailsEnabled,
  isExporterRewardsEnabled,
  isCheckoutV2SettingsAllowed,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';

export const AccountNSettingsIcons = {
  payment_methods: 'i-payment-methods',
  website_app_settings: 'i-monitor',
  business_settings: 'i-brief-case',
  payments_refunds: 'i-dollars',
  bank_and_settlements: 'i-file-asset',
  notification_settings: 'i-notification-bell',
  checkout_settings: 'i-shopping-cart',
  pricing: 'i-zap',
  streaks: 'i-play',
  international_settings: 'i-globe',
};

export const Sections: SectionCardInterface[] = [
  {
    id: SectionCardDataFields.PAYMENT_METHODS,
    title: 'Payment methods',
    icon: AccountNSettingsIcons.payment_methods,
    iconBackground: 'linear-gradient(161.88deg, #30c5d8 18.69%, #1566f1 90.37%)',
    additionalCondition:
      ({ mode }: AdditionalContextInterface) =>
      (user: User): boolean =>
        isPaymentMethodEnabled(user, mode),
    subSections: [
      {
        id: PaymentMethodsFields.CARDS,
        title: PaymentMethodsTitles[PaymentMethodsFields.CARDS],
        href: ROUTES_INFO.CARDS,
        additionalCondition:
          () =>
          (user: User): boolean =>
            user.isIERevampEnabled,
      },
      {
        id: PaymentMethodsFields.UPI,
        title: PaymentMethodsTitles[PaymentMethodsFields.UPI],
        href: ROUTES_INFO.UPI_QR,
        additionalCondition:
          () =>
          (user: User): boolean =>
            user.isIERevampEnabled,
      },
      {
        id: PaymentMethodsFields.NETBANKING,
        title: PaymentMethodsTitles[PaymentMethodsFields.NETBANKING],
        href: ROUTES_INFO.NETBANKING,
        additionalCondition:
          () =>
          (user: User): boolean =>
            user.isIERevampEnabled,
      },
      {
        id: PaymentMethodsFields.EMI,
        title: PaymentMethodsTitles[PaymentMethodsFields.EMI],
        href: ROUTES_INFO.EMI,
        additionalCondition:
          () =>
          (user: User): boolean =>
            user.isIERevampEnabled,
      },
      {
        id: PaymentMethodsFields.WALLET,
        title: PaymentMethodsTitles[PaymentMethodsFields.WALLET],
        href: ROUTES_INFO.WALLET,
        additionalCondition:
          () =>
          (user: User): boolean =>
            user.isIERevampEnabled,
      },
      {
        id: PaymentMethodsFields.PAYLATER,
        title: PaymentMethodsTitles[PaymentMethodsFields.PAYLATER],
        href: ROUTES_INFO.PAY_LATER,
        additionalCondition:
          () =>
          (user: User): boolean =>
            user.isIERevampEnabled,
      },
      {
        id: PaymentMethodsFields.INTERNATIONAL,
        title: PaymentMethodsTitles[PaymentMethodsFields.INTERNATIONAL],
        href: ROUTES_INFO.INTERNATIONAL_PAYMENTS,
        additionalCondition:
          () =>
          (user: User): boolean =>
            user.isIERevampEnabled,
      },
      {
        id: PaymentMethodsFields.MEAL_CARD,
        title: PaymentMethodsTitles[PaymentMethodsFields.MEAL_CARD],
        href: ROUTES_INFO.MEAL_CARD,
        additionalCondition:
          () =>
          (user: User): boolean =>
            user.isIERevampEnabled && user.isSodexoInstrumentEnabled,
      },
      {
        id: PaymentMethodsFields.CARDS,
        title: 'Cards',
        href: `${ROUTES_INFO.PAYMENT_METHODS}?instrument=card`,
        additionalCondition:
          () =>
          (user: User): boolean =>
            !user.isIERevampEnabled,
      },
      {
        id: PaymentMethodsFields.UPI,
        title: 'UPI/QR',
        href: `${ROUTES_INFO.PAYMENT_METHODS}?instrument=upi`,
        additionalCondition:
          () =>
          (user: User): boolean =>
            !user.isIERevampEnabled,
      },
      {
        id: PaymentMethodsFields.NETBANKING,
        title: 'Netbanking',
        href: `${ROUTES_INFO.PAYMENT_METHODS}?instrument=netbanking`,
        additionalCondition:
          () =>
          (user: User): boolean =>
            !user.isIERevampEnabled,
      },
      {
        id: PaymentMethodsFields.EMI,
        title: 'EMI',
        href: `${ROUTES_INFO.PAYMENT_METHODS}?instrument=emi`,
        additionalCondition:
          () =>
          (user: User): boolean =>
            !user.isIERevampEnabled,
      },
      {
        id: PaymentMethodsFields.WALLET,
        title: 'Wallet',
        href: `${ROUTES_INFO.PAYMENT_METHODS}?instrument=wallet`,
        additionalCondition:
          () =>
          (user: User): boolean =>
            !user.isIERevampEnabled,
      },
      {
        id: PaymentMethodsFields.PAYLATER,
        title: 'Pay Later',
        href: `${ROUTES_INFO.PAYMENT_METHODS}?instrument=paylater`,
        additionalCondition:
          () =>
          (user: User): boolean =>
            !user.isIERevampEnabled,
      },
      {
        id: PaymentMethodsFields.INTERNATIONAL,
        title: 'International payments',
        href: `${ROUTES_INFO.PAYMENT_METHODS}?instrument=international`,
        additionalCondition:
          () =>
          (user: User): boolean =>
            !user.isIERevampEnabled,
      },
      {
        id: PaymentMethodsFields.MEAL_CARD,
        title: PaymentMethodsTitles[PaymentMethodsFields.MEAL_CARD],
        href: `${ROUTES_INFO.PAYMENT_METHODS}?instrument=meal-card`,
        additionalCondition:
          () =>
          (user: User): boolean =>
            !user.isIERevampEnabled && user.isSodexoInstrumentEnabled,
      },
    ],
  },
  {
    id: SectionCardDataFields.WEBSITE_APP_SETTINGS,
    title: 'Website and app settings',
    icon: AccountNSettingsIcons.website_app_settings,
    iconBackground: 'linear-gradient(154.84deg, #01B358 17.49%, #008CB1 103.14%)',
    subSections: [
      {
        id: WebsiteAppSettingsFields.WEBSITE_APP_DETAIL,
        title: WebsiteAppSettingsTitles[WebsiteAppSettingsFields.BUSINESS_POLICY_DETAILS],
        href: ROUTES_INFO.WEBSITE_APP_SETTINGS,
        additionalCondition:
          ({ websiteSectionDetailsData, extraConfig }: AdditionalContextInterface) =>
          (user: User): boolean =>
            isWebsiteDetailsEnabled({ user, websiteSectionDetailsData, extraConfig }),
      },
      {
        id: WebsiteAppSettingsFields.BUSINESS_WEBSITE_DETAILS,
        title: WebsiteAppSettingsTitles[WebsiteAppSettingsFields.BUSINESS_WEBSITE_DETAILS],
        href: ROUTES_INFO.BUSINESS_WEBSITE_SETTINGS,
      },
      {
        id: WebsiteAppSettingsFields.API_KEYS,
        title: WebsiteAppSettingsTitles[WebsiteAppSettingsFields.API_KEYS],
        href: ROUTES_INFO.API_KEYS,
        additionalCondition:
          () =>
          (user: User): boolean =>
            isApiKeyEnabled(user),
      },
      {
        id: WebsiteAppSettingsFields.WEBHOOKS,
        title: WebsiteAppSettingsTitles[WebsiteAppSettingsFields.WEBHOOKS],
        href: ROUTES_INFO.WEBHOOKS,
        additionalCondition:
          () =>
          (user: User): boolean =>
            isWebhookEnabled(user),
        onLinkClick: (): void => {
          selfServeTrackInitiate({
            selfServeAction: 'Webhook List Fetched',
            page: 'Webhooks',
            screen: Modules.AccountAndSettings,
          });
        },
      },
      {
        id: WebsiteAppSettingsFields.APPLICATIONS,
        title: WebsiteAppSettingsTitles[WebsiteAppSettingsFields.APPLICATIONS],
        href: ROUTES_INFO.APPLICATIONS,
        additionalCondition:
          () =>
          (user: User): boolean =>
            isApplicationEnabled(user),
      },
    ],
  },
  {
    id: SectionCardDataFields.BUSINESS_SETTINGS,
    title: 'Business settings',
    icon: AccountNSettingsIcons.business_settings,
    iconBackground: 'linear-gradient(159.37deg, #C592FF 13.68%, #2A86F3 123.84%)',
    subSections: [
      {
        id: BusinessSettingsFields.ACCOUNT_DETAILS,
        title: BusinessSettingsTitles[BusinessSettingsFields.ACCOUNT_DETAILS],
        href: ROUTES_INFO.ACCOUNT_DETAILS,
      },
      {
        id: BusinessSettingsFields.BUSINESS_DETAILS,
        title: BusinessSettingsTitles[BusinessSettingsFields.BUSINESS_DETAILS],
        href: ROUTES_INFO.BUSINESS_DETAILS,
      },
      {
        id: BusinessSettingsFields.GST_DETAILS,
        title: BusinessSettingsTitles[BusinessSettingsFields.GST_DETAILS],
        href: ROUTES_INFO.GST_DETAILS,
        additionalCondition:
          ({ extraConfig }: AdditionalContextInterface) =>
          (user: User): boolean =>
            isGstDetailsEnabled(user, extraConfig),
      },
      {
        id: BusinessSettingsFields.CUSTOMER_SUPPORT_DETAILS,
        title: BusinessSettingsTitles[BusinessSettingsFields.CUSTOMER_SUPPORT_DETAILS],
        href: ROUTES_INFO.CUSTOMER_SUPPORT_DETAILS,
        additionalCondition:
          ({ extraConfig }: AdditionalContextInterface) =>
          (): boolean =>
            isCustomerSupportDetailsEnabled(extraConfig),
      },
      {
        id: BusinessSettingsFields.ACTIVATION_DETAILS,
        title: BusinessSettingsTitles[BusinessSettingsFields.ACTIVATION_DETAILS],
        href: ROUTES_INFO.ACTIVATION_DETAILS,
        additionalCondition:
          () =>
          (user: User): boolean =>
            isAccountDetailsEnabled(user),
      },
      {
        id: BusinessSettingsFields.MANAGE_TEAM,
        title: BusinessSettingsTitles[BusinessSettingsFields.MANAGE_TEAM],
        href: ROUTES_INFO.MANAGE_TEAM_DETAILS,
        additionalCondition:
          () =>
          (user: User): boolean =>
            isTeamManagementAllowed(user),
      },
      {
        id: BusinessSettingsFields.INVITATIONS,
        title: BusinessSettingsTitles[BusinessSettingsFields.INVITATIONS],
        href: ROUTES_INFO.TEAM_INVITATIONS,
        additionalCondition:
          () =>
          (user: User): boolean =>
            shouldShowTeamInvitations(user),
      },
      {
        id: BusinessSettingsFields.SUPPORT_TICKETS,
        title: BusinessSettingsTitles[BusinessSettingsFields.SUPPORT_TICKETS],
        href: ROUTES_INFO.SUPPORT_TICKETS_MERCHANT,
        additionalCondition:
          ({ extraConfig }: AdditionalContextInterface) =>
          (user: User): boolean =>
            isSupportTicketEnabled(user, extraConfig),
      },
      {
        id: BusinessSettingsFields.DIGITAL_BILL_SETTINGS,
        title: BusinessSettingsTitles[BusinessSettingsFields.DIGITAL_BILL_SETTINGS],
        href: ROUTES_INFO.DIGITAL_BILL_SETTINGS,
        additionalCondition:
          ({ extraConfig: { abExperiments } }: AdditionalContextInterface) =>
          (): boolean =>
            isBillMeMerchant({ abExperiments }),
      },
      {
        id: BusinessSettingsFields.STORE_SETTINGS,
        title: BusinessSettingsTitles[BusinessSettingsFields.STORE_SETTINGS],
        href: ROUTES_INFO.STORE_SETTINGS,
        additionalCondition:
          ({ extraConfig: { abExperiments } }: AdditionalContextInterface) =>
          (): boolean =>
            isBillMeMerchant({ abExperiments }),
      },
    ],
  },
  {
    id: SectionCardDataFields.PAYMENTS_REFUNDS,
    title: 'Payments and refunds',
    icon: AccountNSettingsIcons.payments_refunds,
    iconBackground: 'linear-gradient(330.16deg, #30C5D8 1.72%, #1566F1 91.46%)',
    subSections: [
      {
        id: PaymentRefundsFields.BALANCES,
        title: PaymentRefundsTitles[PaymentRefundsFields.BALANCES],
        href: ROUTES_INFO.BALANCES,
        additionalCondition:
          ({ extraConfig, rbacExperiment }: AdditionalContextInterface) =>
          (user: User): boolean => {
            const isActionAllowed = initIsActionAllowed(user, rbacExperiment);
            const isRBACEnabled = isRBACExperimentEnabled(user, rbacExperiment);
            return (
              isBalancesEnabled(user, extraConfig, isRBACEnabled) &&
              isActionAllowed({
                permissions: [PERMISSIONS.VIEW_BALANCE],
              })
            );
          },
      },
      {
        id: PaymentRefundsFields.CREDITS,
        title: PaymentRefundsTitles[PaymentRefundsFields.CREDITS],
        href: ROUTES_INFO.CREDITS,
        additionalCondition:
          ({ extraConfig }: AdditionalContextInterface) =>
          (user: User): boolean =>
            isCreditsEnabled(user, extraConfig),
      },
      {
        id: PaymentRefundsFields.REMINDERS,
        title: PaymentRefundsTitles[PaymentRefundsFields.REMINDERS],
        href: ROUTES_INFO.REMINDERS,
        additionalCondition:
          ({ extraConfig }: AdditionalContextInterface) =>
          (): boolean =>
            isReminderEnabled(extraConfig),
      },
      {
        id: PaymentRefundsFields.TRANSACTION_LIMITS,
        title: PaymentRefundsTitles[PaymentRefundsFields.TRANSACTION_LIMITS],
        href: ROUTES_INFO.TRANSACTION_LIMITS,
        additionalCondition: ({ rbacExperiment }) => {
          return (user: User) => {
            const isActionAllowed = initIsActionAllowed(user, rbacExperiment);
            return isActionAllowed({
              permissions: [PERMISSIONS.VIEW_TRANSACTION_LIMIT],
            });
          };
        },
      },
      {
        id: PaymentRefundsFields.FEE_BEARER,
        title: PaymentRefundsTitles[PaymentRefundsFields.FEE_BEARER],
        href: ROUTES_INFO.FEE_BEARER,
        additionalCondition:
          ({ allowCFBInternational }: AdditionalContextInterface) =>
          (user: User): boolean =>
            shouldShowFeeBearerSelfServe({ allowCFBInternational, user }),
      },
      {
        id: PaymentRefundsFields.CAPTURE_REFUND_SETTINGS,
        title: PaymentRefundsTitles[PaymentRefundsFields.CAPTURE_REFUND_SETTINGS],
        href: ROUTES_INFO.CAPTURE_AND_REFUND_SETTINGS,
        additionalCondition:
          ({ extraConfig }: AdditionalContextInterface) =>
          (): boolean =>
            isPaymentCaptureAndRefundEnabled(extraConfig),
      },
      {
        id: PaymentRefundsFields.FAILED_PAYMENTS_RETRY,
        title: PaymentRefundsTitles[PaymentRefundsFields.FAILED_PAYMENTS_RETRY],
        href: ROUTES_INFO.FAILED_PAYMENTS_RETRY,
        additionalCondition:
          () =>
          (user: User): boolean =>
            isFailedPaymentRetryEnabled(user),
      },
      {
        id: PaymentRefundsFields.WHATSAPP_ACCOUNT_SETUP,
        title: PaymentRefundsTitles[PaymentRefundsFields.WHATSAPP_ACCOUNT_SETUP],
        href: ROUTES_INFO.WHATSAPP_ACCOUNT_SETUP,
        isNew: true,
        additionalCondition:
          ({ extraConfig }) =>
          (user: User): boolean =>
            isWhatsAppAccountSetupEnabled(user, extraConfig, true),
      },
    ],
  },
  {
    id: SectionCardDataFields.BANK_ACCOUNTS_SETTLEMENTS,
    title: 'Bank accounts and settlements',
    icon: AccountNSettingsIcons.bank_and_settlements,
    iconBackground: 'linear-gradient(162.28deg, #2A86F3 27.27%, #C592FF 121.23%)',
    additionalCondition: (): ((user: User) => boolean) => isProfileViewAllowed,
    subSections: [
      {
        id: BankAccountSettlementFields.BANK_ACCOUNT_DETAILS,
        title: BankAccountSettlementTitles[BankAccountSettlementFields.BANK_ACCOUNT_DETAILS],
        href: ROUTES_INFO.BANK_ACCOUNT_DETAILS,
        additionalCondition:
          ({ extraConfig }: AdditionalContextInterface) =>
          (): boolean =>
            isBankAccountDetailsAllowed(extraConfig),
      },
      {
        id: BankAccountSettlementFields.SETTLEMENT_DETAILS,
        title: BankAccountSettlementTitles[BankAccountSettlementFields.SETTLEMENT_DETAILS],
        href: ROUTES_INFO.SETTLEMENT_DETAILS,
        additionalCondition: ({ rbacExperiment }) => {
          return (user: User) => {
            const isActionAllowed = initIsActionAllowed(user, rbacExperiment);
            return isActionAllowed({
              permissions: [PERMISSIONS.VIEW_SETTLEMENT],
            });
          };
        },
      },
    ],
  },
  {
    id: SectionCardDataFields.NOTIFICATION_SETTINGS,
    title: 'Notification settings',
    icon: AccountNSettingsIcons.notification_settings,
    iconBackground: 'linear-gradient(156.8deg, #C592FF 3.75%, #1566F1 130.62%)',
    additionalCondition:
      () =>
      (user: User): boolean =>
        isConfigurationViewAllowed(user),
    subSections: [
      {
        id: NotificationSettingsFields.EMAIL,
        title: NotificationSettingsTitles[NotificationSettingsFields.EMAIL],
        href: ROUTES_INFO.EMAIL_NOTIFICATIONS,
        additionalCondition:
          () =>
          (user: User): boolean =>
            isEmailNotificationEnabled(user),
      },
      {
        id: NotificationSettingsFields.SMS,
        title: NotificationSettingsTitles[NotificationSettingsFields.SMS],
        href: ROUTES_INFO.SMS_NOTIFICATIONS,
        additionalCondition:
          () =>
          (user: User): boolean =>
            isSmsNotificationEnabled(user),
      },
      {
        id: NotificationSettingsFields.WHATSAPP,
        title: NotificationSettingsTitles[NotificationSettingsFields.WHATSAPP],
        href: ROUTES_INFO.WHATSAPP_NOTIFICATIONS,
        additionalCondition:
          ({ extraConfig }: AdditionalContextInterface) =>
          (user: User): boolean =>
            isWhatsappNotificationEnabled(user, extraConfig),
      },
    ],
  },
  {
    id: SectionCardDataFields.CHECKOUT_SETTINGS,
    title: 'Checkout settings',
    icon: AccountNSettingsIcons.checkout_settings,
    iconBackground: 'linear-gradient(155.9deg, #EC9B26 10.71%, #BD7A03 59.94%)',
    additionalCondition:
      ({ extraConfig }: AdditionalContextInterface) =>
      (user: User): boolean =>
        !isCheckoutV2SettingsAllowed(extraConfig) &&
        (isConfigurationViewAllowed(user) || isTrustedBadgeAllowed(user, extraConfig)),
    subSections: [
      {
        id: CheckoutSettingsFields.BRANDING,
        title: CheckoutSettingsTitles[CheckoutSettingsFields.BRANDING],
        href: ROUTES_INFO.BRANDING,
        additionalCondition:
          () =>
          (user: User): boolean =>
            isConfigurationViewAllowed(user),
      },
      {
        id: CheckoutSettingsFields.FLASH_CHECKOUT,
        title: CheckoutSettingsTitles[CheckoutSettingsFields.FLASH_CHECKOUT],
        href: ROUTES_INFO.FLASH_CHECKOUT,
        additionalCondition:
          ({ extraConfig }: AdditionalContextInterface) =>
          (user: User): boolean =>
            isConfigurationViewAllowed(user) && isFlashCheckoutAllowed(user, extraConfig),
      },
      {
        id: CheckoutSettingsFields.SKIP_MANDATE_SUMMARY_PAGE,
        title: CheckoutSettingsTitles[CheckoutSettingsFields.SKIP_MANDATE_SUMMARY_PAGE],
        href: ROUTES_INFO.SKIP_MANDATORY_SUMMARY_PAGE,
        additionalCondition:
          ({ extraConfig }: AdditionalContextInterface) =>
          (user: User): boolean =>
            isConfigurationViewAllowed(user) && isSkipMandatorySummaryPageAllowed(extraConfig),
      },
      {
        id: CheckoutSettingsFields.TRUSTED_BADGE,
        title: CheckoutSettingsTitles[CheckoutSettingsFields.TRUSTED_BADGE],
        href: ROUTES_INFO.TRUSTED_BADGE,
        additionalCondition:
          ({ extraConfig }: AdditionalContextInterface) =>
          (user: User): boolean =>
            isTrustedBadgeAllowed(user, extraConfig),
      },
    ],
  },
  {
    id: SectionCardDataFields.CHECKOUT_V2_SETTINGS,
    title: 'Checkout settings',
    icon: AccountNSettingsIcons.checkout_settings,
    iconBackground: 'linear-gradient(155.9deg, #EC9B26 10.71%, #BD7A03 59.94%)',
    additionalCondition:
      ({ extraConfig }: AdditionalContextInterface) =>
      (user: User): boolean =>
        isCheckoutV2SettingsAllowed(extraConfig) && isConfigurationViewAllowed(user),
    subSections: [
      {
        id: Checkout_V2_SettingsFields.CHECKOUT_STYLING,
        title: Checkout_V2_SettingTitles[Checkout_V2_SettingsFields.CHECKOUT_STYLING],
        href: ROUTES_INFO.CHECKOUT_STYLING,
        additionalCondition:
          ({ extraConfig }: AdditionalContextInterface) =>
          (user: User): boolean =>
            isConfigurationViewAllowed(user) || isTrustedBadgeAllowed(user, extraConfig),
      },
      {
        id: Checkout_V2_SettingsFields.FEATURES,
        title: Checkout_V2_SettingTitles[Checkout_V2_SettingsFields.FEATURES],
        href: ROUTES_INFO.CHECKOUT_FEATURES,
        additionalCondition:
          () =>
          (user: User): boolean =>
            isConfigurationViewAllowed(user),
      },
    ],
  },
  {
    id: SectionCardDataFields.PRICING,
    title: 'Pricing',
    icon: AccountNSettingsIcons.pricing,
    iconBackground: 'linear-gradient(126deg, #C8BFFF 9.01%, #553EDF 98.6%)',
    additionalCondition:
      () =>
      (user: User): boolean =>
        user?.isBundlePricingEnabled,
    subSections: [
      {
        id: PricingFields.PRICING_PLANS,
        title: PricingTitles[PricingFields.PRICING_PLANS],
        href: ROUTES_INFO.PRICING_PLANS,
        isNew: true,
      },
    ],
  },
  {
    id: SectionCardDataFields.STREAKS_REWARD,
    title: 'Rewards',
    icon: AccountNSettingsIcons.streaks,
    iconBackground: 'linear-gradient(126deg, #C8BFFF 9.01%, #553EDF 98.6%)',
    additionalCondition:
      ({
        extraConfig: { abExperiments: { STREAKS_REWARDS_GROWTH } = {} },
        mode,
      }: AdditionalContextInterface) =>
      (): boolean =>
        mode === 'live' &&
        (LocalStorageService.getItem('CUSTOMER_GLU_URL_E2E') === 'on' ||
          isExperimentActive(STREAKS_REWARDS_GROWTH)),
    // CUSTOMER_GLU_URL_E2E - e2e run based on localStorage instead of  exp evaluation
    subSections: [
      {
        id: RewardGrowth.STREAK_REWARD,
        title: 'Streaks',
        href: ROUTES_INFO.STREAK_REWARD,
        isNew: true,
      },
    ],
  },
  {
    id: SectionCardDataFields.INTERNATIONAL_SETTINGS,
    title: 'International payments settings',
    icon: AccountNSettingsIcons.international_settings,
    iconBackground: 'linear-gradient(162.28deg, #2A86F3 27.27%, #C592FF 121.23%)',
    additionalCondition:
      ({ extraConfig }: AdditionalContextInterface) =>
      (user: User): boolean =>
        shouldShowFIRCSection(user, extraConfig),
    subSections: [
      {
        id: InternationalSettingsFields.FIRS,
        title: InternationalSettingsTitles[InternationalSettingsFields.FIRS],
        href: ROUTES_INFO.FIRS,
      },
      {
        id: InternationalSettingsFields.INTERNATIONAL_PAYMENTS_CODES,
        title:
          InternationalSettingsTitles[InternationalSettingsFields.INTERNATIONAL_PAYMENTS_CODES],
        href: ROUTES_INFO.INTERNATIONAL_PAYMENTS_CODES,
      },
      {
        id: InternationalSettingsFields.EXPORTER_REWARDS,
        title: InternationalSettingsTitles[InternationalSettingsFields.EXPORTER_REWARDS],
        href: ROUTES_INFO.EXPORTER_REWARDS,
        isNew: true,
        additionalCondition:
          () =>
          (user: User): boolean =>
            isExporterRewardsEnabled(user),
      },
    ],
  },
];
