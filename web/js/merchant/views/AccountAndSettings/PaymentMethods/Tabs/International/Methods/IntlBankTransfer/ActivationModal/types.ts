import { ModalProps } from '@razorpay/blade/components';

export type ActivationModalProps = {
  isOpen: ModalProps['isOpen'];
  onDismiss: (triggerActivation?: boolean) => void;
  purposeCode?: string | null;
  purposeCodeDesc?: string | null;
  iecCode?: string | null;
  promoterPanName?: string | null;
  isEddVerified?: boolean;
  step?: 1 | 2 | 3 | 4;
  hideSteps?: number[];
};
