export interface FilterOption {
  key: string;
  value: string;
}

export interface DateFilterProps {
  options: FilterOption[];
  selected: string;
  onChange: (selectedKey: string) => void;
  isDisabled: boolean;
}
