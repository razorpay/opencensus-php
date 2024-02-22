export interface Option {
  value: string;
  label: string;
  disabled?: boolean;
}

export interface RadioButtonGroupProps {
  testID?: string;
  options: Option[];
  isDisabled?: boolean;
  selectedOption?: string;
  onChange: (value: string) => void;
}
