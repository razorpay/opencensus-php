import { Theme } from '@razorpay/blade/components';
import { SessionReducerState } from 'common/typings';
import { CustomConfigType } from 'merchant_common/views/Reports/types';
import { NON_OWNED_CONFIG_TYPE } from 'merchant_common/views/Reports/constants';
import { I18ContextStateType } from 'common/i18/types';

export const reportsTheme = (theme: Theme) => {
  // colors
  const bladeColors = theme
    ? {
        FIELD_INPUT_TEXT_COLOR: theme.colors.surface.text.gray.normal,
        FIELD_INPUT_PLACEHOLDER_COLOR: theme.colors.surface.text.gray.disabled,
        FIELD_BORDER_ERROR_COLOR: theme.colors.feedback.border.negative.intense,
        FIELD_BORDER_DEFAULT_COLOR: theme.colors.surface.border.gray.muted,
        FIELD_FOCUS_COLOR_L3: theme.colors.surface.background.primary.intense,
        FIELD_FOCUS_COLOR_L2: theme.colors.surface.background.primary.subtle,
        FIELD_FOCUS_COLOR_L1: theme.colors.surface.background.primary.subtle,
        FIELD_WRAPPER_BG_COLOR: theme.colors.surface.background.gray.moderate,
        TABLE_EVEN_BG_COLOR: theme.colors.surface.background.gray.moderate,
        HOVER_BG_COLOR: theme.colors.surface.background.gray.moderate,
        DISABLED_BG_COLOR: theme.colors.surface.background.gray.subtle,
        MARGIN_DIVIDER: theme.spacing[4],
        GRAY_LEVEL_1: theme.colors.surface.background.gray.subtle,
        GRAY_LEVEL_2: theme.colors.surface.border.gray.muted,
        GRAY_LEVEL_3: theme.colors.interactive.background.gray.highlighted,
        NEGATIVE_BG: theme.colors.feedback.background.negative.subtle,
        NEGATIVE_BORDER: theme.colors.feedback.border.negative.intense,
      }
    : {};

  const customColors = {
    FIELD_BG_COLOR: 'hsla(216,15%,54%,0.09)',
    MODAL_BG_MASK_COLOR: 'rgba(17, 30, 60, 0.6)',
    HOVER_BG_COLOR_L2: '#79879C17',
    HOVER_BG_COLOR_L3: '#79879C2E',
  };

  // sizing
  const bladeSizing = theme ? {} : {};

  const customSizing = {
    FIELD_PADDING: '8px 12px',
    FIELD_BORDER_WIDTH: '0.5px',
    FIELD_BORDER_RADIUS: '2px',
    FIELD_WITHOUT_LABEL_MARGIN: '0px',
    FIELD_WITH_LABEL_MARGIN: '5px 0px',
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

export const reportConfigType = (i18?: I18ContextStateType) =>
  i18?.isConfigTagEnabled
    ? {
        // hiding by type
        transactions: i18.isConfigTagEnabled('reports.transactions'),
        refunds: i18.isConfigTagEnabled('reports.refunds'),
        rawsql: i18.isConfigTagEnabled('reports.raw_sql'),
        settlement_ondemands: i18.isConfigTagEnabled('reports.on_demand_settlements'),
        qr_code: i18.isConfigTagEnabled('reports.qr_codes'),
        subscriptions: i18.isConfigTagEnabled('reports.subscriptions'),
        paymentlinksv2: i18.isConfigTagEnabled('reports.payment_links'),
        contacts: i18.isConfigTagEnabled('reports.contacts'),
        payment_links: i18.isConfigTagEnabled('reports.payment_links'),
        custom: i18.isConfigTagEnabled('reports.custom'),
        scrooge_refunds: i18.isConfigTagEnabled('reports.refunds'),
        // hiding by name
        'Payments Report With Offers': i18.isConfigTagEnabled('reports.payment_report_with_offers'),
        'QR Code Report with Pay_Id': i18.isConfigTagEnabled('reports.qr_code_report_with_pay_id'),
        'Payment Button Report': i18.isConfigTagEnabled('reports.payment_btn_report'),
        'Payment page': i18.isConfigTagEnabled('reports.payment_pages'),
      }
    : {};

export const CUSTOM_CONFIG_MAP = {
  monthlyInvoice: {
    name: 'Monthly Invoice',
    type: NON_OWNED_CONFIG_TYPE,
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
    type: NON_OWNED_CONFIG_TYPE,
    id: 'dsp_report',
    description: '',
  },

  broking: {
    name: 'Broking Report',
    type: NON_OWNED_CONFIG_TYPE,
    id: 'broking',
    description: '',
  },

  rpp_report: {
    name: 'e-Mitra Report',
    type: NON_OWNED_CONFIG_TYPE,
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

  if (
    Boolean(user?.isOrgAllowedFunctionality) &&
    user.isOrgAllowedFunctionality('monthlyInvoice')
  ) {
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
