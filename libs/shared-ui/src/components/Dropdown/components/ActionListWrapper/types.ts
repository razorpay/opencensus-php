import { ClickProps, Option, Options } from '../../types';
export interface ActionListWrapperProps {
  isMultipleSelection: boolean;
  selectedOptions: Option[];
  tempSelectedOptions: Option[];
  onOptionClick: (clickedOption: Option) => (value: ClickProps) => void;
  options: Options;
}
