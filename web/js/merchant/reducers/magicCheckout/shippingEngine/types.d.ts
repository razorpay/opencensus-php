export interface ShippingSummaryAPIResponse {
  shipping_profiles: ShippingProfile[];
}

export interface ShippingProfile {
  name: string;
  zones?: Zone[];
  is_default?: boolean;
  id?: string;
  item_count?: number;
}

export interface Zone {
  id: string;
  name: string;
  shipping_methods: ShippingMethod[];
}

export interface ShippingMethod {
  name: string;
  [key: string]: any;
}

export interface ItemCategory {
  id: string;
  default: boolean;
  name: string;
}

export type DefaultProfile = {
  name?: string;
};

export interface ShippingEngineStore {
  isLoading: {
    zones: boolean;
    item_categories: boolean;
    shipping_methods: boolean;
    summary: boolean;
  };
  selected_profile?: string;
  validations: {
    zones: boolean;
    item_categories: boolean;
    shipping_methods: boolean;
  };
  shipping_profiles: {
    [key: string]: ShippingProfile;
  };
  default_profile: DefaultProfile | null;
}
