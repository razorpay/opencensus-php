import { Option } from '../../types';

export interface FooterActionsProps {
  tempSelectedOptions: Option[];
  onClear: () => void;
  onApply: () => void;
}
