import React from 'react';
import Button from 'common/new-ui/Button';
import ModalHeader from 'common/ui/ModalHeader';

const RequestSubmittedModal = ({ closeModal }) => (
  <div>
    <ModalHeader title="Request Submitted" onCloseClick={closeModal} />
    <div className="modal-body">
      <div>
        We have received your request for enabling international payments for Payment Pages, Payment
        Links & Invoices.
      </div>
      <br />
      <div>We will get back to you within 5-7 working days with an update on your request</div>
      <div className="Modal__actions text-right">
        <Button.Primary onClick={closeModal}>Got it</Button.Primary>
      </div>
    </div>
  </div>
);

export default RequestSubmittedModal;
