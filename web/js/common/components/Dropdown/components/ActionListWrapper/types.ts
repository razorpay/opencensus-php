import { ClickProps, Option, Options } from 'common/components/Dropdown/types';
export interface ActionListWrapperProps {
  isMultipleSelection: boolean;
  selectedOptions: Option[];
  tempSelectedOptions: Option[];
  onOptionClick: (clickedOption: Option) => (value: ClickProps) => void;
  options: Options;
}
