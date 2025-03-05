import React, { useEffect } from 'react';
import isEqual from 'lodash/isEqual';
import {
  ArrowRightIcon,
  Box,
  Button,
  Link,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Switch,
  Text,
} from '@razorpay/blade/components';
import { ListItemCard } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/ListItemCard';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import _track from './track';

export default function UpiConfigurationModal({
  isOpen,
  onClose,
  blockName,
}: {
  isOpen: boolean;
  onClose: () => void;
  blockName: string;
}) {
  const { values, handleSelectedConfigChange } = useCheckoutEditor();
  const selectedPaymentConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
  const currentConfig =
    selectedPaymentConfig?.checkout_config?.display?.blocks?.[blockName]?.instruments || [];
  const [isEnableSaveButton, setIsEnableSaveButton] = React.useState(false);
  const initialObject = currentConfig?.find((item) => item.method === 'upi') || {
    method: 'upi',
    flows: [],
  };
  const isInitialConfigExists = currentConfig.filter((item) => item.method === 'upi').length > 0;
  const initialFinalCardConfigurationObj = {
    ...initialObject,
    flows:
      (!initialObject?.flows || initialObject.flows.length === 0) && isInitialConfigExists
        ? ['qr', 'intent', 'collect']
        : initialObject.flows,
  };
  const [upiFeatureEnabledList, setUpiFeatureEnabledList] = React.useState(
    initialFinalCardConfigurationObj,
  );

  // @HarshLileshShah remove this useEffect that you have added
  useEffect(() => {
    if (!isEqual(initialObject, upiFeatureEnabledList)) {
      setIsEnableSaveButton(true);
    } else {
      setIsEnableSaveButton(false);
    }
  }, [upiFeatureEnabledList, currentConfig]);

  const UpiConfigurationList = [
    {
      title: 'UPI QR Code',
      docsUrl:
        'https://razorpay.com/docs/payments/payment-gateway/web-integration/standard/configure-payment-methods/supported-methods/#supported-upi-flows',
      flows: 'qr',
    },
    {
      title: 'UPI Apps',
      docsUrl:
        'https://razorpay.com/docs/payments/payment-gateway/web-integration/standard/configure-payment-methods/supported-methods/#supported-upi-apps',
      flows: 'intent',
    },
    {
      title: 'UPI ID/Number',
      docsUrl:
        'https://razorpay.com/docs/payments/payment-gateway/web-integration/standard/configure-payment-methods/understand-configuration/#upi',
      flows: 'collect',
    },
  ];

  const handleSave = () => {
    const updatedInstruments = [...currentConfig];
    const existingIndex = updatedInstruments.findIndex(
      (instrument) =>
        instrument.method === upiFeatureEnabledList.method &&
        (!instrument.apps || instrument.apps.length < 1),
    );

    if (existingIndex !== -1) {
      updatedInstruments[existingIndex] = upiFeatureEnabledList;
    } else {
      updatedInstruments.push(upiFeatureEnabledList);
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
      <ModalHeader title="UPI" />
      <ModalBody padding="spacing.0">
        <Box
          display="flex"
          flexDirection="column"
          gap="spacing.6"
          paddingY="spacing.6"
          paddingX="spacing.9"
        >
          {UpiConfigurationList.map((item, index) => (
            <ListItemCard
              key={index}
              backgroundColor="surface.background.gray.moderate"
              Title={
                <Text variant="body" weight="medium" size="medium">
                  {item.title}
                </Text>
              }
              accessibilityLabel="upi-qr-code"
              Description={
                <Link href={item.docsUrl} icon={ArrowRightIcon} iconPosition="right">
                  See documentation
                </Link>
              }
              RightComponent={
                <Switch
                  accessibilityLabel={`${item.title} switch`}
                  isChecked={upiFeatureEnabledList.flows?.includes(item.flows) ?? false}
                  onChange={({ isChecked }) => {
                    const newFlow = isChecked
                      ? [...(upiFeatureEnabledList?.flows || []), item.flows]
                      : (upiFeatureEnabledList.flows || []).filter(
                          (prevItem) => prevItem !== item.flows,
                        );
                    setUpiFeatureEnabledList({
                      method: upiFeatureEnabledList.method,
                      flows: newFlow,
                    });
                    _track.upiFlowToggled(item.flows, isChecked);
                  }}
                />
              }
            />
          ))}
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
