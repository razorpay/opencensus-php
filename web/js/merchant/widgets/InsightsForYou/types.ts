import { DateRangeInput, DateRangeValues } from 'merchant/widgets/common/Select/types';

export interface StoreHierarchyItem {
  store_id?: string;
  name: string;
  type: string;
  parent_group_id: string;
  group_id: string;
}

export interface MerchantStoreHierarchy {
  store_hierarchy: StoreHierarchyItem[];
}

export interface ComponentInput {
  default_value?: string;
  values?: string[];
  type?: string;
}

export interface ButtonWithIconInput {
  type: string;
  name: string;
  values: string[];
  data?: {
    components: Array<ComponentDataType>;
  };
}

export interface InsightsForYouWidgetProps {
  id: string;
  type: string;
  title: string;
  background_img?: string;
  inputs: Array<DateRangeInput | ButtonWithIconInput>;
  components: Array<ComponentDataType>;
}

export type ComponentDataType = {
  id: string;
  title: string;
  type: string;
  inputs?: ComponentInput[];
  components?: ComponentDataType[];
  data?: {
    merchant_store_hierarchy?: MerchantStoreHierarchy;
    [key: string]: any;
  };
};

export interface AllFiltersModalProps {
  isOpen: boolean;
  onClose: () => void;
  input?: any;
  date?: any;
  variables?: any;
  screen?: string;
  widgetId?: string;
  title?: string;
  isMobile?: boolean;
  isDatePickerEnabled?: boolean;
  paymentSource: string;
  storeIds: string[];
  renderInput: Function;
  componentWidget?: any[];
  modalTitle: string;
  onFilterApply: ({ stores, source }: { stores: string[]; source: string }) => void;
  handleDateChange: (value: Array<DateRangeValues>, custom_range: [number, number]) => void;
}

export type ComponentWidgetItem = {
  id: string;
  title: string;
  type: string;
  inputs?: ComponentInput[];
};

export type FiltersWidgetProps = {
  component: ComponentWidgetItem[];
  sourceChannel?: string;
  storeId: string[];
};
