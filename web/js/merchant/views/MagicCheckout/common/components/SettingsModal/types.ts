export interface SettingsModalProps {
  header: string;
  isOpen: boolean;
  handleDismiss: () => void;
  variant: 'zone' | 'category';
  searchPlaceholder?: string;
  searchFn?: (text: string) => void;
  confirmAction: (entityName: string) => void;
  entityName?: string;
  itemClassName?: string;
  disableConfirmButton: boolean;
  isLoading: boolean;
  children: React.ReactNode;
}
