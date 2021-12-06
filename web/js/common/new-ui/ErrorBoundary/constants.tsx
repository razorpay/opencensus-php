export enum Ranks {
  P0 = 'P0',
  P1 = 'P1',
  P2 = 'P2',
  P3 = 'P3',
}
export enum Teams {
  PLATFORM = 'Platform',
  CAPITAL = 'Capital',
  CHECKOUT = 'Checkout',
  APPS = 'Apps',
  GROWTH = 'Growth',
  CARE = 'Care',
  PARTNERSHIP = 'Partnership',
  RISK = 'Risk',
  PG_DASHBOARD = 'PG Dashboard',
  BANKING = 'Banking',
  TERMINAL = 'Terminal',
  COMMON = 'Common',
  ROUTING = 'Routing',
}

// Maintain this list in ascending order
// Make sure to contribute on this list when adding new routes
export const RoutesConfig = {
  '/': Teams.COMMON,
  '/dashboard': Teams.COMMON,

  '/addfunds': Teams.PG_DASHBOARD,
  '/applications': Teams.PG_DASHBOARD,
  '/bbps': Teams.PG_DASHBOARD,

  '/capital': Teams.CAPITAL,
  '/capital/cash-advance': Teams.CAPITAL,
  '/capital/cash-advance/repayments-schedule': Teams.CAPITAL,
  '/capital/corporate-cards': Teams.CAPITAL,
  '/capital/non-fldg-loans': Teams.CAPITAL,

  '/checkout-rewards': Teams.CHECKOUT,

  '/config': Teams.PG_DASHBOARD,
  '/credits': Teams.PG_DASHBOARD,
  '/customers': Teams.PG_DASHBOARD,
  '/disputes': Teams.PG_DASHBOARD,
  '/instantsettlements': Teams.PG_DASHBOARD,

  '/invoices': Teams.PG_DASHBOARD,
  '/invoices/new': Teams.PG_DASHBOARD,

  '/items': Teams.PG_DASHBOARD,
  '/keys': Teams.PG_DASHBOARD,

  '/offers': Teams.CHECKOUT,

  '/optimizer': Teams.ROUTING,
  '/optimizer/add-provider': Teams.ROUTING,
  '/optimizer/create-rule': Teams.ROUTING,
  '/optimizer/update-rule': Teams.ROUTING,
  '/optimizer/rules': Teams.ROUTING,

  '/orders': Teams.PG_DASHBOARD,

  '/partners': Teams.PARTNERSHIP,
  '/partners/applications': Teams.PARTNERSHIP,
  '/partners/applications/new': Teams.PARTNERSHIP,
  '/partners/earnings': Teams.PARTNERSHIP,
  '/partners/earnings/daily': Teams.PARTNERSHIP,
  '/partners/earnings/invoices': Teams.PARTNERSHIP,
  '/partners/earnings/transactional': Teams.PARTNERSHIP,
  '/partners/reports': Teams.PARTNERSHIP,
  '/partners/settings': Teams.PARTNERSHIP,
  '/partners/submerchants': Teams.PARTNERSHIP,
  '/partners/subventions': Teams.PARTNERSHIP,
  '/partners/subventions/daily': Teams.PARTNERSHIP,
  '/partners/subventions/transactional': Teams.PARTNERSHIP,

  '/payment-methods': Teams.TERMINAL,

  '/paymentbuttons': Teams.APPS,
  '/paymentlinks': Teams.APPS,
  '/paymentlinks/batchuploads': Teams.APPS,
  '/paymentpages': Teams.APPS,

  '/payments': Teams.PG_DASHBOARD,
  '/payments/batchuploads': Teams.PG_DASHBOARD,
  '/paypal_onboard_redirect': Teams.PG_DASHBOARD,

  '/plans': Teams.APPS,
  '/plans/new': Teams.APPS,

  '/profile': Teams.PG_DASHBOARD,

  '/qr_codes': Teams.APPS,
  '/qr_codes/new': Teams.APPS,
  '/qr_codes/payments': Teams.APPS,

  '/recurring_payments': Teams.APPS,

  '/referrals': Teams.PG_DASHBOARD,

  '/refunds': Teams.PG_DASHBOARD,
  '/refunds/batchupload': Teams.PG_DASHBOARD,
  '/refunds/batchuploads': Teams.PG_DASHBOARD,

  '/registration_links': Teams.APPS,

  '/reminders': Teams.PG_DASHBOARD,
  '/reports': Teams.PG_DASHBOARD,
  '/reversals': Teams.PG_DASHBOARD,
  '/reversals/batchreversals': Teams.PG_DASHBOARD,

  '/route': Teams.APPS,
  '/route/accounts': Teams.APPS,
  '/route/batchuploads': Teams.APPS,
  '/route/payments': Teams.APPS,
  '/route/reversals': Teams.APPS,
  '/route/transfers': Teams.APPS,

  '/settlements': Teams.PG_DASHBOARD,

  '/smartcollect/payments': Teams.APPS,
  '/smartcollect': Teams.APPS,
  '/virtualaccounts': Teams.APPS,

  '/stores': Teams.APPS,
  '/stores/orders': Teams.APPS,
  '/stores/products': Teams.APPS,

  '/subscription_buttons': Teams.APPS,
  '/subscriptions': Teams.APPS,
  '/subscriptions/batchuploads': Teams.APPS,
  '/subscriptions/settings': Teams.APPS,

  '/super-checkout': Teams.CHECKOUT,

  '/team': Teams.PG_DASHBOARD,

  '/ticket-support': Teams.CARE,
  '/ticket-support/tickets': Teams.CARE,

  '/tokens': Teams.APPS,

  '/transfers': Teams.PG_DASHBOARD,

  '/trustedbadge': Teams.CHECKOUT,

  '/webhooks': Teams.PG_DASHBOARD,
};
