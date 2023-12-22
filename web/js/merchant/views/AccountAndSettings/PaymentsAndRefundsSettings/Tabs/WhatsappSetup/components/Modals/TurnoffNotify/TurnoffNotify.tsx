import React, { useState } from 'react';
import {
  Button,
  Box,
  ModalHeader as BladeModalHeader,
  ModalBody as BladeModalBody,
  ModalFooter as BladeModalFooter,
  Modal as BladeModal,
  BottomSheet,
  Text,
  BottomSheetHeader,
  BottomSheetBody,
  BottomSheetFooter,
} from '@razorpay/blade/components';
import { useMobile } from 'common/hooks/useMobile';
import { defaultSnapPoints } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/constants';
import { whatsappAccountSetupAnalyticsTrack } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/utils';

const TurnoffNotify = ({
  modalState: { isOpen, info },
  onClose,
  updateNotificationFeature,
}): JSX.Element => {
  const isMobile = useMobile();
  const [isLoading, setIsLoading] = useState(false);

  const handleDismiss = (): void => {
    if (isMobile) {
      document.body.style.overflow = 'unset';
    }
    onClose({ action: 'close' });
  };

  const handleContinue = (): void => {
    setIsLoading(true);
    whatsappAccountSetupAnalyticsTrack({
      objectName: 'WA Toggle All PLs to Whatsapp Confirm',
      actionName: 'Clicked',
    });
    updateNotificationFeature(info.isChecked, true);
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
      <ModalHeader title="Turn off notifications?" />
      <ModalBody>
        <Text type="subdued" size="large">
          If you turn off notifications, customers will no longer receive any updates for Payment
          Links on Whatsapp
        </Text>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end" width="100%" gap="spacing.5">
          <Button variant="secondary" onClick={handleDismiss}>
            Cancel
          </Button>
          <Button variant="primary" onClick={handleContinue} isLoading={isLoading}>
            Yes, I understand
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default TurnoffNotify;
