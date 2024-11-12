import {
  DropdownProps as BladeDropdownProps,
  IconComponent,
  ButtonProps,
  ActionListItemProps,
} from '@razorpay/blade/components';

export type Option = {
  title: string;
  value: string;
  leading?: ActionListItemProps['leading'];
};

export type SectionOption = {
  section: {
    name: string;
    options: Option[];
  };
};

export type Options = Array<Option | SectionOption>;

export interface DropdownProps extends Omit<BladeDropdownProps, 'children'> {
  prefixTitle?: string;
  defaultOptions?: Option[];
  options: Options;
  onChange?: (option: Option[]) => void;
  isLink?: boolean;
  isDisabled?: boolean;
  isSelectInput?: boolean;
  selectInputName?: string;
  withBottomSheet?: boolean;
  bottomSheetTitle?: string;
}

export interface ClickProps {
  name: string;
  value?: boolean;
}

export interface DropdownCommonProps {
  icon: IconComponent;
  iconPosition: ButtonProps['iconPosition'];
  onClick: () => void;
  'data-testid': string;
  isDisabled: boolean;
}

export interface DropdownTarget {
  isLink: boolean;
  isSelectInput: boolean;
  selectInputName: string;
  dropdownCommonProps: DropdownCommonProps;
  dropdownTitle: string;
  defaultOptions: Option[];
}

export interface DropdownContent {
  isMultipleSelection: boolean;
  selectedOptions: Option[];
  tempSelectedOptions: Option[];
  onOptionClick: (clickedOption: Option) => (value: ClickProps) => void;
  onClear: () => void;
  onApply: () => void;
  options: Options;
  isWithBottomSheet: boolean;
  bottomSheetTitle: string;
  isDropdownOpen: boolean;
}
