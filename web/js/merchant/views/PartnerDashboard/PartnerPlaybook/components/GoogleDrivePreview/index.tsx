import React from 'react';
import { Box, Modal, ModalBody, ModalHeader } from '@razorpay/blade/components';

import { ProgramItem } from 'merchant/views/PartnerDashboard/PartnerPlaybook/types';

type GoogleDriveModalProps = {
  item?: null | ProgramItem;
  isOpen: boolean;
  closePreview: () => void;
};
const GoogleDrivePreview = ({ item, isOpen, closePreview }: GoogleDriveModalProps): JSX.Element => {
  return (
    <Modal size="large" isOpen={isOpen} onDismiss={closePreview}>
      <ModalHeader title={item?.title} />
      <ModalBody>
        <Box display="flex" alignItems="center" flexDirection="column">
          <Box width="100%">
            {item?.preview_url ? (
              <iframe
                src={item.preview_url}
                width="100%"
                height="440px"
                allowFullScreen
                allow="autoplay"
              />
            ) : (
              'Loading...'
            )}
          </Box>
        </Box>
      </ModalBody>
    </Modal>
  );
};
export default GoogleDrivePreview;
