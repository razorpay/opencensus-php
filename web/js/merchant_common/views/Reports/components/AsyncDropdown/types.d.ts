import {
  CheckItemType,
  NecessityIndicatorType,
  VirtualizedTypes,
} from 'merchant_common/views/Reports/components/types';

type DefaultAsyncDropdownProps<ItemType, AllowMultiple, Virtualized> = {
  /**
   * To enable multiple selection
   */
  shouldAllowMultiple?: AllowMultiple;
  ariaLabelBy?: string;
  /**
   * True by default, when false dropdown list will not close on option selection
   */
  shouldCloseDropdownOnSelect?: boolean;
  defaultValue?: SingleOrMulipleItem<ItemType, AllowMultiple>;
  helpText?: string;
  errorText?: string;
  /**
   * In TODO
   */
  disabled?: boolean;
  label?: string;
  isLoading?: boolean;
  onChange: (x: SingleOrMulipleItem<ItemType, AllowMultiple>) => void;
  placeHolder?: string;
  /**
   * Custom component to render in dropdown.
   */
  renderCustomOption?: (x: ItemType) => JSX.Element;
  /**
   * Function when called, returns the validation state
   */
  validate?: () => boolean;
  value: SingleOrMulipleItem<ItemType, AllowMultiple> | undefined;
  /**
   * To enable virtual list, powered by "rc-virtual-list".
   */
  isVirtualized?: Virtualized;
  /**
   * Callback fired if promise is rejected.
   */
  onError?: (x) => void;
  /**
   * Callback fired if promise is resolved.
   */
  onSuccess?: (x) => void;
  /**
   * A fn used to parse the resolved data.
   */
  parseData: (x) => SingleOrMulipleItem<ItemType, AllowMultiple>;
  /**
   * A promise to be called when user types in the search input.
   */
  promise: Promise;
  /**
   * Debounce interval for the type ahead api calls.
   */
  debounceInterval?: number;
  necessityIndicator?: NecessityIndicatorType;
};

export type AsyncDropdownPropsType<T1, T2, T3> = DefaultAsyncDropdownProps<T1, T2, T3> &
  VirtualizedTypes<T3> &
  CheckItemType<T1>;
