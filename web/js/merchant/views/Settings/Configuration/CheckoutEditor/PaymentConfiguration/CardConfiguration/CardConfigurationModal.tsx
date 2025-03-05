import React, { useEffect, useState } from 'react';
import isEqual from 'lodash/isEqual';
import {
  Box,
  Button,
  Chip,
  ChipGroup,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
} from '@razorpay/blade/components';
import CardType from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CardConfiguration/CardType';
import CardIssuer from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CardConfiguration/CardIssuer';
import CardProvider from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CardConfiguration/CardProvider';
import BinNumber from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CardConfiguration/BinNumber';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import {
  getCard,
  getNetbanking,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/helpers/methods';
import { PaymentConfigInstrument } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types';

const cardType: { title: string; [key: string]: any }[] = [
  {
    title: 'debit',
    isEnabled: true,
  },
  {
    title: 'credit',
  },
];
const initialState = {
  iins: [],
  types: [],
  method: 'card',
  issuers: [],
  networks: [],
};

export default function CardConfigurationModal({
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

  const { types, networks } = getCard(allMethodDetails);
  const { banks: activatedCardIssuers } = getNetbanking(allMethodDetails);
  const [cardConfigType, setCardConfigType] = React.useState(['']);
  const [isEnableSaveButton, setIsEnableSaveButton] = React.useState(false);
  const initialObject = currentConfig?.find((item) => item.method === 'card') || initialState;
  const isInitialConfigExists = currentConfig.filter((item) => item.method === 'card').length > 0;
  const initialFinalCardConfigurationObj = {
    ...initialObject,
    issuers:
      (!initialObject?.issuers || initialObject.issuers.length === 0) && isInitialConfigExists
        ? activatedCardIssuers.map((issuer) => issuer.code)
        : initialObject.issuers,
    networks:
      (!initialObject?.networks || initialObject.networks.length === 0) && isInitialConfigExists
        ? networks.map((network) => network.name)
        : initialObject.networks,
    types:
      (!initialObject?.types || initialObject.types.length === 0) && isInitialConfigExists
        ? cardType.map((type) => type.title)
        : initialObject.types,
  };
  const [finalCardConfigurationObj, setFinalCardConfigurationObj] = useState(
    initialFinalCardConfigurationObj,
  );

  const close = () => {
    onClose();
    setCardConfigType(['']);
  };

  // @HarshLileshShah remove this useEffect that you have added
  useEffect(() => {
    cardType.forEach((item) => {
      if (!types[item.title]) {
        item.isDisabled = true;
      }
    });
  }, [types]);

  // @HarshLileshShah remove this useEffect that you have added
  useEffect(() => {
    if (!isEqual(initialObject, finalCardConfigurationObj)) {
      setIsEnableSaveButton(true);
    } else {
      setIsEnableSaveButton(false);
    }
  }, [finalCardConfigurationObj, currentConfig]);

  const handleSave = () => {
    const updatedInstruments = [
      ...((selectedPaymentConfig?.checkout_config?.display?.blocks?.[blockName]?.instruments ||
        []) as PaymentConfigInstrument[]),
    ];

    const existingIndex = updatedInstruments.findIndex(
      (instrument) => instrument.method === finalCardConfigurationObj.method,
    );
    if (existingIndex !== -1) {
      updatedInstruments[existingIndex] = finalCardConfigurationObj;
    } else {
      updatedInstruments.push(finalCardConfigurationObj);
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
    close();
  };

  const Footer = () => (
    <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
      <Button variant="tertiary" onClick={close}>
        Cancel
      </Button>
      <Button isDisabled={!isEnableSaveButton} onClick={handleSave}>
        Save
      </Button>
    </Box>
  );

  return (
    <Modal isOpen={isOpen} onDismiss={close} size="medium">
      <ModalHeader title="Domestic cards" />
      <ModalBody padding="spacing.0">
        <Box paddingY="spacing.6" paddingX="spacing.9">
          <Box>
            <ChipGroup
              accessibilityLabel="Select how you want to add cards"
              label="Select how you want to add cards"
              onChange={({ values }) => setCardConfigType(values)}
              selectionType="multiple"
            >
              <Chip value="Card Type">Card Type</Chip>
              <Chip value="Card Provider">Card Provider</Chip>
              <Chip value="Card Issuer">Card Issuer</Chip>
              <Chip value="BIN Number">BIN Number</Chip>
            </ChipGroup>
          </Box>
          <Box paddingTop="spacing.9" gap="spacing.5" display="flex" flexDirection="column">
            {cardConfigType.includes('Card Type') && (
              <CardType
                setFinalCardConfigurationObj={setFinalCardConfigurationObj}
                finalCardConfigurationObj={finalCardConfigurationObj}
                cardType={cardType}
              />
            )}
            {cardConfigType.includes('Card Provider') && (
              <CardProvider
                setFinalCardConfigurationObj={setFinalCardConfigurationObj}
                finalCardConfigurationObj={finalCardConfigurationObj}
                cardProvider={networks}
              />
            )}
            {cardConfigType.includes('Card Issuer') && (
              <CardIssuer
                setFinalCardConfigurationObj={setFinalCardConfigurationObj}
                finalCardConfigurationObj={finalCardConfigurationObj}
                cardIssuer={activatedCardIssuers}
              />
            )}
            {cardConfigType.includes('BIN Number') && (
              <BinNumber
                setFinalCardConfigurationObj={setFinalCardConfigurationObj}
                finalCardConfigurationObj={finalCardConfigurationObj}
              />
            )}
          </Box>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Footer />
      </ModalFooter>
    </Modal>
  );
}
