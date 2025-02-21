import React, { useState } from 'react';
import {
  ActionList,
  ActionListItem,
  Box,
  Button,
  Dropdown,
  DropdownOverlay,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  RadioGroup,
  SelectInput,
} from '@razorpay/blade/components';
import { PaymentConfigInstrument } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types';

import SingleInstrumentListItem from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/AddSingleInstrument/SingleInstrumentListItem';
import {
  getEmi,
  getNetbanking,
  getPaylater,
  getUpi,
  getWallet,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/helpers/methods';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import { PREVIEW_SCREEN } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
export function AddSingleInstrumentModal({
  onClose,
  blockID,
}: {
  onClose: () => void;
  blockID: string;
}) {
  const {
    values,
    handleSelectedConfigChange,
    handleSelectedPaymentOptionChange,
    handlePreviewScreenChange,
  } = useCheckoutEditor();
  const allMethodDetails = values[CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_METHOD_DETAILS];
  const selectedPaymentConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
  const selectedBlockInstruments =
    selectedPaymentConfig?.checkout_config?.display?.blocks?.[blockID]?.instruments || [];
  const { providers: paylaterProviders, isEnabled: isEnabledPaylater } =
    getPaylater(allMethodDetails);
  const { wallets, isEnabled: isEnabledWallets } = getWallet(allMethodDetails);
  const { banks: netbankingProviders, isEnabled: isEnabledNetbanking } = getNetbanking(
    allMethodDetails,
  ) as { banks: Array<{ code: string; name: any; type?: string }>; isEnabled: boolean };
  const { providers: emiProvider, isEnabled: isEnabledCardlessEmi } = getEmi(allMethodDetails);
  const { cardless } = emiProvider;
  const { apps: upiApps, isEnabled: isEnabledUpi } = getUpi(allMethodDetails);
  netbankingProviders.forEach((item) => {
    item.type = item.code.includes('_C') ? 'corporate' : 'retail';
  });

  const [paymentsMethodSelected, setPaymentsMethodSelected] = useState<string>('');
  const [singleInstrumentConfig, setSingleInstrumentConfig] =
    useState<PaymentConfigInstrument | null>(null);
  const close = () => {
    setPaymentsMethodSelected('');
    onClose();
  };

  const handelConfig = ({ value }) => {
    switch (paymentsMethodSelected) {
      case 'wallet':
        setSingleInstrumentConfig({ method: 'wallet', wallets: [value] });
        break;
      case 'netbanking':
        setSingleInstrumentConfig({ method: 'netbanking', banks: [value] });
        break;
      case 'paylater':
        setSingleInstrumentConfig({ method: 'paylater', providers: [value] });
        break;
      case 'cardless_emi':
        setSingleInstrumentConfig({ method: 'cardless_emi', providers: [value] });
        break;
      case 'upi':
        setSingleInstrumentConfig({ method: 'upi', apps: [value], flows: ['intent'] });
        break;
      default:
        setSingleInstrumentConfig(null);
    }
  };
  const handleSaveClick = () => {
    const blocks = {
      ...selectedPaymentConfig?.checkout_config?.display?.blocks,
      [blockID]: {
        ...(selectedPaymentConfig?.checkout_config?.display?.blocks?.[blockID] || {}),
        instruments: [
          ...(selectedBlockInstruments ?? []),
          ...(singleInstrumentConfig ? [singleInstrumentConfig] : []),
        ],
        name: selectedPaymentConfig?.checkout_config?.display?.blocks?.[blockID]?.name || '',
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
    handleSelectedPaymentOptionChange({
      name: selectedPaymentConfig?.checkout_config?.display?.blocks?.[blockID]?.name || '',
      isCustomBlock: true,
    });
    handlePreviewScreenChange(PREVIEW_SCREEN.METHODS);
  };
  const Footer = () => {
    return (
      <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
        <Button variant="tertiary" onClick={close}>
          Cancel
        </Button>
        <Button
          onClick={() => {
            handleSaveClick();
            close();
          }}
          isDisabled={!singleInstrumentConfig}
        >
          Save
        </Button>
      </Box>
    );
  };
  return (
    <Modal isOpen={true} onDismiss={close} size="medium">
      <ModalHeader title="Add a single payment instrument" />
      <ModalBody padding="spacing.0">
        <Box paddingY="spacing.6" paddingX="spacing.9">
          <Dropdown selectionType="single">
            <SelectInput
              label="Select a payment method"
              placeholder="Select an option"
              name="action"
              onChange={({ values }) => {
                setPaymentsMethodSelected(values[0]);
              }}
            />
            <DropdownOverlay>
              <ActionList>
                {isEnabledWallets && <ActionListItem title="Wallet" value="wallet" />}
                {isEnabledNetbanking && <ActionListItem title="Netbanking" value="netbanking" />}
                {isEnabledPaylater && <ActionListItem title="Paylater" value="paylater" />}
                {isEnabledCardlessEmi && cardless.length > 0 && (
                  <ActionListItem title="Cardless EMI" value="cardless_emi" />
                )}
                {isEnabledUpi && <ActionListItem title="UPI" value="upi" />}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
          <Box gap="spacing.5" paddingTop="spacing.5" display="flex" flexDirection="column">
            <RadioGroup label="" necessityIndicator="none" size="medium" onChange={handelConfig}>
              {paymentsMethodSelected === 'paylater' &&
                paylaterProviders.map((item, index) => (
                  <Box testID="ListItemCard" key={index}>
                    <SingleInstrumentListItem item={item} method="paylater" />
                  </Box>
                ))}
              {paymentsMethodSelected === 'wallet' &&
                wallets.map((item, index) => (
                  <Box testID="ListItemCard" key={index}>
                    <SingleInstrumentListItem item={item} method="wallet" />
                  </Box>
                ))}
              {paymentsMethodSelected === 'netbanking' &&
                netbankingProviders.map((item, index) => (
                  <Box testID="ListItemCard" key={index}>
                    <SingleInstrumentListItem item={item} method="netbanking" />
                  </Box>
                ))}
              {paymentsMethodSelected === 'upi' &&
                upiApps.map((item, index) => (
                  <Box testID="ListItemCard" key={index}>
                    <SingleInstrumentListItem item={item} method="upi" />
                  </Box>
                ))}
              {paymentsMethodSelected === 'cardless_emi' &&
                cardless.map((item, index) => (
                  <Box testID="ListItemCard" key={index}>
                    <SingleInstrumentListItem item={item} method="cardless_emi" />
                  </Box>
                ))}
            </RadioGroup>
          </Box>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Footer />
      </ModalFooter>
    </Modal>
  );
}
