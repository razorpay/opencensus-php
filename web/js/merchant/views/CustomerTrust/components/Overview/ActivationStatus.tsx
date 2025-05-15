import React, { useState } from 'react';
import {
  Badge,
  Box,
  IconButton,
  Menu,
  MenuItem,
  MenuOverlay,
  MoreVerticalIcon,
  Text,
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Button,
} from '@razorpay/blade/components';
import { OnboardingStatus } from '../../types';

export const ActivationStatus = ({ onboardingStatus }: { onboardingStatus: OnboardingStatus }) => {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isDeactivateModalOpen, setIsDeactivateModalOpen] = useState(false);

  const handleCloseModal = () => {
    setIsDeactivateModalOpen(false);
  };

  return (
    <>
      <Modal isOpen={isDeactivateModalOpen} onDismiss={handleCloseModal} size="small">
        <ModalHeader title="Cannot deactivate directly" />
        <ModalBody>
          <Text size="small" color="surface.text.gray.muted">
            This feature cannot be disabled directly to maintain customer trust. To disable Buyer
            Protection, please write a mail to this email ID: magicsales@razorpay.com
          </Text>
        </ModalBody>
        <ModalFooter>
          <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
            <Button
              variant="secondary"
              onClick={() => {
                // copy email id to clipboard
                navigator.clipboard.writeText('magicsales@razorpay.com');
              }}
            >
              Copy Email ID
            </Button>
            <Button onClick={handleCloseModal}>Got it</Button>
          </Box>
        </ModalFooter>
      </Modal>

      <Box
        display="flex"
        alignItems="center"
        justifyContent="space-between"
        paddingX="24px"
        paddingY="16px"
        borderRadius="medium"
        borderColor="surface.border.gray.muted"
        borderWidth="thin"
      >
        <Text weight="semibold" color="surface.text.gray.normal">
          {onboardingStatus === 'pending'
            ? 'Your request is processing. Buyer protection will be active shortly.'
            : 'Buyer protection is active on your checkout'}
        </Text>
        <Box display="flex" alignItems="center" gap="spacing.4">
          <Badge
            color={onboardingStatus === 'pending' ? 'notice' : 'positive'}
            size="large"
            emphasis="subtle"
          >
            {onboardingStatus === 'pending' ? 'Pending' : 'Active'}
          </Badge>

          {onboardingStatus === 'activated' && (
            <Menu
              isOpen={isMenuOpen}
              onOpenChange={({ isOpen: value }) => {
                setIsMenuOpen(value);
              }}
            >
              <IconButton
                icon={MoreVerticalIcon}
                emphasis="intense"
                accessibilityLabel="options"
                onClick={() => {
                  setIsMenuOpen(true);
                }}
              />
              <MenuOverlay>
                <MenuItem
                  title="Deactivate Buyer Protection"
                  onClick={() => {
                    setIsMenuOpen(false);
                    setIsDeactivateModalOpen(true);
                  }}
                />
                <MenuItem title="Contact support" />
              </MenuOverlay>
            </Menu>
          )}
        </Box>
      </Box>
    </>
  );
};
