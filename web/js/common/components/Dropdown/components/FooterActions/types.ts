import { Option } from 'common/components/Dropdown/types';

export interface FooterActionsProps {
  tempSelectedOptions: Option[];
  onClear: () => void;
  onApply: () => void;
}
