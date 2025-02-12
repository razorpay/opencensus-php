import React, { useEffect } from 'react';

import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';

import { classList } from 'common/utils/rzp-utils';

/* Position-awared Modal which opens over the field being edited / in the middle of screen */
const CreatorModal = ({ children, overElement, className, onClose, allowScroll }) => {
  // If not available, then opens modal in center of screen

  /*
    Adding class on paymentpage-container to handle CSS on 
    mobile based on whether base forms are open or not
  */
  useEffect(() => {
    document.getElementById('paymentpage-container')?.classList.add('creator-modal-open');

    return () => {
      document.getElementById('paymentpage-container')?.classList.remove('creator-modal-open');
    };
  }, []);

  const modalContent = <ModalContent className="paymentlinks-creator">{children}</ModalContent>;

  return overElement ? (
    <React.Fragment>
      <div className={className} />
      <Modal className={className} showCloseBtn={false} allowScroll={allowScroll}>
        <div className="mimic-expand" />
        {modalContent}
      </Modal>
    </React.Fragment>
  ) : (
    <ModalMask maskClosable={false} className="payment-pages-v3-creator">
      <Modal
        onClose={onClose}
        className={classList('animate-appear', className)}
        showCloseBtn
        allowScroll={allowScroll}
      >
        {modalContent}
      </Modal>
    </ModalMask>
  );
};

export default CreatorModal;
