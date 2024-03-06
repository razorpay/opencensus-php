export interface ZoneModalProps {
  id?: string;
  countriesUrl: string;
  isOpen: boolean;
  closeModal: () => void;
  updateZone: (zone: Zone) => Promise<void>;
  createZone: (zone: Zone) => Promise<void>;
  zone?: Zone;
  item_category_id?: string;
  mode: 'edit' | 'create';
  showNotification: (payload: Record<string, unknown>) => void;
  zoneType: 'cod' | 'shipping';
  loading: boolean;
  isZoneFetching?: boolean;
}

export interface ZoneItemProps {
  item: CountryMap;
  handleSelectedZones: (item: CountryMap, checked: boolean) => void;
  countryZone?: string;
  searchText: string;
  handleCollapse?: (code: string, index: number) => void;
  collapsed?: boolean;
  zone: State | Country | string | undefined;
  isCountry?: boolean;
}

export interface Zone {
  id?: string;
  name: string;
  type: string;
  item_category_id?: string;
  locations: Location[];
  state_count?: number | string;
  shipping_methods?: any[];
  location_count?: number;
}

export interface Location {
  id?: string;
  type: string;
  location_type: 'state' | 'country';
  zipcode?: string;
  state_code?: string;
  country_code: string;
}

export interface CountriesAPIResponse {
  status_code: number;
  success: boolean;
  data: {
    countries: Country[];
  };
}

export interface Country {
  name: string;
  code: string;
  zone_name?: any;
  states: State[];
  depth?: number;
  selected?: boolean;
}

export interface State {
  name: string;
  code: string;
  zone_name?: any;
}

export type VisibleStatus = 'all' | 'some' | 'none';

export interface CountryMap extends Omit<Country, 'states'> {
  index: number;
  depth: number;
  selected: boolean;
  total_children: number;
  total_selectable_children: number;
  parentIndex: number;
  states: State[] | StateMap;
  total_selected: number;
  visible_status: VisibleStatus;
}

export interface StateMap {
  [key: string]: {
    code: string;
    name: string;
    depth: number;
    zone_name?: string;
    parent?: string;
    parentIndex: number;
    selected: boolean;
    visible_status: VisibleStatus;
  };
}
