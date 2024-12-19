export interface NavigationData {
  default_item: boolean;
  icon: string;
}

export interface ActionParams {
  path?: string;
  url?: string;
}

export interface Action {
  title: string;
  action: string;
  type: string;
  icon?: string;
  icon_position?: string;
  action_params: ActionParams;
  properties?: {
    variant: string;
  };
}

export interface Analytics {
  enabled: boolean;
}

export interface ComponentInput {
  type: string;
  values?: string[];
  default_value?: string;
  name?: string;
}

export interface Component {
  id: string;
  type: string;
  title?: string;
  background_img?: string;
  description?: string;
  actions?: Action[];
  inputs?: ComponentInput[];
  components?: Component[];
  alias?: string;
  analytics?: Analytics;
  styles?: Record<string, any> | null;
  data?: {
    navigation_data?: NavigationData;
    value?: string;
    value_type?: string;
    currency?: string;
    change?: number;
    sub_text?: string;
    chart_data?: ChartData;
  };
}

export interface ChartData {
  type: string;
  labels?: string[];
  schema?: {
    x: Axis;
    y: Axis;
  };
  data?: DataPoint[];
}

export interface Axis {
  type: string;
  unit?: string;
}

export interface DataPoint {
  label: string;
  points?: Point[];
}

export interface Point {
  x: string;
  y: number;
}

export interface OneNavigationResponse {
  id: string;
  type: string;
  title: string;
  actions: Action[];
  inputs: ComponentInput[];
  components: Component[];
  data: {
    navigation_data: {
      brand_logo: string;
    };
  };
  alias: string;
  analytics: Analytics;
  styles: Record<string, any> | null;
}

export interface ListItem {
  id: string;
  isActive: boolean;
  icon: string | null;
  trailing: string | null;
  title: string;
  description: string;
  isAlwaysOverflowing: boolean | null;
  datum: Component;
}

export type ProductAlias =
  | 'payments_top_navigation_item'
  | 'partnership_top_navigation_item'
  | 'banking_top_navigation_item'
  | 'payroll_top_navigation_item'
  | 'billme_top_navigation_item'
  | 'rize_top_navigation_item';
