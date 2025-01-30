import { api } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/api';

import type { UseFormActions } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/context/types';

export const useFormActions: UseFormActions = ({
  storeActions,
  setIsModalOpen,
  setIsProcessingRequest,
  notify,
  merchantId,
}) => {
  const formActions = {
    createRule: async (rule) => {
      setIsProcessingRequest(true);

      await api
        .createRule(rule, merchantId)
        .then((res) => {
          if (res.success && res.data?.id) {
            storeActions.addRule(res.data);
            setIsModalOpen(false);
            notify('success', `Successfully created new ${rule.type} rule`);
          }
        })
        .finally(() => {
          setIsProcessingRequest(false);
        });
    },
    updateRule: async (rule) => {
      setIsProcessingRequest(true);

      await api
        .updateRule(rule, merchantId)
        .then((res) => {
          if (res.success && res.data?.id) {
            storeActions.updateRule(res.data);
            setIsModalOpen(false);
            notify('success', `Successfully updated ${rule.type} rule ${rule.name}`);
          }
        })
        .finally(() => {
          setIsProcessingRequest(false);
        });
    },
  };

  return formActions;
};
