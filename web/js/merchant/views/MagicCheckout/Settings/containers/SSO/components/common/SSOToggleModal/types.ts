export type ModalState = 'loading' | 'success' | 'error';

export interface SSOToggleModalProps {
  isOpen: boolean;
  onDismiss: () => void;
}

export interface SuccessContentProps {
  activationLink?: string;
}

export interface ErrorContentProps {
  errorMsg?: string;
}
