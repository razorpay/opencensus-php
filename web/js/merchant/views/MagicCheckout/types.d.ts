export const PLATFORMS = {
  SHOPIFY: 'shopify',
  WOOCOMMERCE: 'woocommerce',
  NATIVE: 'native',
} as const;

type Platform = keyof typeof PLATFORMS;

type GenericRecord = Record<string, unknown>;

type User = {
  role: string;
  isMagicOrderAnalyticsCREnabled: boolean;
  isMagicCODEngineEnabled: boolean;
  isMagicPrepayCODEnabled: boolean;
  isMagicOrderAnalyticsEnabled: boolean;
  isCODOrderControlEnabled: boolean;
  isCODIntelligenceEnabled: boolean;
  isMagicRTOAnalyticsV3Enabled: boolean;
  merchant: {
    id: string;
    [key: string]: unknown;
  };
  [key: string]: unknown;
};

export interface RouteItem {
  className?: string;
  id?: string;
  path: string;
  label: string;
  tabHeading?: string;
  Component: React.ComponentType;
  condition?: (user: User, abExperiments?: GenericRecord) => boolean;
  onRCOD?: boolean;
  onRCODOnly?: boolean;
}

export type RoutesConfig = {
  [x: string]: RouteItem[];
};
