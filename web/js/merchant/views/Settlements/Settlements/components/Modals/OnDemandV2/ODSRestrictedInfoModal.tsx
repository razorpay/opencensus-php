import React from 'react';
import {
  Modal,
  ModalBody,
  Box,
  Heading,
  Button,
  Text,
  List,
  ListItem,
  CheckIcon,
  ZapIcon,
  IconButton,
  CloseIcon,
} from '@razorpay/blade/components';

import { formatAmount } from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandV2/helpers';

const TIPS = [
  'Maintain daily payment gateway transactions',
  'Keep refunds low',
  'Minimise bank chargebacks',
];

const ODSRestrictedInfoModal = ({
  isOpen,
  onDismiss,
  currency,
  maxLimit,
}: {
  isOpen: boolean;
  onDismiss: VoidFunction;
  currency: 'INR';
  maxLimit: number;
}) => {
  const handleDismiss = () => {
    onDismiss();
  };

  return (
    <Modal isOpen={isOpen} onDismiss={handleDismiss}>
      <ModalBody padding="spacing.0">
        {/* Header */}
        <Box
          borderTopLeftRadius="large"
          borderTopRightRadius="large"
          overflow="hidden"
          paddingX="spacing.6"
          paddingTop="spacing.6"
          paddingBottom="spacing.8"
          backgroundColor="surface.background.primary.intense"
        >
          <Box display="flex" justifyContent="flex-end">
            <Box
              backgroundColor="surface.background.gray.intense"
              display="inline-block"
              borderRadius="round"
              padding="spacing.2"
            >
              <IconButton
                size="large"
                icon={CloseIcon}
                accessibilityLabel="Close"
                onClick={handleDismiss}
              />
            </Box>
          </Box>
          <Box
            display="flex"
            marginTop="spacing.2"
            justifyContent="space-between"
            gap="spacing.4"
            alignItems="center"
          >
            <div>
              <Heading color="surface.text.staticWhite.normal" size="xlarge">
                Early Access
              </Heading>
              <Heading color="surface.text.staticWhite.normal" size="xlarge" weight="regular">
                to Instant Settlements
              </Heading>
            </div>
            <Box transform="scale(2)">
              <ZapIcon size="2xlarge" color="interactive.icon.staticWhite.muted" />
            </Box>
          </Box>
        </Box>
        {/* Body */}
        <Box padding="spacing.6">
          <Heading size="small">
            60% of your balance upto {formatAmount(maxLimit, currency)} instantly
          </Heading>
          <Text marginTop="spacing.3" color="surface.text.gray.subtle">
            You are enjoying early access to Instant Settlements and can settle a part of your
            balance.
          </Text>
          <Text marginTop="spacing.9" marginBottom="spacing.3" weight="semibold">
            To unlock 100% settlements,
          </Text>
          <List marginBottom="spacing.9" icon={CheckIcon}>
            {TIPS.map((tip, idx) => (
              <ListItem key={idx}>{tip}</ListItem>
            ))}
          </List>
          <Button isFullWidth onClick={handleDismiss}>
            Understood
          </Button>
        </Box>
      </ModalBody>
    </Modal>
  );
};

export default ODSRestrictedInfoModal;
