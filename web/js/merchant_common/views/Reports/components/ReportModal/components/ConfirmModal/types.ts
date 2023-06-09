import { Dispatch, ReactElement, SetStateAction } from 'react';
import { IconComponent, IconProps } from '@razorpay/blade/components';
import { DashboardType } from 'merchant_common/views/Reports/types';

export enum Feedback {
  negative = 'negative',
  information = 'information',
  neutral = 'neutral',
  positive = 'positive',
  notice = 'notice',
}

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
    intent: keyof typeof Feedback;
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
