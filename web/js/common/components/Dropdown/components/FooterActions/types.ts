import { Option } from '@libs/web-nexus/common/components/Dropdown/types';

export interface FooterActionsProps {
  tempSelectedOptions: Option[];
  onClear: () => void;
  onApply: () => void;
}
