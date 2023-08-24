import React, { useState } from 'react';
import { Modal, ModalBody, ModalHeader } from '@razorpay/blade/components';

import FAQContent from './FAQContent';
type FAQModalProps = {
  getTriggerComponent: (args: { onClick: () => void }) => React.ReactNode;
  inviteFlow: string;
  productType: string;
};

const FAQModal = ({ getTriggerComponent, inviteFlow, productType }: FAQModalProps): JSX.Element => {
  const [isFaqModalOpen, setFaqModalOpen] = useState(false);
  return (
    <>
      {getTriggerComponent({ onClick: () => setFaqModalOpen(true) })}
      <Modal
        zIndex={1112}
        isOpen={isFaqModalOpen}
        onDismiss={() => setFaqModalOpen(false)}
        size="small"
      >
        <ModalHeader title="Perform KYC on behalf of your client" />
        <ModalBody>
          <FAQContent inviteFlow={inviteFlow} productType={productType} noTopMargin />
        </ModalBody>
      </Modal>
    </>
  );
};
export default FAQModal;
