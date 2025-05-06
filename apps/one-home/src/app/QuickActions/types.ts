// Define specific action parameter types
interface NavigateActionParamsInternal {
  path: string;
}

interface RedirectActionParams {
  url: string;
}

export type QuickAction = {
  title: string;
  action: 'navigate';
  type: string;
  icon: string;
  icon_position: string;
  action_params: NavigateActionParamsInternal | RedirectActionParams;
};

interface Analytics {
  enabled: boolean;
}

export interface QuickActionItem {
  id: string;
  type: 'quick_action_item' | 'quick_action_sub_item' | 'more_navigation_item';
  title: string;
  description?: string;
  actions: QuickAction[];
  inputs: any[];
  components: QuickActionItem[]; // Recursive structure
  error?: { code: string; message: string };
  alias: string;
  analytics: Analytics;
  styles: any | null;
}

export interface QuickActionsResponse {
  payments: QuickActionItem;
  banking: QuickActionItem;
}

export type QuickActionsCategory =
  | 'quickActionsPayments'
  | 'quickActionsBanking'
  | 'quickActionsConnectedProducts';

export type QuickActionEntityAlias = 'payments_quick_action_item' | 'banking_quick_action_item';

export type NavigationType = 'internal_navigation' | 'external_navigation';

export type NavigateActionValue = {
  type: NavigationType;
  navigateTo: string;
};

export type PageData = {
  title?: string;
  description?: string;
  imageSrc?: string;
  actions?: any[]; // tighten if needed
};

export type NavigateSelectAction = {
  actionType: 'navigate';
  value: NavigateActionValue;
  pageData: PageData | null;
};

export type GrowthOrAccessDeniedSelectAction = {
  actionType: 'growth_page' | 'access_denied_page';
  value: GrowthOrAccessDeniedSelectAction;
  pageData: PageData | null;
};

export type ModalSelectAction = {
  actionType: 'modal';
  value: string;
  pageData: PageData | null;
};

export type FallbackSelectAction = {
  actionType: string;
  value: string | null;
  pageData: PageData | null;
};

export type SelectAction =
  | NavigateSelectAction
  | GrowthOrAccessDeniedSelectAction
  | ModalSelectAction
  | FallbackSelectAction;

export type QuickActionConnectedProductItem = {
  id: string;
  alias: string;
  title: string;
  description?: string;
  isAlwaysOverflowing?: boolean;
  selectAction: SelectAction;
};

export interface QuickActionsConnectedProductsProps {
  title: string;
  data: QuickActionConnectedProductItem[];
  error: Error;
  isMobile: boolean;
}
