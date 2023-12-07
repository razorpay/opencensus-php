import React from 'react';
import { Box, Modal, ModalBody, ModalHeader } from '@razorpay/blade/components';

import { INTRO_VIDEO_EMBED_LINK } from 'merchant/views/PartnerDashboard/PartnerPlaybook/data';
type IntroVideoModalProps = {
  isOpen: boolean;
  setIsOpen: (isOpen: boolean) => void;
};
const IntroVideoModal = ({ isOpen, setIsOpen }: IntroVideoModalProps): JSX.Element => {
  return (
    // Note: zIndex for sidenav in the dashboard is 1111
    <Modal zIndex={1112} size="medium" isOpen={isOpen} onDismiss={() => setIsOpen(false)}>
      <ModalHeader title="Introduction to Partner Playbook" />
      <ModalBody>
        <Box display="flex" alignItems="center" flexDirection="column">
          <Box width="100%">
            <iframe
              width="100%"
              height="315"
              src={INTRO_VIDEO_EMBED_LINK}
              title="YouTube video player"
              frameBorder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowFullScreen
            />
          </Box>
        </Box>
      </ModalBody>
    </Modal>
  );
};
export default IntroVideoModal;
