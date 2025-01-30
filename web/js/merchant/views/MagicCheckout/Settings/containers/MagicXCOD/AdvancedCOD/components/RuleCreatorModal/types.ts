import type { Rule as ACODRule } from 'merchant/reducers/magicCheckout/magicxACODRules/types';

// hooks
export type UseRCMState = () => {
  title: string;
  ruleSizeIndicatorColor: 'information' | 'notice' | 'negative';
  acodRule: Omit<ACODRule, 'id'> & { id?: string };
  isModalOpen: boolean;
  isProcessingRequest: boolean;
};

export type UseRCMHandlers = () => {
  handleDismiss: () => void;
  handleSubmit: () => void;
  handleMetaPropChange: (prop: 'name' | 'description', value: string) => void;
};
