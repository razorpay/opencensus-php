import React from 'react';
import {
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Button,
  Box,
  List,
  ListItem,
  BottomSheet,
  BottomSheetHeader,
  BottomSheetBody,
  BottomSheetFooter,
} from '@razorpay/blade/components';
import { formatDateTime } from '@razorpay/i18nify-js';
import { isMobileDevice } from '@libs/shared-utils';

interface PlotlineRewardsPopupProps {
  isOpen: boolean;
  onDismiss: () => void;
  onPrimaryClick: () => void;
  onSecondaryClick: () => void;
  expiryDate: Date | null;
}

const PlotlineRewardsPopup = ({
  isOpen,
  onDismiss,
  onPrimaryClick,
  onSecondaryClick,
  expiryDate,
}: PlotlineRewardsPopupProps) => {
  const validTill = expiryDate ? formatDateTime(expiryDate, { locale: 'en-US' }) : '';

  return isMobileDevice() ? (
    <BottomSheet isOpen={isOpen} onDismiss={onDismiss} snapPoints={[0.5, 0.7, 0.85]}>
      <BottomSheetHeader
        title="Reward Details"
        subtitle="Rewards applicable for first 5 transactions"
      />
      <BottomSheetBody>
        <List>
          <ListItem>Every time you receive a payment, you stand a chance to win a reward.</ListItem>
          <ListItem>Campaign valid till {validTill}.</ListItem>
          <ListItem>Access your Rewards Page from the profile dropdown</ListItem>
        </List>
      </BottomSheetBody>
      <BottomSheetFooter>
        <Box display="flex" flexDirection="column" gap="spacing.3">
          <Button variant="primary" onClick={onPrimaryClick}>
            Go to Rewards Page
          </Button>
          <Button variant="tertiary" onClick={onSecondaryClick}>
            View T&Cs
          </Button>
        </Box>
      </BottomSheetFooter>
    </BottomSheet>
  ) : (
    <Modal isOpen={isOpen} onDismiss={onDismiss}>
      <ModalHeader title="Reward Details" subtitle="Rewards applicable for first 5 transactions" />
      <ModalBody>
        <List>
          <ListItem>Every time you receive a payment, you stand a chance to win a reward.</ListItem>
          <ListItem>Campaign valid till {validTill}.</ListItem>
          <ListItem>Access your Rewards Page from the profile dropdown.</ListItem>
        </List>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button variant="tertiary" onClick={onSecondaryClick}>
            View T&Cs
          </Button>
          <Button variant="primary" onClick={onPrimaryClick}>
            Go to Rewards Page
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default PlotlineRewardsPopup;
