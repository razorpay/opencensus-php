import { Theme } from '@razorpay/blade/components';
import { SessionReducerState } from 'common/typings';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import { CustomConfigType } from 'merchant_common/views/Reports/types';

export const reportsTheme = (theme: Theme) => {
  // colors
  const bladeColors = theme
    ? {
        FIELD_INPUT_TEXT_COLOR: theme.colors.surface.text.normal.lowContrast,
        FIELD_INPUT_PLACEHOLDER_COLOR: theme.colors.surface.text.placeholder.lowContrast,
        FIELD_BORDER_ERROR_COLOR: theme.colors.feedback.border.negative.highContrast,
        FIELD_BORDER_DEFAULT_COLOR: theme.colors.surface.border.normal.lowContrast,
        FIELD_FOCUS_COLOR_L3: theme.colors.brand.primary['500'],
        FIELD_FOCUS_COLOR_L2: theme.colors.brand.primary['400'],
        FIELD_FOCUS_COLOR_L1: theme.colors.brand.primary['300'],
        FIELD_WRAPPER_BG_COLOR: theme.colors.surface.background.level3.lowContrast,
        TABLE_EVEN_BG_COLOR: theme.colors.surface.background.level3.lowContrast,
        HOVER_BG_COLOR: theme.colors.surface.background.level3.lowContrast,
        DISABLED_BG_COLOR: theme.colors.surface.background.level1.lowContrast,
        MARGIN_DIVIDER: theme.spacing[4],
      }
    : {};

  const customColors = {
    FIELD_BG_COLOR: '#ffffff',
    MODAL_BG_MASK_COLOR: 'rgba(17, 30, 60, 0.6)',
  };

  // sizing
  const bladeSizing = theme ? {} : {};

  const customSizing = {
    FIELD_PADDING: '8px 12px',
    FIELD_BORDER_WIDTH: '0.5px',
    FIELD_BORDER_RADIUS: '4px',
    FIELD_WITHOUT_LABEL_MARGIN: '0',
    FIELD_WITH_LABEL_MARGIN: '5px 0',
    FIELD_SHADOW_BLUR_RADIUS: '20px',
    FIELD_SHADOW: 'rgba(0,0,0,0.1)',
  };

  return {
    ...bladeColors,
    ...customColors,
    ...bladeSizing,
    ...customSizing,
  };
};

export const REPORT_CONFIG_TYPE = {
  // hiding by type
  transactions: HIDDEN_INTERNATIONAL_FEATURES_TAGS.Transactions,
  refunds: HIDDEN_INTERNATIONAL_FEATURES_TAGS.Refunds,
  rawsql: HIDDEN_INTERNATIONAL_FEATURES_TAGS.RawSQL,
  settlement_ondemands: HIDDEN_INTERNATIONAL_FEATURES_TAGS.OnDemandSettlements,
  qr_code: HIDDEN_INTERNATIONAL_FEATURES_TAGS.QrCodes,
  subscriptions: HIDDEN_INTERNATIONAL_FEATURES_TAGS.Subscriptions,
  paymentlinksv2: HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentLinks,
  contacts: HIDDEN_INTERNATIONAL_FEATURES_TAGS.Contacts,
  payment_links: HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentLinks,
  custom: HIDDEN_INTERNATIONAL_FEATURES_TAGS.Custom,
  scrooge_refunds: HIDDEN_INTERNATIONAL_FEATURES_TAGS.Refunds,
  // hiding by name
  'Payments Report With Offers': HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentReportWithOffers,
  'QR Code Report with Pay_Id': HIDDEN_INTERNATIONAL_FEATURES_TAGS.QRCodeReportWithPayID,
  'Payment Button Report': HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentBtnReport,
  'Payment page': HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentPages,
};

export const CUSTOM_CONFIG_MAP = {
  monthlyInvoice: {
    name: 'Monthly Invoice',
    type: 'custom_non_owned',
    id: 'invoice',
    description: 'Download your monthly invoice in one single step.',
    helpInfo: {
      info: 'Learn more about this report here,',
      link: {
        label: 'GST Changes',
        href: 'https://razorpay.com/docs/announcements/gst-changes',
      },
    },
  },

  dsp_report: {
    name: 'DSP Transaction Report',
    type: 'custom_non_owned',
    id: 'dsp_report',
    description: '',
  },

  broking: {
    name: 'Broking Report',
    type: 'custom_non_owned',
    id: 'broking',
    description: '',
  },

  rpp_report: {
    name: 'e-Mitra Report',
    type: 'custom_non_owned',
    id: 'rpp_report',
    description: '',
  },
};

export const isCustomConfigsDisabledForOrg = (orgId: string) => {
  if (!orgId) return true;

  const blackListedOrg = [
    // Curlec
    'KjWRtYXwpK6VfK',
  ];
  return blackListedOrg.findIndex((id) => orgId.includes(id)) != -1;
};

export const getCustomConfigs = (session?: SessionReducerState): CustomConfigType[] => {
  // base validation
  if (!session || !session.user) return [];
  const { user, org } = session;

  if (isCustomConfigsDisabledForOrg(org?.id as string)) return [];

  const customConfigs: any = [];

  if (user.isOrgAllowedFunctionality('monthlyInvoice')) {
    customConfigs.push(CUSTOM_CONFIG_MAP.monthlyInvoice);
  } else if (user.findTag('borking_report')) {
    customConfigs.push(CUSTOM_CONFIG_MAP.broking);
  } else if (user.findTag('rpp_report')) {
    customConfigs.push(CUSTOM_CONFIG_MAP.rpp_report);
  } else if (user.findTag('dsp_report')) {
    customConfigs.push(CUSTOM_CONFIG_MAP.dsp_report);
  }

  return customConfigs;
};

export const MARKET_PLACE_CONFIG_TYPES = ['transactions', 'payments', 'refunds', 'settlements'];
