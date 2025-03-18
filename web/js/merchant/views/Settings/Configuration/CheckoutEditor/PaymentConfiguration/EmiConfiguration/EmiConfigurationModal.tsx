import {
  Accordion,
  AccordionItem,
  AccordionItemBody,
  AccordionItemHeader,
  Box,
  Button,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
} from '@razorpay/blade/components';
import React, { useState } from 'react';
import isEqual from 'lodash/isEqual';
import EmiCardTypeList from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/EmiConfiguration/EmiCardTypeList';
import EmiCardProviderList from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/EmiConfiguration/EmiCardProviderList';
import EmiBinNumber from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/EmiConfiguration/EmiBinNumber';
import CardlessEmi from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/EmiConfiguration/CardlessEmi';
import EmiCardIssuerList from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/EmiConfiguration/EmiCardIssuerList';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import {
  getCard,
  getEmi,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/helpers/methods';
import { PaymentConfigInstrument } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types';

export default function EmiConfigurationModal({
  isOpen,
  onClose,
  blockName,
}: {
  isOpen: boolean;
  onClose: () => void;
  blockName: string;
}) {
  const cardType: { title: string;[key: string]: any }[] = [
    {
      title: 'debit',
    },
    {
      title: 'credit',
    },
  ];

  const { values, handleSelectedConfigChange } = useCheckoutEditor();
  const allMethodDetails = values[CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_METHOD_DETAILS];
  const selectedPaymentConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
  const currentConfig =
    selectedPaymentConfig?.checkout_config?.display?.blocks?.[blockName]?.instruments || [];
  const { providers } = getEmi(allMethodDetails);
  const { networks } = getCard(allMethodDetails);
  const cardConfig = currentConfig?.find((item) => item.method === 'emi')
  const initialEmiConfig = cardConfig || {
    iins: [],
    types: [],
    method: 'emi',
    issuers: [],
    networks: [],
  };
  const { debit: debitCardProviders, credit: creditCardProviders, cardless } = providers;

  const [emiIssuers, setEmiIssuers] = useState([...debitCardProviders, ...creditCardProviders]);

  const isInitialConfigExists = !!(cardConfig);
  const emiModalConfig = {
    ...initialEmiConfig,
    issuers:
      isInitialConfigExists && ((initialEmiConfig?.issuers ?? []).length === 0)
        ? emiIssuers.map((issuer) => issuer.code.replace('_DC', ''))
        : initialEmiConfig.issuers,
    networks:
      isInitialConfigExists && ((initialEmiConfig?.networks ?? []).length === 0)
        ? networks.map((network) => network.name)
        : initialEmiConfig.networks,
    types:
      isInitialConfigExists && ((initialEmiConfig?.types ?? []).length === 0)
        ? cardType.map((type) => type.title)
        : initialEmiConfig.types,
  };
  const [updatedEMIConfig, setUpdatedEMIConfig] = useState(emiModalConfig);
  const [prevEmiConfig, setPrevEmiConfig] = useState(emiModalConfig);

  if (!isEqual(prevEmiConfig, emiModalConfig)) {
    setUpdatedEMIConfig(emiModalConfig);
    setPrevEmiConfig(emiModalConfig);
  }

  const initialConfigs = currentConfig.filter((item) => item.method === 'cardless_emi');
  const isInitialConfigExistsCardless = currentConfig.some((item) => item.method === 'cardless_emi');

  const defaultConfig = { providers: [], method: 'cardless_emi' };

  const initialConfig = initialConfigs.length > 1
    ? initialConfigs.find((item) => (item.providers?.length !== 1)) || defaultConfig
    : initialConfigs[0] || defaultConfig;

  const cardlessModalConfig = {
    ...initialConfig,
    providers:
      // 1. when no CardlessEmi config is provided => initialCardlessEmiConfig = defaultConfig.providers  
      // 2. when only non single CardlessEmi config is provided => initialCardlessEmiConfig.providers  
      // 3. when only single CardlessEmi config is provided => initialCardlessEmiConfig.providers  
      // 4. when single and multiple (no provider provided) exist => allproviders  
      // 5. when single and multiple (provider provided) exist => initialCardlessEmiConfig.providers 
      (!initialConfig?.providers || initialConfig.providers.length === 0) &&
        isInitialConfigExistsCardless
        ? cardless.map((provider) => provider.code)
        : initialConfig.providers,
  };

  const [updatedCardLessEmiConfig, setUpdatedCardLessEmiConfig] = useState(
    cardlessModalConfig,
  );
  const isConfigChanged = !isEqual(initialConfig, updatedCardLessEmiConfig) || !isEqual(initialEmiConfig, updatedEMIConfig)
  const [prevCardlessEmiConfig, setPrevCardlessEmiConfig] = useState(cardlessModalConfig);

  if (!isEqual(prevCardlessEmiConfig, cardlessModalConfig)) {
    setUpdatedCardLessEmiConfig(cardlessModalConfig);
    setPrevCardlessEmiConfig(cardlessModalConfig);
  }
  const [isEnableSaveButton, setIsEnableSaveButton] = useState(false);

  const updateEmiIssuers = (types) => {
    let issuers: { code: string; name: any }[] = [];
    if ((types ?? []).length === 0) {
      issuers = [...debitCardProviders, ...creditCardProviders];
    } else {
      if ((types ?? []).includes('debit')) {
        issuers = [...debitCardProviders, ...issuers];
      }
      if ((types ?? []).includes('credit')) {
        issuers = [...issuers, ...creditCardProviders];
      }
    }
    setEmiIssuers(issuers);
  };

  const handleSave = () => {
    const updatedInstruments = [
      ...((selectedPaymentConfig?.checkout_config?.display?.blocks?.[blockName]?.instruments ||
        []) as PaymentConfigInstrument[]),
    ];

    // Update or add activeEMI
    const existingIndexEMI = updatedInstruments.findIndex(
      (instrument) => instrument.method === updatedEMIConfig.method,
    );

    if (existingIndexEMI !== -1) {
      updatedInstruments[existingIndexEMI] = updatedEMIConfig;
    } else {
      updatedInstruments.push(updatedEMIConfig);
    }

    // Update or add activeCardLessEmi
    const existingIndexCardless = updatedInstruments.findIndex(
      (instrument) =>
        instrument.method === updatedCardLessEmiConfig.method &&
        (initialConfigs.length > 1
          ? (instrument?.providers?.length ?? 0) === 0 || (instrument?.providers?.length ?? 0) > 1
          : true),
    );

    if (existingIndexCardless !== -1) {
      updatedInstruments[existingIndexCardless] = updatedCardLessEmiConfig;
    } else {
      updatedInstruments.push(updatedCardLessEmiConfig);
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
      <ModalHeader title="EMI" subtitle="Debit card, Credit card and Cashless EMI options" />
      <ModalBody padding="spacing.0">
        <Box
          paddingY="spacing.5"
          paddingX="spacing.9"
          display="flex"
          flexDirection="column"
          gap="spacing.5"
        >
          <Accordion variant="filled">
            <AccordionItem>
              <AccordionItemHeader title="Card Type" />
              <AccordionItemBody>
                <EmiCardTypeList
                  cardType={cardType}
                  updateEmiIssuers={updateEmiIssuers}
                  finalCardConfigurationObj={updatedEMIConfig}
                  setFinalCardConfigurationObj={setUpdatedEMIConfig}
                />
              </AccordionItemBody>
            </AccordionItem>
          </Accordion>
          <Accordion variant="filled">
            <AccordionItem>
              <AccordionItemHeader title="Card Issuer" />
              <AccordionItemBody>
                <EmiCardIssuerList
                  cardIssuer={emiIssuers}
                  finalCardConfigurationObj={updatedEMIConfig}
                  setFinalCardConfigurationObj={setUpdatedEMIConfig}
                />
              </AccordionItemBody>
            </AccordionItem>
          </Accordion>
          <Accordion variant="filled">
            <AccordionItem>
              <AccordionItemHeader title="Card Provider" />
              <AccordionItemBody>
                <EmiCardProviderList
                  cardProvider={networks}
                  finalCardConfigurationObj={updatedEMIConfig}
                  setFinalCardConfigurationObj={setUpdatedEMIConfig}
                />
              </AccordionItemBody>
            </AccordionItem>
          </Accordion>
          <Accordion variant="filled">
            <AccordionItem>
              <AccordionItemHeader title="Bin Number" />
              <AccordionItemBody>
                <EmiBinNumber
                  finalCardConfigurationObj={updatedEMIConfig}
                  setFinalCardConfigurationObj={setUpdatedEMIConfig}
                />
              </AccordionItemBody>
            </AccordionItem>
          </Accordion>
          {cardless.length > 0 && (
            <Accordion variant="filled">
              <AccordionItem>
                <AccordionItemHeader title="Cardless EMI" />
                <AccordionItemBody>
                  <CardlessEmi
                    cardlessProvider={cardless}
                    finalCardConfigurationObj={updatedCardLessEmiConfig}
                    setFinalCardConfigurationObj={setUpdatedCardLessEmiConfig}
                  />
                </AccordionItemBody>
              </AccordionItem>
            </Accordion>
          )}
        </Box>
      </ModalBody>
      <ModalFooter>
        <Footer />
      </ModalFooter>
    </Modal>
  );
}
