export const PLATFORMS = {
  SHOPIFY: 'shopify',
  WOOCOMMERCE: 'woocommerce',
  NATIVE: 'native',
} as const;

type Platform = keyof typeof PLATFORMS;

type GenericRecord = Record<string, unknown>;

export interface RouteItem {
  className?: string;
  id?: string;
  path: string;
  label: string;
  tabHeading?: string;
  Component: React.ComponentType;
  condition?: (user?: GenericRecord, abExperiments?: GenericRecord) => boolean;
  onRCOD?: boolean;
  onRCODOnly?: boolean;
}

export type RoutesConfig = {
  [x: string]: RouteItem[];
};
