import React, { useState } from 'react';
import {
  Button,
  Box,
  ModalHeader as BladeModalHeader,
  ModalBody as BladeModalBody,
  ModalFooter as BladeModalFooter,
  Modal as BladeModal,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  SelectInput,
  BottomSheet,
  BottomSheetHeader,
  BottomSheetBody,
  BottomSheetFooter,
} from '@razorpay/blade/components';
import { useMobile } from 'common/hooks/useMobile';
import {
  SERVICE_PROVIDER_LOGIN_HREF,
  defaultSnapPoints,
  BusinessServiceProvider,
} from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/constants';
import { whatsappAccountSetupAnalyticsTrack } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/utils';

const AccountLink = ({ modalState: { isOpen }, onClose, setModalState }): JSX.Element => {
  const [selectedProvider, setProvider] = useState(BusinessServiceProvider[0].value);
  const isMobile = useMobile();
  const handleDismiss = (): void => {
    if (isMobile) {
      document.body.style.overflow = 'unset';
    }
    setProvider('');
    onClose({ action: 'close' });
  };

  const handleSelect = ({ values }): void => setProvider(values?.[0]);

  const handleContinue = (): void => {
    // istanbul ignore else
    if (selectedProvider) {
      const redirectionUrl = SERVICE_PROVIDER_LOGIN_HREF[selectedProvider];
      whatsappAccountSetupAnalyticsTrack({
        objectName: 'WA Business Account',
        actionName: 'Selected',
        properties: {
          selectedBusinessAccount: selectedProvider,
        },
      });
      setProvider('');
      setModalState((prevState) => ({
        ...prevState,
        activeView: 'statusNotification',
        isOpen: true,
        info: {
          type: 'initiate',
        },
      }));
      window.open(redirectionUrl, '_blank');
    }
  };

  const { Modal, ModalHeader, ModalBody, ModalFooter } = isMobile
    ? {
        Modal: BottomSheet,
        ModalHeader: BottomSheetHeader,
        ModalBody: BottomSheetBody,
        ModalFooter: BottomSheetFooter,
      }
    : {
        Modal: BladeModal,
        ModalHeader: BladeModalHeader,
        ModalBody: BladeModalBody,
        ModalFooter: BladeModalFooter,
      };

  return (
    <Modal zIndex={1112} isOpen={isOpen} onDismiss={handleDismiss} snapPoints={defaultSnapPoints}>
      <ModalHeader title="Link your existing Whatsapp Account" />
      <ModalBody>
        <Dropdown selectionType="single">
          <SelectInput
            label="Select your Business Service Provider"
            placeholder="Select"
            name="serviceProvider"
            onChange={handleSelect}
            defaultValue={selectedProvider}
          />
          <DropdownOverlay>
            <ActionList>
              {BusinessServiceProvider.map(({ title, value }) => (
                <ActionListItem title={title} value={value} key={value} />
              ))}
            </ActionList>
          </DropdownOverlay>
        </Dropdown>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end" width="100%" gap="spacing.5">
          <Button variant="tertiary" onClick={handleDismiss}>
            Cancel
          </Button>
          <Button onClick={handleContinue} isDisabled={!selectedProvider}>
            Continue
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default AccountLink;
