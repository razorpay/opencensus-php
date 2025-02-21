import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
} from '@razorpay/blade/components';
import React, { useEffect, useState } from 'react';
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

  const initialObjects = currentConfig.filter((item) => item.method === 'paylater');
  let initialObject: PaymentConfigInstrument = {
    providers: [],
    method: 'paylater',
  };

  if (initialObjects.length > 1) {
    initialObject =
      initialObjects.find(
        (item) => (item?.providers?.length ?? 0) === 0 || (item?.providers?.length ?? 0) > 1,
      ) || initialObject;
  } else if (initialObjects.length === 1) {
    initialObject = initialObjects[0];
  }

  const isInitialConfigExists =
    currentConfig.filter((item) => item.method === 'paylater').length > 0;
  const initialFinalCardConfigurationObj = {
    ...initialObject,
    providers:
      (!initialObject?.providers || initialObject.providers.length === 0) && isInitialConfigExists
        ? providers.map((provider) => provider.code)
        : initialObject.wallets,
  };
  const [isEnableSaveButton, setIsEnableSaveButton] = useState(false);
  const [activeBanks, setActiveBanks] = useState(initialFinalCardConfigurationObj);
  useEffect(() => {
    setIsEnableSaveButton(!isEqual(initialObject, activeBanks));
  }, [activeBanks, initialObject]);

  const handleSave = () => {
    const updatedInstruments = [...currentConfig];
    const existingIndex = updatedInstruments.findIndex(
      (instrument) =>
        instrument.method === activeBanks.method &&
        (initialObjects.length > 1
          ? (instrument?.providers?.length ?? 0) === 0 || (instrument?.providers?.length ?? 0) > 1
          : true),
    );

    if (existingIndex !== -1) {
      updatedInstruments[existingIndex] = activeBanks;
    } else {
      updatedInstruments.push(activeBanks);
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
          isDisabled={!isEnableSaveButton}
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
                  activeBanks={activeBanks}
                  setActiveBanks={setActiveBanks}
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
