import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
} from '@razorpay/blade/components';
import React, { useState } from 'react';
import isEqual from 'lodash/isEqual';
import PayLaterListItem from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/PayLaterConfiguration/PayLaterListItem';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import { getPaylater } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/helpers/methods';
import { PaymentConfigInstrument } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types';

export default function PayLaterConfigurationModal({
  isOpen,
  onClose,
  blockName,
}: {
  isOpen: boolean;
  onClose: () => void;
  blockName: string;
}) {
  const { values, handleSelectedConfigChange } = useCheckoutEditor();
  const allMethodDetails = values[CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_METHOD_DETAILS];
  const selectedPaymentConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
  const currentConfig =
    selectedPaymentConfig?.checkout_config?.display?.blocks?.[blockName]?.instruments || [];
  const { providers } = getPaylater(allMethodDetails);

  const initialPaylaterConfigs = currentConfig.filter((item) => item.method === 'paylater');

  const defaultConfig = { providers: [], method: 'paylater' } as PaymentConfigInstrument;

  const initialPaylaterConfig = initialPaylaterConfigs.length > 1
    ? initialPaylaterConfigs.find(
      (item) => (item?.providers?.length !== 1)
    ) || defaultConfig
    : initialPaylaterConfigs[0] || defaultConfig;

  const isInitialConfigExists = initialPaylaterConfigs.length > 0;

  const paylaterModalConfig = {
    ...initialPaylaterConfig,
    providers:

      // 1. when no paylater config is provided => initialPaylaterConfig = defaultConfig.providers  
      // 2. when only non single paylater config is provided => initialPaylaterConfig.providers  
      // 3. when only single paylater config is provided => initialPaylaterConfig.providers  
      // 4. when single and multiple (no provider provided) exist => allProviders  
      // 5. when single and multiple (provider provided) exist => initialPaylaterConfig.providers 
      (!initialPaylaterConfig?.providers || initialPaylaterConfig.providers.length === 0) && isInitialConfigExists
        ? providers.map((provider) => provider.code)
        : initialPaylaterConfig.providers,
  };

  const [updatedPaylaterConfig, setUpdatedPaylaterConfig] = useState(paylaterModalConfig);

  const isConfigChanged = !isEqual(initialPaylaterConfig, updatedPaylaterConfig);
  const [prevPaylaterConfig, setPrevPaylaterConfig] = useState(paylaterModalConfig);

  if (!isEqual(prevPaylaterConfig, paylaterModalConfig)) {
    setPrevPaylaterConfig(paylaterModalConfig);
    setUpdatedPaylaterConfig(paylaterModalConfig)
  }

  const handleSave = () => {
    const updatedInstruments = [...currentConfig];
    const existingIndex = updatedInstruments.findIndex(
      (instrument) =>
        instrument.method === updatedPaylaterConfig.method &&
        (initialPaylaterConfigs.length > 1
          ? (instrument?.providers?.length ?? 0) === 0 || (instrument?.providers?.length ?? 0) > 1
          : true),
    );

    if (existingIndex !== -1) {
      updatedInstruments[existingIndex] = updatedPaylaterConfig;
    } else {
      updatedInstruments.push(updatedPaylaterConfig);
    }

    const blocks = {
      ...selectedPaymentConfig?.checkout_config?.display?.blocks,
      [blockName]: {
        ...(selectedPaymentConfig?.checkout_config?.display?.blocks?.[blockName] || {}),
        instruments: updatedInstruments,
        name: selectedPaymentConfig?.checkout_config?.display?.blocks?.[blockName]?.name || '',
      },
    };
    handleSelectedConfigChange({
      ...selectedPaymentConfig,
      checkout_config: {
        ...selectedPaymentConfig.checkout_config,
        display: {
          ...selectedPaymentConfig?.checkout_config?.display,
          blocks,
        },
      },
    });
  };
  const Footer = () => {
    return (
      <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
        <Button variant="tertiary" onClick={onClose}>
          Cancel
        </Button>
        <Button
          isDisabled={!isConfigChanged}
          onClick={() => {
            handleSave();
            onClose();
          }}
        >
          Save
        </Button>
      </Box>
    );
  };
  return (
    <Modal isOpen={isOpen} onDismiss={onClose} size="medium">
      <ModalHeader title="PayLater" subtitle="Buy now, pay with e-pay later" />
      <ModalBody padding="spacing.0">
        <Box paddingY="spacing.6" paddingX="spacing.9">
          <Box gap="spacing.5" display="flex" flexDirection="column">
            {providers.map((item, index) => (
              <Box testID="ListItemCard" key={index}>
                <PayLaterListItem
                  item={item}
                  activeBanks={updatedPaylaterConfig}
                  setActiveBanks={setUpdatedPaylaterConfig}
                />
              </Box>
            ))}
          </Box>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Footer />
      </ModalFooter>
    </Modal>
  );
}
