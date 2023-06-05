import { ReactElement } from 'react';
import { IconComponent, IconProps, Feedback } from '@razorpay/blade/components';
import { DashboardType } from 'merchant_common/views/Reports/types';

export interface ConfirmModalConfigType {
  title: string;
  desc: string;
  confirmBtn: {
    onClick: ({
      closeModal,
      setLoading,
    }: {
      setLoading: Dispatch<SetStateAction<boolean>>;
      closeModal: () => void;
    }) => void;
    icon: ((x: IconProps) => ReactElement) | IconComponent;
    label: string;
  };
  alert: {
    intent: Feedback;
    description: string;
  };
}

export interface ConfirmModalParams {
  modalConfig?: ConfirmModalConfigType;
}

export interface ConfirmModalProps {
  params?: ConfirmModalParams;
  onClose: () => void;
  dashboardType: DashboardType;
}
