import React, { useState, useEffect } from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Box,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
  ActionListItemIcon,
  PlusIcon,
  TextInput,
  Button,
  Alert,
} from '@razorpay/blade/components';

import { SelectProvider } from './SelectProvider';
import { getAccountName, getProviderName } from '../utils';
import { createLinkAccount, updateLinkAccount } from '../service';

import { Provider } from 'merchant/views/Optimizer/types';
import { OptimizerAccount, CreateOptimizerLinkAccountPayload } from '../types';
import { trackOptimizerAccountEvents } from '../track';

export const OptimizerLinkAccount = ({
  isOpen,
  closeModal,
  optimizerAccounts,
  providers,
  accountLinkedSuccess,
}: {
  isOpen: boolean;
  closeModal: () => void;
  optimizerAccounts: OptimizerAccount[];
  providers: Provider[];
  accountLinkedSuccess: (accountName: string) => void;
}): JSX.Element => {
  const [accountId, setAccountId] = useState<string>('');
  const [accountName, setAccountName] = useState<string>('');
  const [selectedProvider, setSelectedProvider] = useState<string>('');
  const [gatewayId, setGatewayId] = useState<string>('');
  const [isLinking, setIsLinking] = useState<boolean>(false);
  const [linkAccountError, setLinkAccountError] = useState<string>('');
  const [routeProviders, setRouteProviders] = useState<Provider[]>([]);

  useEffect(() => {
    setRouteProviders(providers.filter((provider) => provider.Gateway_details?.optimizer_route));
  }, [providers]);

  const handleClose = () => {
    setAccountId('');
    setAccountName('');
    setSelectedProvider('');
    setGatewayId('');
    setIsLinking(false);
    setLinkAccountError('');
    closeModal();
  };

  const handleAccountIdChange = ({ values }: { values: string[] }) => {
    if (values[0] !== 'new') {
      const accountName = getAccountName(optimizerAccounts, values[0]);
      setAccountName(accountName);
    }
    setAccountId(values[0]);
  };

  const linkAccount = () => {
    trackOptimizerAccountEvents({
      objectName: 'Link button',
      actionName: 'clicked',
      screen: 'Optimizer Accounts - Link Modal',
    });
    setLinkAccountError('');
    setIsLinking(true);
    const payload: Partial<CreateOptimizerLinkAccountPayload> = {
      provider_id: selectedProvider,
      gateway_account_id: gatewayId,
      provider_name: getProviderName(providers, selectedProvider),
    };
    if (accountId === 'new') {
      payload.account_name = accountName;
      createLinkAccount(payload as CreateOptimizerLinkAccountPayload)
        .then((res) => {
          if (res.success) {
            accountLinkedSuccess(accountName);
            handleClose();
          }
        })
        .catch(({ errors }) => {
          if (errors) {
            setLinkAccountError(errors[0]);
          }
        })
        .finally(() => {
          setIsLinking(false);
        });
    } else if (accountId !== '') {
      updateLinkAccount(accountId, payload)
        .then((res) => {
          if (res.success) {
            accountLinkedSuccess(accountName);
            handleClose();
          }
        })
        .catch(({ errors }) => {
          if (errors) {
            setLinkAccountError(errors[0]);
          }
        })
        .finally(() => {
          setIsLinking(false);
        });
    }
  };

  return (
    <Modal isOpen={isOpen} onDismiss={handleClose}>
      <ModalHeader title="Link Account" />
      <ModalBody>
        {linkAccountError && (
          <Alert
            description={linkAccountError}
            isFullWidth
            isDismissible
            color="negative"
            marginBottom="spacing.7"
          />
        )}
        <Box display="flex" flexDirection="column" gap="spacing.6">
          <Dropdown selectionType="single" _width="100%">
            <SelectInput
              name="account_id"
              label="Account ID"
              labelPosition="top"
              placeholder="Ex: acc_PA3lV7lN4vuMEm"
              onChange={handleAccountIdChange}
              isRequired
            />
            <DropdownOverlay>
              <ActionList>
                <ActionListItem
                  leading={<ActionListItemIcon icon={PlusIcon} />}
                  title="Create new account"
                  description="This will generate a new ID once you finish linking"
                  value="new"
                />
                {optimizerAccounts.map((account) => (
                  <ActionListItem key={account.id} title={account.id} value={account.id} />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
          <TextInput
            label="Account Name"
            name="account_name"
            value={accountName}
            onChange={({ value }) => setAccountName(value as string)}
            helpText="The business/individual name for the account, which will appear on all reports"
            isDisabled={accountId !== 'new'}
            isRequired
          />
          <SelectProvider
            providers={routeProviders}
            selectedProvider={selectedProvider}
            setSelectedProvider={setSelectedProvider}
          />
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
          onClick={linkAccount}
          isDisabled={!(accountId && accountName && selectedProvider && gatewayId)}
          isLoading={isLinking}
        >
          Link
        </Button>
      </ModalFooter>
    </Modal>
  );
};
