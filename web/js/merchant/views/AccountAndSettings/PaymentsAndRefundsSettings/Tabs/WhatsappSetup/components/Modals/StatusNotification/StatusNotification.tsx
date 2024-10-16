import React, { useEffect } from 'react';
import {
  Box,
  ModalHeader as BladeModalHeader,
  ModalBody as BladeModalBody,
  Modal as BladeModal,
  BottomSheet,
  Text,
  BottomSheetHeader,
  BottomSheetBody,
  Heading,
} from '@razorpay/blade/components';
import InitiateCheck from 'assets/initiate-check.svg';
import SuccessCheck from 'assets/success-check.svg';

import { useMobile } from 'common/hooks/useMobile';
import { defaultSnapPoints } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/constants';
import { whatsappAccountSetupAnalyticsTrack } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/utils';

export const Config = {
  initiate: {
    title: 'We’ve initiated the integration',
    subtext: 'Integration is in progress',
    icon: InitiateCheck,
  },
  success: {
    title: 'Set-up completed successfully!',
    subtext: 'We’ve verified your details. Notifcations are now enabled for your payment links',
    icon: SuccessCheck,
  },
};

const StatusNotification = ({
  modalState: { isOpen, info },
  onClose,
  businessProvider,
}): JSX.Element => {
  const { type } = info;
  const { title, subtext, icon } = Config[type];
  const isMobile = useMobile();

  useEffect(() => {
    whatsappAccountSetupAnalyticsTrack({
      objectName: `WA Setup ${type === 'initiate' ? 'Initiated' : 'Completed Successfully'} Banner`,
      actionName: 'Displayed',
      properties: {
        selectedBusinessAccount: businessProvider?.title,
      },
    });
  }, []);

  const handleDismiss = (): void => {
    if (isMobile) {
      document.body.style.overflow = 'unset';
    }
    onClose({ action: 'close' });
  };

  const { Modal, ModalHeader, ModalBody } = isMobile
    ? {
        Modal: BottomSheet,
        ModalHeader: BottomSheetHeader,
        ModalBody: BottomSheetBody,
      }
    : {
        Modal: BladeModal,
        ModalHeader: BladeModalHeader,
        ModalBody: BladeModalBody,
      };

  return (
    <Modal isOpen={isOpen} onDismiss={handleDismiss} snapPoints={defaultSnapPoints}>
      <ModalHeader />
      <ModalBody>
        <Box
          display="flex"
          flexDirection="column"
          alignItems="center"
          padding={['spacing.6', 'spacing.0', 'spacing.6', 'spacing.0']}
          gap="spacing.8"
        >
          <img src={icon} alt={`${type}_check`} />
          <Box display="flex" flexDirection="column" alignItems="center" gap="spacing.4">
            <Heading size="large">{title}</Heading>
            <Text size="large" textAlign="center" color="surface.text.gray.muted">
              {subtext}
            </Text>
          </Box>
        </Box>
      </ModalBody>
    </Modal>
  );
};

export default StatusNotification;
