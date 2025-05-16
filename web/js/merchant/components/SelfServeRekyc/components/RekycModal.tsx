import React from 'react';
import { Box, Button, Heading, Text } from '@razorpay/blade/components';

import useModalComponents from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/hooks/useModalComponents';

import BadgeComponent from 'merchant/components/SelfServeRekyc/components/Badge';
import { ModalImageWrapper, ModalWrapper } from 'merchant/components/SelfServeRekyc/styledComponents/RekycModal';

import { RekycModalProps } from 'merchant/components/SelfServeRekyc/types';

const RekycModal = (props: RekycModalProps) => {

  const {
    onDismiss,
    isMobile,
    isOpen,
    modalInfo,
    daysFromDeadline,
    deadlineDate,
    rekycUrl,
    onCtaClick,
  } = props;

  const { Modal, ModalBody, ModalFooter, ModalHeader } = useModalComponents(isMobile);

  const {
    imageSrc,
    heading,
    description,
    ctaText,
    hideCta,
    dynamicHeading,
    badgeText,
    dynamicDescription
  } = modalInfo

  return (
    <ModalWrapper className="modal-wrapper">
    <Modal
      isOpen={isOpen}
      size={isMobile ? "medium" :"small"}
      snapPoints={[0.85, 0.95, 1]}
      onDismiss={onDismiss}
      zIndex={1002}
    >
      <ModalHeader title=""/>
      <ModalBody padding="0px">
        <ModalImageWrapper src={imageSrc} alt="notice-kyc"/>
        <Box display="flex" flexDirection="column" gap={isMobile ? 'spacing.5' : 'spacing.6'} paddingX='spacing.6'>
          <Box
            display='flex'
            flexDirection='column'
            gap='spacing.4'
            alignItems='center'
            justifyContent='center'
          >
            <BadgeComponent daysFromDeadline={daysFromDeadline} badgeText={badgeText} chipType="negative"/>
            <Heading size="medium" weight='semibold' textAlign='center'>
              {dynamicHeading && (typeof heading !== 'string') ? heading(deadlineDate) : heading}
            </Heading>
          </Box>
          <Text size="medium" marginBottom={hideCta ? 'spacing.9' : 'spacing.7'} textAlign='center'>
            {dynamicDescription && (typeof description !== 'string') ?  description(deadlineDate): description}
          </Text>
        </Box>
      </ModalBody>
      {
        !hideCta ?
        <ModalFooter>
          <Box display="flex" justifyContent="flex-end" alignItems="center">
            <Button
              variant="primary"
              color="primary"
              size="medium"
              isFullWidth={true}
              onClick={() => onCtaClick(rekycUrl)}
            >
              {ctaText}
            </Button>
          </Box>
        </ModalFooter> : null
      }
    </Modal>
    </ModalWrapper>
  );
};

export default RekycModal;
