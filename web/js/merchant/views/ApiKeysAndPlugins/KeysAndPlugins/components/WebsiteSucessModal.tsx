import React from 'react';

interface WebsiteSucessModalProps {
  closeModal: any;
}

const WebsiteSucessModal = ({ closeModal }: WebsiteSucessModalProps) => {
  return (
    <div className="add-link-modal-content">
      <button type="button" className="close" data-testid="close" onClick={closeModal}>
        <i className="i i-close" />
      </button>
      <div className="heading">Website/App URL added successfully</div>
      <div>
        Our team will verify your website/app so that you can start collecting payments on it. We'll
        contact you via email if we need further information.
      </div>
      <div className="form-group keys-btn">
        <button className="btn btn-primary btn-block center-block" onClick={closeModal}>
          Okay, Got it!
        </button>
      </div>
    </div>
  );
};

export default WebsiteSucessModal;
