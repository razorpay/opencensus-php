import React, { useState } from 'react';
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
import { compose } from 'redux';
import { connect } from 'react-redux';
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

function CardConfigurationModal({
  isOpen,
  onClose,
  blockName,
  org
}: {
  isOpen: boolean;
  onClose: () => void;
  blockName: string;
  org: { business_name: string };
}) {
  const { values, handleSelectedConfigChange } = useCheckoutEditor();
  const allMethodDetails = values[CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_METHOD_DETAILS];
  const selectedPaymentConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
  const currentConfig =
    selectedPaymentConfig?.checkout_config?.display?.blocks?.[blockName]?.instruments || [];

  const { types, networks } = getCard(allMethodDetails);
  const { banks: activatedCardIssuers } = getNetbanking(allMethodDetails);
  const [cardConfigType, setCardConfigType] = useState(['']);
  const cardConfig = currentConfig?.find((item) => item.method === 'card')

  const initialCardConfig = cardConfig ?? initialState;
  const isInitialConfigExists = !!(cardConfig);

  const cardModalConfig = {
    ...initialCardConfig,
    issuers:
      isInitialConfigExists && ((initialCardConfig?.issuers ?? []).length === 0)
        ? activatedCardIssuers.map((issuer) => issuer.code)
        : initialCardConfig.issuers,
    networks:
      isInitialConfigExists && ((initialCardConfig?.networks ?? []).length === 0)
        ? networks.map((network) => network.name)
        : initialCardConfig.networks,
    types:
      isInitialConfigExists && ((initialCardConfig?.types ?? []).length === 0)
        ? cardType.map((type) => type.title)
        : initialCardConfig.types,
  };
  const [updatedCardModalConfig, setUpdatedCardModalConfig] = useState(
    cardModalConfig,
  );
  const [prevCardConfig, setPrevCardConfig] = useState(cardModalConfig);

  if (!isEqual(cardModalConfig, prevCardConfig)) {
    setPrevCardConfig(cardModalConfig);
    setUpdatedCardModalConfig(cardModalConfig);
  }


  const close = () => {
    onClose();
    setCardConfigType(['']);
  };

  cardType.forEach((item) => {
    if (!types[item.title]) {
      item['isDisabled'] = true;
    }
  });

  const hasCardConfigChanged = !isEqual(initialCardConfig, updatedCardModalConfig)

  const handleSave = () => {
    const updatedInstruments = [
      ...((selectedPaymentConfig?.checkout_config?.display?.blocks?.[blockName]?.instruments ||
        []) as PaymentConfigInstrument[]),
    ];

    const existingIndex = updatedInstruments.findIndex(
      (instrument) => instrument.method === updatedCardModalConfig.method,
    );
    if (existingIndex !== -1) {
      updatedInstruments[existingIndex] = updatedCardModalConfig;
    } else {
      updatedInstruments.push(updatedCardModalConfig);
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
      <Button isDisabled={!hasCardConfigChanged} onClick={handleSave}>
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
                setUpdatedCardModalConfig={setUpdatedCardModalConfig}
                updatedCardModalConfig={updatedCardModalConfig}
                cardType={cardType}
                org={org}
              />
            )}
            {cardConfigType.includes('Card Provider') && (
              <CardProvider
                setUpdatedCardModalConfig={setUpdatedCardModalConfig}
                updatedCardModalConfig={updatedCardModalConfig}
                cardProvider={networks}
                org={org}
              />
            )}
            {cardConfigType.includes('Card Issuer') && (
              <CardIssuer
                setUpdatedCardModalConfig={setUpdatedCardModalConfig}
                updatedCardModalConfig={updatedCardModalConfig}
                cardIssuer={activatedCardIssuers}
                org={org}
              />
            )}
            {cardConfigType.includes('BIN Number') && (
              <BinNumber
                setUpdatedCardModalConfig={setUpdatedCardModalConfig}
                updatedCardModalConfig={updatedCardModalConfig}
                org={org}
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

const mapStateToProps = (state) => ({
  org: state.session.org,
});

export default compose(connect(mapStateToProps))(CardConfigurationModal);