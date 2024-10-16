import React from 'react';
import {
  Box,
  Text,
  Button,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
} from '@razorpay/blade/components';

import IntoView from 'common/ui/IntoView';
import TextHighlighter from 'common/ui/TextHighlighter';
import { EmailLessCheckoutConfigOptions } from 'merchant/reducers/config';
import { useCheckoutConfig } from 'merchant/views/Settings/Configuration/CheckoutConfig/context';
import { CHECKOUT_EMAIL_SETTINGS } from 'merchant/views/Settings/Configuration/deeplink-constants';

const CHECKOUT_EMAIL_CONFIG_OPTIONS = [
  {
    name: 'No (Default)',
    code: EmailLessCheckoutConfigOptions.NO,
  },
  {
    name: 'As an optional field',
    code: EmailLessCheckoutConfigOptions.OPTIONAL,
  },
  {
    name: 'As a mandatory field',
    code: EmailLessCheckoutConfigOptions.REQUIRED,
  },
];

const EmailSettings = () => {
  const {
    values,
    isEmailRequiredModalOpen,
    handleEmailChange,
    handleCloseEmailRequiredModal,
    handleConfirmEmailRequired,
  } = useCheckoutConfig();

  const handleSelectChange = (evt: { values: string[] }) => {
    handleEmailChange(evt.values[0]);
  };

  return (
    <IntoView hashedWith={CHECKOUT_EMAIL_SETTINGS}>
      <Box display="flex" flexDirection="column" gap="spacing.2">
        <Box>
          <Text weight="semibold" color="surface.text.gray.subtle">
            <TextHighlighter hashedWith={CHECKOUT_EMAIL_SETTINGS}>
              Collect email address from users on Checkout page
            </TextHighlighter>
          </Text>
        </Box>
        <Dropdown>
          <SelectInput label="" placeholder="" value={values.email} onChange={handleSelectChange} />
          <DropdownOverlay>
            <ActionList>
              {CHECKOUT_EMAIL_CONFIG_OPTIONS.map((config) => (
                <ActionListItem key={config.code} title={config.name} value={config.code} />
              ))}
            </ActionList>
          </DropdownOverlay>
        </Dropdown>
      </Box>
      <Modal isOpen={isEmailRequiredModalOpen} onDismiss={handleCloseEmailRequiredModal}>
        <ModalHeader title="Are you sure you want to collect the customer's e-mail address on checkout?" />
        <ModalBody>
          <Text color="surface.text.gray.subtle">
            Collecting additional information from the user that is not necessary might result in
            increased drop-off on checkout
          </Text>
        </ModalBody>
        <ModalFooter>
          <Box display="flex" gap="spacing.5" justifyContent="flex-end" width="100%">
            <Button onClick={handleCloseEmailRequiredModal} variant="tertiary">
              No, don&apos;t collect
            </Button>
            <Button onClick={handleConfirmEmailRequired}>Yes, collect email</Button>
          </Box>
        </ModalFooter>
      </Modal>
    </IntoView>
  );
};

export default EmailSettings;
