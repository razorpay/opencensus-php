import React, { useEffect, useState } from 'react';
import {
  ModalBody,
  ModalFooter,
  Box,
  Heading,
  TextInput,
  Button,
  Alert,
  Text,
  IconButton,
  CloseIcon,
  Divider,
} from '@razorpay/blade/components';

import { updateLinkAccount } from '../service';

import { CreateOptimizerLinkAccountPayload } from '../types';

export const ChangeAccountDetails = ({
  closeModal,
  accountId,
  providerId,
  providerName,
  gatewayAccountId,
  showNotification,
  updateDetails,
}: {
  closeModal: () => void;
  accountId: string;
  providerId: string;
  providerName: string;
  gatewayAccountId: string;
  showNotification: (notification: { type: string; message: string }) => void;
  updateDetails: (providerId: string, gatewayAccountId: string) => void;
}): JSX.Element => {
  const [selectedProvider, setSelectedProvider] = useState<string>('');
  const [gatewayId, setGatewayId] = useState<string>('');
  const [isSaving, setIsSaving] = useState<boolean>(false);
  const [changeAccountError, setChangeAccountError] = useState<string>('');

  useEffect(() => {
    setSelectedProvider(providerId);
    setGatewayId(gatewayAccountId);
  }, [providerId, gatewayAccountId]);

  const saveAccount = () => {
    setIsSaving(true);
    const payload: Partial<CreateOptimizerLinkAccountPayload> = {
      provider_id: selectedProvider,
      gateway_account_id: gatewayId,
      provider_name: providerName,
    };
    updateLinkAccount(accountId, payload)
      .then((res) => {
        if (res.success) {
          showNotification({
            type: 'success',
            message: 'Account details updated successfully.',
          });
          updateDetails(selectedProvider, gatewayId);
          closeModal();
        }
      })
      .catch(({ errors }) => {
        if (errors) {
          setChangeAccountError(errors[0]);
        }
      })
      .finally(() => {
        setIsSaving(false);
      });
  };

  return (
    <Box width="450px">
      <Box
        marginY="spacing.5"
        paddingX="spacing.5"
        display="flex"
        flexDirection="row"
        justifyContent="space-between"
      >
        <Heading>Change details</Heading>
        <IconButton accessibilityLabel="close" icon={CloseIcon} onClick={closeModal} />
      </Box>
      <Divider />
      <ModalBody>
        {changeAccountError && (
          <Alert
            description={changeAccountError}
            isFullWidth
            isDismissible
            color="negative"
            marginBottom="spacing.7"
          />
        )}
        <Box display="flex" flexDirection="column" gap="spacing.6">
          <Box>
            <Text size="small" color="surface.text.gray.subtle" weight="semibold">
              Provider
            </Text>
            <Box
              borderWidth="thin"
              borderColor="surface.border.gray.normal"
              paddingY="spacing.3"
              paddingX="spacing.4"
              borderRadius="medium"
              marginTop="spacing.3"
            >
              {providerName}
            </Box>
          </Box>
          <TextInput
            label="Gateway linked account ID"
            name="gateway_id"
            value={gatewayId}
            onChange={({ value }) => setGatewayId(value as string)}
            isRequired
          />
        </Box>
      </ModalBody>
      <ModalFooter>
        <Button
          isFullWidth
          onClick={saveAccount}
          isDisabled={!(selectedProvider && gatewayId)}
          isLoading={isSaving}
        >
          Save
        </Button>
      </ModalFooter>
    </Box>
  );
};
