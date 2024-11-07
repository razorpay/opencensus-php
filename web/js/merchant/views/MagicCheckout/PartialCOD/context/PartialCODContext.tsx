import React, { createContext, useEffect, useMemo, useState } from 'react';
import {
  NEW_PARTIAL_COD_CONFIGS,
  NOTIFICATION_MSGS,
} from 'merchant/views/MagicCheckout/PartialCOD/constants';
import {
  ApiCallbackType,
  CUSTOMER_RISK_CATEGORY,
  CustomerRiskCategory,
  PARTIAL_COD_TYPE,
  PartialCODConfigs,
  PartialCODConfigsType,
  PartialCODContextProps,
  PrepaidPaymentAmountItem,
} from 'merchant/views/MagicCheckout/PartialCOD/types';

export const PartialCODContext = createContext<PartialCODContextProps>({
  configsLocal: NEW_PARTIAL_COD_CONFIGS,
  configsToShow: [],
  handleAddSlab: () => {},
  handleRemoveSlab: () => {},
  handleRiskCategory: () => {},
  handleUpdateSlabType: () => {},
  handleUpdatePartialCODStatus: () => {},
  handleUpdateSlab: () => {},
  isPartialCODEnabledLocal: false,
  saveConfigs: () => {},
  setConfigsLocal: () => {},
  setIsPartialCODEnabledLocal: () => {},
  showNotification: () => {},
});

export function PartialCODContextProvider({
  children,
  isPartialCODEnabled,
  partialCODConfigs,
  platform,
  shopId,
  showNotification,
  updateConfigs,
}) {
  const [configsLocal, setConfigsLocal] = useState<PartialCODConfigs>(NEW_PARTIAL_COD_CONFIGS);
  const [isPartialCODEnabledLocal, setIsPartialCODEnabledLocal] =
    useState<boolean>(isPartialCODEnabled);

  useEffect(() => {
    setConfigsLocal(
      Object.entries(partialCODConfigs).length ? partialCODConfigs : NEW_PARTIAL_COD_CONFIGS,
    );
  }, [partialCODConfigs]);

  const sanitizeConfigs = (configData?: PartialCODConfigs) => {
    const tConfigs: PartialCODConfigs = configData || { ...configsLocal };
    if (tConfigs.type === PARTIAL_COD_TYPE.BASIC) {
      //delete key max_order_amount if type is basic
      delete tConfigs.prepaid_payment_amount[0]?.rules?.max_order_amount;
    }
    return tConfigs;
  };

  const generatePayload = (
    configData?: PartialCODConfigs,
    isEnabled: boolean = isPartialCODEnabledLocal,
  ) => {
    return {
      platform,
      shop_id: shopId,
      one_cc_partial_payments_cod: {
        enabled: configData ? isEnabled : false,
        configs: configData ? sanitizeConfigs(configData) : {},
      },
    };
  };

  const saveConfigs = (
    configData?: PartialCODConfigs,
    { onSuccess, onError, onEnd }: ApiCallbackType = {},
    isEnabled?: boolean,
  ) => {
    const payload = generatePayload(configData, isEnabled);

    updateConfigs(payload)
      .then(() => {
        onSuccess?.();
      })
      .catch((err: { errors: Array<string> }) => {
        showNotification({
          type: 'error',
          message: NOTIFICATION_MSGS[err?.errors[0]] || err?.errors[0] || NOTIFICATION_MSGS.error,
          className: 'magic-partial-cod-notification',
        });
        onError?.();
      })
      .finally(() => {
        onEnd?.();
      });
  };

  const handleAddSlab = (
    newPrepaidPaymentAmountItem: PrepaidPaymentAmountItem,
    callbacks: ApiCallbackType,
  ) => {
    const tConfigs = {
      ...configsLocal,
      prepaid_payment_amount: [...configsLocal.prepaid_payment_amount, newPrepaidPaymentAmountItem],
    };
    saveConfigs(tConfigs, {
      ...callbacks,
      onSuccess: () => {
        setConfigsLocal(tConfigs);
        showNotification({
          type: 'success',
          message: NOTIFICATION_MSGS.slabSavedSuccess,
          className: 'magic-partial-cod-notification',
        });
        callbacks?.onSuccess?.();
      },
    });
  };

  const handleRemoveSlab = (index: number, callbacks: ApiCallbackType) => {
    const tConfigs = { ...configsLocal };
    tConfigs.prepaid_payment_amount = tConfigs.prepaid_payment_amount.filter((_, i) => i !== index);

    saveConfigs(tConfigs, {
      ...callbacks,
      onSuccess: () => {
        setConfigsLocal(tConfigs);
        showNotification({
          type: 'success',
          message: NOTIFICATION_MSGS.slabRemovedSuccess,
          className: 'magic-partial-cod-notification',
        });
        callbacks?.onSuccess?.();
      },
    });
  };

  const handleUpdateSlabType = (targetValue: PartialCODConfigsType, callbacks: ApiCallbackType) => {
    const tConfigs: PartialCODConfigs = {
      type: targetValue,
      prepaid_payment_amount: [],
    };
    if (targetValue === PARTIAL_COD_TYPE.BASIC) {
      saveConfigs(undefined, {
        ...callbacks,
        onSuccess: () => {
          setConfigsLocal(tConfigs);
          showNotification({
            type: 'success',
            message: NOTIFICATION_MSGS.slabRemovedSuccess,
            className: 'magic-partial-cod-notification',
          });
          callbacks?.onSuccess?.();
        },
      });
    } else setConfigsLocal(tConfigs);
  };

  const handleRiskCategory = (
    riskType: Exclude<CustomerRiskCategory, CUSTOMER_RISK_CATEGORY.ALL>,
    index: number,
  ) => {
    const tConfigs = { ...configsLocal };
    const riskCategories = tConfigs.prepaid_payment_amount[index]?.rules?.customer_risk_category;

    if (!riskCategories.includes(riskType)) {
      riskCategories.push(riskType);
    }

    setConfigsLocal(tConfigs);
  };

  const handleUpdateSlab = (
    index: number,
    data: PrepaidPaymentAmountItem,
    callbacks: ApiCallbackType,
  ) => {
    const tConfigs = { ...configsLocal };
    tConfigs.prepaid_payment_amount[index] = data;

    saveConfigs(tConfigs, {
      ...callbacks,
      onSuccess: () => {
        setConfigsLocal(tConfigs);
        showNotification({
          type: 'success',
          message: NOTIFICATION_MSGS.slabUpdatedSuccess,
          className: 'magic-partial-cod-notification',
        });
        callbacks?.onSuccess?.();
      },
    });
  };

  const handleUpdatePartialCODStatus = (status = false, callbacks: ApiCallbackType) => {
    const tConfigs = { ...configsLocal };
    saveConfigs(tConfigs, callbacks, status);
  };

  const configsToShow = useMemo(() => {
    if (configsLocal.type === PARTIAL_COD_TYPE.BASIC)
      return configsLocal.prepaid_payment_amount.length > 0
        ? [configsLocal.prepaid_payment_amount[0]]
        : [];
    return configsLocal.prepaid_payment_amount;
  }, [configsLocal]);

  return (
    <PartialCODContext.Provider
      value={{
        configsLocal,
        configsToShow,
        handleAddSlab,
        handleRemoveSlab,
        handleRiskCategory,
        handleUpdateSlabType,
        handleUpdatePartialCODStatus,
        handleUpdateSlab,
        isPartialCODEnabledLocal,
        saveConfigs,
        setConfigsLocal,
        setIsPartialCODEnabledLocal,
        showNotification,
      }}
    >
      {children}
    </PartialCODContext.Provider>
  );
}
