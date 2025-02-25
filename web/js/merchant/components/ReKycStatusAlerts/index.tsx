import React, { useEffect, useState } from 'react';
import {
  Badge,
  Box,
  Button,
  Divider,
  Heading,
  Modal,
  ModalBody,
  ModalHeader,
  Text,
  Alert,
  AlertCircleIcon,
} from '@razorpay/blade/components';
import kycImage from 'assets/onboarding/ncKyc.svg';
import { useStore } from '@federated/apps/shell/commonStore';

import { getItem, setItem } from 'common/utils/localStorage';

import { getModalContent, getBannerContent } from './constants';
import { ActionButton } from './styled';
import { track } from './utils';

export const ReKycStatusModal = () => {
  const user = useStore((state) => state.session.user);
  const notify = useStore((state) => state.showNotification);
  const [isOpen, setIsOpen] = useState(true);

  const widget = 'modal';
  const canPerformActions = user.isAdminOrOwner;
  const content = getModalContent(user);
  const isVisible = !!content;

  useEffect(() => {
    if (isVisible) {
      track(user, widget, 'loaded');
    }
  }, []);

  const onDismiss = () => {
    setIsOpen(false);
    track(user, widget, 'dismissed');
  };

  if (!isVisible) return null;

  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss}>
      <ModalBody padding="spacing.0">
        <ModalHeader />
        <img src={kycImage} alt="Update KYC Reminder" width="100%" />
        <Box display="grid" placeItems="center" gap="spacing.4" paddingTop="spacing.6">
          <Badge color="negative" size="large">
            Action Required
          </Badge>
          <Heading size="large">{content.title}</Heading>
        </Box>
        <Box display="grid" gap="spacing.7" padding="spacing.6">
          <Divider />
          <Text textAlign="center" color="surface.text.gray.subtle">
            {content.description}
          </Text>
          {content.action && canPerformActions ? (
            <ActionButton onClick={() => content.action?.onClick(user, widget, notify)}>
              {content.action.text}
            </ActionButton>
          ) : (
            <Button onClick={onDismiss}>Got it</Button>
          )}
        </Box>
      </ModalBody>
    </Modal>
  );
};

export const ReKycStatusBanner = ({ isRtux = false }) => {
  const user = useStore((state) => state.session.user);
  const notify = useStore((state) => state.showNotification);

  const widget = 'banner';
  const isDismissed = getItem('rekyc_banner_dismiss') === 'true';
  const canPerformActions = user.isAdminOrOwner;
  const content = getBannerContent(user);
  const isVisible = !!content && (!content.dismissible || !isDismissed);

  useEffect(() => {
    if (isVisible) {
      track(user, widget, 'loaded');
    }
  }, []);

  if (!isVisible) return null;

  const onDismiss = () => {
    setItem('rekyc_banner_dismiss', 'true');
    track(user, widget, 'dismissed');
  };

  return (
    <Box
      marginX={isRtux ? { base: 'spacing.0', m: 'spacing.6' } : 'spacing.6'}
      borderRadius="medium"
      overflow="hidden"
    >
      <Alert
        isDismissible={!!content.dismissible}
        emphasis="subtle"
        color={content.importance}
        onDismiss={onDismiss}
        isFullWidth
        icon={AlertCircleIcon}
        description={content.description}
        actions={
          content.action && canPerformActions
            ? {
                primary: {
                  text: content.action.text,
                  onClick: () => content.action?.onClick(user, widget, notify),
                },
              }
            : undefined
        }
      />
    </Box>
  );
};
