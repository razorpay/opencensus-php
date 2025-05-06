import { QuickActionsCategory } from './types';

type MessageAnalytics = {
  title: string;
  widgetId: string;
  subWidgetId: string;
};

type MessageContent = {
  title: string;
  errorMessage?: string;
  analytics: MessageAnalytics;
};

export const messages: Record<QuickActionsCategory | 'quickActionsSection', MessageContent> = {
  quickActionsSection: {
    title: 'Quick Actions',
    errorMessage: `We couldn’t load the information due to a technical issue. Please try again or check back later.`,
    analytics: {
      title: 'Quick Actions',
      widgetId: 'one_home_quick_actions',
      subWidgetId: '',
    },
  },
  quickActionsPayments: {
    title: 'Quick Actions Payments',
    errorMessage: `We couldn’t load the payments quick actions information due to a technical issue. Please try again or check back later.`,
    analytics: {
      title: 'Quick Actions Payments',
      widgetId: 'one_home_quick_actions',
      subWidgetId: 'one_home_quick_actions_payments_quick_actions',
    },
  },
  quickActionsBanking: {
    title: 'Quick Actions Banking+',
    errorMessage: `We couldn’t load the banking quick actions information due to a technical issue. Please try again or check back later.`,
    analytics: {
      title: 'Quick Actions Banking+',
      widgetId: 'one_home_quick_actions',
      subWidgetId: 'one_home_quick_actions_banking_quick_actions',
    },
  },
  quickActionsConnectedProducts: {
    title: 'Other product lines by Razorpay',
    errorMessage: `We couldn’t the information due to a technical issue. Please try again or check back later.`,
    analytics: {
      title: 'Other product lines by Razorpay',
      widgetId: 'one_home_quick_actions',
      subWidgetId: 'one_home_quick_actions_connected_products_actions',
    },
  },
};
