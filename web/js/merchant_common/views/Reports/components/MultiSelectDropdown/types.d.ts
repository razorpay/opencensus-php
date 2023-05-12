import { NecessityIndicatorType } from 'merchant_common/views/Reports/components/types';

export interface OptionPropsType<ItemType, AllowMultiple> {
  disabled?: boolean;
  item: ItemType;
  itemHeight?: number;
  labelKey?: string;
  onClick: (x: ItemType) => void;
  renderCustomOption?: (x: ItemType) => JSX.Element;
  value: SingleOrMultipleItem<ItemType, AllowMultiple> | undefined;
}

export type SingleOrMultipleItem<ItemType, AllowMultiple> = AllowMultiple extends true
  ? ItemType[]
  : ItemType;

export type VirtualizedTypes<T> = T extends true
  ? {
      /**
       * Height of the option, dynamic height for virtual list is still in TODO.
       */
      itemHeight: number;
      /**
       * Max visible option to show in dropdown.
       */
      maxVisibleOption?: number;
    }
  : Record<undefined, undefined>;

export type CheckItemType<T> = T extends string
  ? Record<undefined, undefined>
  : {
      labelKey: string;
    };

export type DefaultDropdownProps<ItemType, AllowMultiple, Virtualized> = {
  tabIndex?: number;
  /**
   * To enable multiple selection
   */
  shouldAllowMultiple?: AllowMultiple;
  ariaLabelBy?: string;
  /**
   * True by default, when false dropdown list will not close on option selection
   */
  shouldCloseDropdownOnSelect?: boolean;
  defaultValue?: SingleOrMultipleItem<ItemType, AllowMultiple>;
  helpText?: string;
  errorText?: string;
  /**
   * In TODO
   */
  disabled?: boolean;
  label?: string;
  isLoading?: boolean;
  onChange: (x: SingleOrMultipleItem<ItemType, AllowMultiple>) => void;
  /**
   * A callback fired on input search.
   */
  onSearchInput?: (searchedFor: string) => void;
  options: ItemType[];
  placeHolder?: string;
  /**
   * Custom component to render in dropdown.
   */
  renderCustomOption?: (x: ItemType) => JSX.Element;
  /**
   * When true, dropdown will have a search input field to filter the options.
   */
  isSearchable?: boolean;
  /**
   * Function when called, returns the validation state
   */
  validate?: () => boolean;
  value: SingleOrMultipleItem<ItemType, AllowMultiple> | undefined;
  /**
   * To enable virtual list, powered by "rc-virtual-list".
   */
  isVirtualized?: Virtualized;
  necessityIndicator?: NecessityIndicatorType;
};

export type BaseDropdownPropsType<T1, T2, T3> = DefaultDropdownProps<T1, T2, T3> &
  VirtualizedTypes<T3> &
  CheckItemType<T1>;
