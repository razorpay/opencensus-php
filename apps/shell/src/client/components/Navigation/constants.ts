export const isOneHomeDevSetup = true;

export const PRODUCT_ALIAS_MAP = {
  PAYMENTS: 'payments_top_navigation_item',
  BANKING: 'banking_top_navigation_item',
  PAYROLL: 'payroll_top_navigation_item',
  RIZE: 'rize_top_navigation_item',
  PARTNERS: 'partners_top_navigation_item',
  HOME: 'home_top_navigation_item',
} as const;

export const PRODUCT_PATH_MAP = {
  [PRODUCT_ALIAS_MAP.BANKING]: '/banking/*',
  [PRODUCT_ALIAS_MAP.PARTNERS]: '/partners/*',
  [PRODUCT_ALIAS_MAP.HOME]: '/home',
};

export const PRODUCT_PATH_MAP_FOR_INTERNAL_NAVIGATION = {
  [PRODUCT_ALIAS_MAP.BANKING]: '/banking',
  [PRODUCT_ALIAS_MAP.PAYMENTS]: '/dashboard',
};
