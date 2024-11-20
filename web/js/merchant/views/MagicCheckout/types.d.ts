import { PLATFORMS } from 'merchant/views/MagicCheckout/constants';

type Platform = (typeof PLATFORMS)[keyof typeof PLATFORMS];

export type GenericRecord = Record<string, unknown>;

export type User = {
  role: string;
  isMagicOrderAnalyticsCREnabled: boolean;
  isMagicCODEngineEnabled: boolean;
  isMagicPrepayCODEnabled: boolean;
  isMagicOrderAnalyticsEnabled: boolean;
  isCODOrderControlEnabled: boolean;
  isCODIntelligenceEnabled: boolean;
  isMagicRTOAnalyticsV3Enabled: boolean;
  isMagicShopifyOrderEditEnabled: boolean;
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
  condition?: (user: User, abExperiments?: GenericRecord, platform?: Platform) => boolean;
  onRCOD?: boolean;
  onRCODOnly?: boolean;
  renderNavItemTag?: () => JSX.Element;
}

export type PlatformSpecificRoutes = {
  [key in Platform]?: RouteItem[];
};
