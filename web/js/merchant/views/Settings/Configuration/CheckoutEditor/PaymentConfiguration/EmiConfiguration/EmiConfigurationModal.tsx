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
import React, { useEffect, useState } from 'react';
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
  const cardType: { title: string; [key: string]: any }[] = [
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
  const initialState = currentConfig?.find((item) => item.method === 'emi') || {
    iins: [],
    types: [],
    method: 'emi',
    issuers: [],
    networks: [],
  };
  const { debit: debitCardProviders, credit: creditCardProviders, cardless } = providers;

  const [emiIssuers, setEmiIssuers] = useState([...debitCardProviders, ...creditCardProviders]);

  const isInitialConfigExists = currentConfig.filter((item) => item.method === 'emi').length > 0;
  const initialFinalCardConfigurationObj = {
    ...initialState,
    issuers:
      (!initialState?.issuers || initialState.issuers.length === 0) && isInitialConfigExists
        ? emiIssuers.map((issuer) => issuer.code.replace('_DC', ''))
        : initialState.issuers,
    networks:
      (!initialState?.networks || initialState.networks.length === 0) && isInitialConfigExists
        ? networks.map((network) => network.name)
        : initialState.networks,
    types:
      (!initialState?.types || initialState.types.length === 0) && isInitialConfigExists
        ? cardType.map((type) => type.title)
        : initialState.types,
  };
  const [activeEMI, setActiveEMI] = useState(initialFinalCardConfigurationObj);
  const initialObjects = currentConfig.filter((item) => item.method === 'cardless_emi');
  const isInitialConfigExistsCardless =
    currentConfig.filter((item) => item.method === 'cardless_emi').length > 0;
  const initialObject =
    initialObjects.length > 1
      ? initialObjects.find(
          (item) => (item.providers?.length ?? 0) === 0 || (item.providers?.length ?? 0) > 1,
        ) || {
          providers: [],
          method: 'cardless_emi',
        }
      : initialObjects[0] || { wallets: [], method: 'cardless_emi' };

  const initialFinalCardConfigurationObCardless = {
    ...initialObject,
    providers:
      (!initialObject?.providers || initialObject.providers.length === 0) &&
      isInitialConfigExistsCardless
        ? cardless.map((provider) => provider.code)
        : initialObject.providers,
  };

  const [activeCardLessEmi, setActiveCardLessEmi] = useState(
    initialFinalCardConfigurationObCardless,
  );
  const [isEnableSaveButton, setIsEnableSaveButton] = useState(false);

  // @HarshLileshShah remove this useEffect that you have added
  useEffect(() => {
    setIsEnableSaveButton(
      !isEqual(initialObject, activeCardLessEmi) || !isEqual(initialState, activeEMI),
    );
  }, [activeCardLessEmi, activeEMI]);

  // @HarshLileshShah remove this useEffect that you have added
  useEffect(() => {
    let issuers: { code: string; name: any }[] = [];
    if ((activeEMI?.types ?? []).length === 0) {
      issuers = [...debitCardProviders, ...creditCardProviders];
    } else {
      if ((activeEMI?.types ?? []).includes('debit')) {
        issuers = [...debitCardProviders, ...issuers];
      }
      if ((activeEMI?.types ?? []).includes('credit')) {
        issuers = [...creditCardProviders, ...issuers];
      }
    }
    setEmiIssuers(issuers);
  }, [activeEMI?.types]);

  const handleSave = () => {
    const updatedInstruments = [
      ...((selectedPaymentConfig?.checkout_config?.display?.blocks?.[blockName]?.instruments ||
        []) as PaymentConfigInstrument[]),
    ];

    // Update or add activeEMI
    const existingIndexEMI = updatedInstruments.findIndex(
      (instrument) => instrument.method === activeEMI.method,
    );

    if (existingIndexEMI !== -1) {
      updatedInstruments[existingIndexEMI] = activeEMI;
    } else {
      updatedInstruments.push(activeEMI);
    }

    // Update or add activeCardLessEmi
    const existingIndexCardless = updatedInstruments.findIndex(
      (instrument) =>
        instrument.method === activeCardLessEmi.method &&
        (initialObjects.length > 1
          ? (instrument?.banks?.length ?? 0) === 0 || (instrument?.banks?.length ?? 0) > 1
          : true),
    );

    if (existingIndexCardless !== -1) {
      updatedInstruments[existingIndexCardless] = activeCardLessEmi;
    } else {
      updatedInstruments.push(activeCardLessEmi);
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
                  finalCardConfigurationObj={activeEMI}
                  setFinalCardConfigurationObj={setActiveEMI}
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
                  finalCardConfigurationObj={activeEMI}
                  setFinalCardConfigurationObj={setActiveEMI}
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
                  finalCardConfigurationObj={activeEMI}
                  setFinalCardConfigurationObj={setActiveEMI}
                />
              </AccordionItemBody>
            </AccordionItem>
          </Accordion>
          <Accordion variant="filled">
            <AccordionItem>
              <AccordionItemHeader title="Bin Number" />
              <AccordionItemBody>
                <EmiBinNumber
                  finalCardConfigurationObj={activeEMI}
                  setFinalCardConfigurationObj={setActiveEMI}
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
                    finalCardConfigurationObj={activeCardLessEmi}
                    setFinalCardConfigurationObj={setActiveCardLessEmi}
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
