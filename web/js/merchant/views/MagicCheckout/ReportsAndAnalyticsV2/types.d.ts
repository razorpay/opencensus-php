import { User } from 'merchant/views/MagicCheckout/types';

export type Layout = {
  chart: string;
  width: string;
};

type BaseTab = {
  label: string;
  layout: Layout[];
  condition?: (user: User, dashboardView?: string) => boolean;
  isCharts: boolean;
  Component: React.ComponentType<unknown>;
};

export type OverviewTab = Omit<BaseTab, 'Component'>;

export type ConversionTab = Omit<BaseTab, 'Component'>;

export type ReportsTab = Omit<BaseTab, 'layout'>;

export type ActiveTab = OverviewTab | ConversionTab | ReportsTab;

export type Tabs = {
  OVERVIEW: OverviewTab;
  CONVERSION: ConversionTab;
  REPORTS: ReportsTab;
};

type SessionState = {
  user: User;
  [x: string]: unknown;
};

type MagicRTOAnalyticsState = {
  loading: boolean;
  startTime: number;
  endTime: number;
  [x: string]: unknown;
};

type MagicCheckoutState = {
  cod_order_control: boolean;
  one_cc_prepay_cod_conversion: boolean;
  [x: string]: unknown;
};

export type AppState = {
  session: SessionState;
  magicRTOAnalytics: MagicRTOAnalyticsState;
  magicCheckout: MagicCheckoutState;
  [x: string]: unknown;
};
