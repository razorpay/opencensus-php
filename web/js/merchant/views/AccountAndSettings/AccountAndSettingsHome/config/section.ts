import {
  isConfigurationViewAllowed,
  isWhatsappNotificationEnabled,
  isSmsNotificationEnabled,
  isTrustedBadgeAllowed,
  isSkipMandatorySummaryPageAllowed,
  isFlashCheckoutAllowed,
  isPaymentMethodEnabled,
  shouldShowFeeBearerSelfServe,
  isWebsiteDetailsEnabled,
  isWebhookEnabled,
  isApiKeyEnabled,
  isGstDetailsEnabled,
  isTeamManagementAllowed,
  isAccountDetailsEnabled,
  isSupportTicketEnabled,
  isFailedPaymentRetryEnabled,
  isPaymentCaptureAndRefundEnabled,
  isReminderEnabled,
  isCreditsEnabled,
  isBalancesEnabled,
  isBankAccountDetailsAllowed,
  isProfileViewAllowed,
  shouldShowFIRCSection,
  shouldShowTeamInvitations,
  isApplicationEnabled,
  isEmailNotificationEnabled,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import {
  AdditionalContextInterface,
  SectionCardInterface,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import {
  SectionCardDataFields,
  PaymentMethodsFields,
  WebsiteAppSettingsFields,
  BusinessSettingsFields,
  PaymentRefundsFields,
  NotificationSettingsFields,
  CheckoutSettingsFields,
  BankAccountSettlementFields,
  PricingFields,
  PaymentMethodsTitles,
  WebsiteAppSettingsTitles,
  BusinessSettingsTitles,
  PaymentRefundsTitles,
  BankAccountSettlementTitles,
  NotificationSettingsTitles,
  CheckoutSettingsTitles,
  PricingTitles,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import User from 'common/typings/User';
import { Modules } from 'common/constant/enums';

export const AccountNSettingsIcons = {
  payment_methods: 'i-payment-methods',
  website_app_settings: 'i-monitor',
  business_settings: 'i-brief-case',
  payments_refunds: 'i-dollars',
  bank_and_settlements: 'i-file-asset',
  notification_settings: 'i-notification-bell',
  checkout_settings: 'i-shopping-cart',
  pricing: 'i-zap',
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
        title: WebsiteAppSettingsTitles[WebsiteAppSettingsFields.WEBSITE_APP_DETAIL],
        href: ROUTES_INFO.WEBSITE_APP_SETTINGS,
        additionalCondition:
          ({ websiteSectionDetailsData }: AdditionalContextInterface) =>
          (user: User): boolean =>
            isWebsiteDetailsEnabled({ user, websiteSectionDetailsData }),
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
          () =>
          (user: User): boolean =>
            isGstDetailsEnabled(user),
      },
      {
        id: BusinessSettingsFields.CUSTOMER_SUPPORT_DETAILS,
        title: BusinessSettingsTitles[BusinessSettingsFields.CUSTOMER_SUPPORT_DETAILS],
        href: ROUTES_INFO.CUSTOMER_SUPPORT_DETAILS,
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
          () =>
          (user: User): boolean =>
            isSupportTicketEnabled(user),
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
          () =>
          (user: User): boolean =>
            isBalancesEnabled(user),
      },
      {
        id: PaymentRefundsFields.CREDITS,
        title: PaymentRefundsTitles[PaymentRefundsFields.CREDITS],
        href: ROUTES_INFO.CREDITS,
        additionalCondition:
          () =>
          (user: User): boolean =>
            isCreditsEnabled(user),
      },
      {
        id: PaymentRefundsFields.REMINDERS,
        title: PaymentRefundsTitles[PaymentRefundsFields.REMINDERS],
        href: ROUTES_INFO.REMINDERS,
        additionalCondition:
          () =>
          (user: User): boolean =>
            isReminderEnabled(user),
      },
      {
        id: PaymentRefundsFields.TRANSACTION_LIMITS,
        title: PaymentRefundsTitles[PaymentRefundsFields.TRANSACTION_LIMITS],
        href: ROUTES_INFO.TRANSACTION_LIMITS,
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
          () =>
          (user: User): boolean =>
            isPaymentCaptureAndRefundEnabled(user),
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
        additionalCondition: (): ((user: User) => boolean) => isBankAccountDetailsAllowed,
      },
      {
        id: BankAccountSettlementFields.SETTLEMENT_DETAILS,
        title: BankAccountSettlementTitles[BankAccountSettlementFields.SETTLEMENT_DETAILS],
        href: ROUTES_INFO.SETTLEMENT_DETAILS,
      },
      {
        id: BankAccountSettlementFields.FIRS,
        title: BankAccountSettlementTitles[BankAccountSettlementFields.FIRS],
        href: ROUTES_INFO.FIRS,
        additionalCondition: (): ((user: User) => boolean) => shouldShowFIRCSection,
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
          () =>
          (user: User): boolean =>
            isWhatsappNotificationEnabled(user),
      },
    ],
  },
  {
    id: SectionCardDataFields.CHECKOUT_SETTINGS,
    title: 'Checkout settings',
    icon: AccountNSettingsIcons.checkout_settings,
    iconBackground: 'linear-gradient(155.9deg, #EC9B26 10.71%, #BD7A03 59.94%)',
    additionalCondition:
      () =>
      (user: User): boolean =>
        isConfigurationViewAllowed(user) || isTrustedBadgeAllowed(user),
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
          () =>
          (user: User): boolean =>
            isConfigurationViewAllowed(user) && isFlashCheckoutAllowed(user),
      },
      {
        id: CheckoutSettingsFields.SKIP_MANDATE_SUMMARY_PAGE,
        title: CheckoutSettingsTitles[CheckoutSettingsFields.SKIP_MANDATE_SUMMARY_PAGE],
        href: ROUTES_INFO.SKIP_MANDATORY_SUMMARY_PAGE,
        additionalCondition:
          () =>
          (user: User): boolean =>
            isConfigurationViewAllowed(user) && isSkipMandatorySummaryPageAllowed(user),
      },
      {
        id: CheckoutSettingsFields.TRUSTED_BADGE,
        title: CheckoutSettingsTitles[CheckoutSettingsFields.TRUSTED_BADGE],
        href: ROUTES_INFO.TRUSTED_BADGE,
        additionalCondition: (): ((user: User) => boolean) => isTrustedBadgeAllowed,
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
];
