const ROUTE_REG = {
  accountsettings:
    /^\/(account-settings|payment-methods|checkout-settings|notification-settings|website-app-settings|payments-and-refunds-settings|business-settings|bank-accounts-settlements|international-settings)/,
  riskAndFraud: /^\/(risk-and-fraud|risk-analytics)/,
  transactions: /^\/(payments|refunds|orders|batch-refunds|disputes|success-rate)/,
  // eslint-disable-next-line prettier/prettier
  settlements:
    /^\/(settlements|routeinstantsettlements|instantsettlement_details|instantsettlements)/,
  // eslint-disable-next-line prettier/prettier
  my_account:
    /^\/(trustedbadge|profile|credits|addfunds|referrals|website-app-details|ticket-support|team)/,
  settings: /^\/(config|webhooks|keys|applications|reminders|payment-methods)/,
  invoices: /^\/(invoices|items)/,
  route: /^\/route(\/(payments|transfers|reversals|accounts|batchuploads))?/,
  payment_links: /^\/paymentlinks(\/batchuploads)?/,
  payment_button: /^\/(paymentbuttons|subscription_buttons)/,
  payment_pages: /^\/(paymentpages)/,
  payment_handle: /^\/(payment-handle)/,
  // eslint-disable-next-line prettier/prettier
  subscriptions:
    /^\/(subscriptions(\/batchuploads)?|plans|addons|recurring_payments|tokens|authlinks|registration_links)/,
  partner: /^\/(submerchants(\/(applications|settings))?|commissions)/,
  magic_checkout: /^\/magic(\/|$)/,
  magic_konnect: /^\/magic-konnect(\/|$)/,
  optimizer: /^\/optimizer(\/(add-provider|create-rule|update-rule|rules))?/,
  smart_collect: /^\/(smartcollect|virtualaccounts)/,
  qr_codes: /^\/qr_codes(\/(payments))?/,
  offers: /^\/offers(\/(new))?/,
  stores: /^\/stores(\/(products|orders))?/,
  cash_advance: /^\/capital\/cash-advance/,
  line_of_credit: /^\/capital\/line-of-credit/,
  working_capital_loans: /^\/capital\/non-fldg-loans/,
  x_corporate_cards: /^\/capital\/corporate-cards/,
  capital_loans: /^\/capital\/loans/,
  affordability: /^\/affordability(\/(widget))?/,
  developers: /^\/developers(\/(api|webhooks))?/,
  wallet: /^\/(wallet)/,
  internationalPaymentsBtn: /^\/(international)/,
  payment_metrics: /^\/(payment-metrics)/,
  pos: /^\/pos(\/(catalog|dashboard))*/,
  gcms_programs: /^\/gcms\/programs/,
  gcms_resellers: /^\/gcms\/resellers/,
  gcms_orders: /^\/gcms\/orders/,
  gcms_funds: /^\/gcms\/funds/,
  gcms_reports: /^\/gcms\/reports/,
  reconciliations: /^\/reconciliations/,
  assisted_financing: /^\/(assisted-financing)/,
};

export const BASE_ROUTES = {
  home: '/dashboard',
  transactions: '/payments',
  settlements: '/settlements',
  settings: '/config',
  developers: '/developers/apis',
  my_account: '/profile',
  reports: '/reports',
  x_corporate_cards: '/capital/corporate-cards/',
  working_capital_loans: '/capital/non-fldg-loans/',
  checkout_rewards: '/checkout-rewards',
  offers: '/offers',
  customers: '/customers',
  optimizer: '/optimizer',
  bbps: '/bbps',
  magic_checkout: '/magic',
  magic_konnect: '/magic-konnect',
  smart_collect: '/smartcollect/virtualaccounts',
  qr_codes: '/qr_codes',
  subscriptions: '/subscriptions',
  x_payroll: '/payroll',
  x_banking: '/razorpayx',
  route: '/route/payments',
  payment_button: '/paymentbuttons',
  api_keys: '/api-keys',
  stores: '/stores/products',
  payment_pages: '/paymentpages',
  payment_links: '/paymentlinks',
  cash_advance: '/capital/cash-advance',
  line_of_credit: '/capital/line-of-credit',
  capital_loans: '/capital/loans',
  invoices: '/invoices',
  app_store: '/app-store',
  subscription_buttons: '/subscription_buttons',
  chargeAtWill: '/recurring_payments',
  partner: '/submerchants',
  accountsettings: '/account-settings',
  affordability: '/affordability/widget',
  payment_handle: '/payment-handle',
  wallet: '/wallet',
  internationalPaymentsBtn: '/payment-methods/international-payments',
  payment_metrics: '/payment-metrics',
  pos: '/pos',
  gcms_programs: '/gcms/programs',
  gcms_resellers: '/gcms/resellers',
  gcms_orders: '/gcms/orders',
  gcms_funds: '/gcms/funds',
  gcms_reports: '/gcms/reports',
  riskAndFraud: '/risk-and-fraud',
  reconciliations: '/reconciliations/dashboard/processes',
  assisted_financing: '/assisted-financing',
};

export const initializeRoutes = (location, user) => {
  const pathname = location.pathname;
  const routes = { ...BASE_ROUTES };
  if (!user.isAllowedView('configuration')) {
    routes.settings = '/webhooks';
  }
  if (user.isDeveloperConsoleWebhooksTabEnabled && !user.isDeveloperConsoleEnabled) {
    routes.developers = '/developers/webhooks';
  }
  if (user.isOrgAxis) {
    routes.my_account = '/profile';
  }
  if (ROUTE_REG.transactions.test(pathname)) {
    routes.transactions = pathname.match(ROUTE_REG.transactions)[0];
  } else if (ROUTE_REG.my_account.test(pathname)) {
    routes.my_account = pathname.match(ROUTE_REG.my_account)[0];
  } else if (ROUTE_REG.settings.test(pathname)) {
    routes.settings = pathname.match(ROUTE_REG.settings)[0];
  } else if (ROUTE_REG.invoices.test(pathname)) {
    routes.invoices = pathname.match(ROUTE_REG.invoices)[0];
  } else if (ROUTE_REG.payment_metrics.test(pathname)) {
    routes.payment_metrics = pathname.match(ROUTE_REG.payment_metrics)[0];
  } else if (ROUTE_REG.affordability.test(pathname)) {
    routes.affordability = pathname.match(ROUTE_REG.affordability)[0];
  } else if (ROUTE_REG.route.test(pathname)) {
    routes.route = pathname.match(ROUTE_REG.route)[0];
  } else if (ROUTE_REG.payment_links.test(pathname)) {
    routes.payment_links = pathname.match(ROUTE_REG.payment_links)[0];
  } else if (ROUTE_REG.payment_handle.test(pathname)) {
    routes.payment_handle = pathname.match(ROUTE_REG.payment_handle)[0];
  } else if (ROUTE_REG.payment_button.test(pathname)) {
    routes.payment_button = pathname.match(ROUTE_REG.payment_button)[0];
  } else if (ROUTE_REG.partner.test(pathname)) {
    routes.partner = pathname.match(ROUTE_REG.partner)[0];
  } else if (ROUTE_REG.subscriptions.test(pathname)) {
    routes[user.isChargeAtWillEnabled ? 'chargeAtWill' : 'subscriptions'] = pathname.match(
      ROUTE_REG.subscriptions,
    )[0];
  } else if (ROUTE_REG.magic_checkout.test(pathname)) {
    routes.magic_checkout = pathname.match(ROUTE_REG.magic_checkout)[0];
  } else if (ROUTE_REG.magic_konnect.test(pathname)) {
    routes.magic_konnect = pathname.match(ROUTE_REG.magic_konnect)[0];
  } else if (ROUTE_REG.wallet.test(pathname)) {
    routes.wallet = pathname.match(ROUTE_REG.wallet)[0];
  } else if (ROUTE_REG.internationalPaymentsBtn.test(pathname)) {
    routes.internationalPaymentsBtn = pathname.match(ROUTE_REG.internationalPaymentsBtn)[0];
  } else if (user.isRegistrationLinkBasedRole) {
    routes.chargeAtWill = 'registration_links';
  } else if (ROUTE_REG.gcms_programs.test(pathname)) {
    routes.gcms_programs = pathname.match(ROUTE_REG.gcms_programs)[0];
  } else if (ROUTE_REG.gcms_resellers.test(pathname)) {
    routes.gcms_resellers = pathname.match(ROUTE_REG.gcms_resellers)[0];
  } else if (ROUTE_REG.gcms_orders.test(pathname)) {
    routes.gcms_orders = pathname.match(ROUTE_REG.gcms_orders)[0];
  } else if (ROUTE_REG.gcms_funds.test(pathname)) {
    routes.gcms_funds = pathname.match(ROUTE_REG.gcms_funds)[0];
  } else if (ROUTE_REG.gcms_reports.test(pathname)) {
    routes.gcms_reports = pathname.match(ROUTE_REG.gcms_reports)[0];
  } else if (ROUTE_REG.riskAndFraud.test(pathname)) {
    routes.riskAndFraud = pathname.match(ROUTE_REG.riskAndFraud)[0];
  } else if (ROUTE_REG.assisted_financing.test(pathname)) {
    routes.assisted_financing = pathname.match(ROUTE_REG.assisted_financing)[0];
  }
  return routes;
};

export const getActiveTab = ({ pathname }) => {
  return (
    Object.keys(ROUTE_REG).find((each) => ROUTE_REG[each].test(pathname)) ||
    Object.keys(BASE_ROUTES).find((each) => BASE_ROUTES[each] === pathname) ||
    ''
  );
};
