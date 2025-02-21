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
import WalletListItem from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/WalletConfiguration/WalletListItem';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import { getWallet } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/helpers/methods';

export default function WalletConfigurationModal({
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
  const { wallets } = getWallet(allMethodDetails);

  const initialObjects = currentConfig.filter((item) => item.method === 'wallet');
  const initialObject =
    initialObjects.length > 1
      ? initialObjects.find(
          (item) => (item.wallets?.length ?? 0) === 0 || (item.wallets?.length ?? 0) > 1,
        ) || {
          wallets: [],
          method: 'wallet',
        }
      : initialObjects[0] || { wallets: [], method: 'wallet' };

  const isInitialConfigExists = currentConfig.filter((item) => item.method === 'wallet').length > 0;
  const initialFinalCardConfigurationObj = {
    ...initialObject,
    wallets:
      (!initialObject?.wallets || initialObject.wallets.length === 0) && isInitialConfigExists
        ? wallets.map((provider) => provider.code)
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
          ? (instrument?.wallets?.length ?? 0) === 0 || (instrument?.wallets?.length ?? 0) > 1
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
        name: selectedPaymentConfig?.checkout_config?.display?.blocks?.[blockName]?.name || '',
        instruments: updatedInstruments,
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
    onClose();
  };

  return (
    <Modal isOpen={isOpen} onDismiss={onClose} size="medium">
      <ModalHeader title="Wallet" subtitle="Amazon Pay, Freecharge, PhonePe, PayTM," />
      <ModalBody padding="spacing.0">
        <Box paddingY="spacing.6" paddingX="spacing.9">
          <Box gap="spacing.5" display="flex" flexDirection="column">
            {wallets.map((item, index) => (
              <Box testID="ListItemCard" key={index}>
                <WalletListItem
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
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button variant="tertiary" onClick={onClose}>
            Cancel
          </Button>
          <Button isDisabled={!isEnableSaveButton} onClick={handleSave}>
            Save
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
}
