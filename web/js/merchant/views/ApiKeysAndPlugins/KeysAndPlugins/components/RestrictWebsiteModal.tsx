import React from 'react';

interface RestrictWebsiteModalProps {
  closeModal: any;
}

const RestrictWebsiteModal = ({ closeModal }: RestrictWebsiteModalProps) => {
  return (
    <div className="add-link-modal-content">
      <button type="button" className="close" data-testid="close" onClick={closeModal}>
        <i className="i i-close" />
      </button>
      <div className="heading">You can add a new website/app URL later</div>
      <div>
        We're currently reviewing the website/app you've already shared. Once the review is
        complete, you'll be able to add a new website/app without any hassle.
      </div>
      <div className="form-group keys-btn">
        <button className="btn btn-primary btn-block center-block" onClick={closeModal}>
          Okay, Got it!
        </button>
      </div>
    </div>
  );
};

export default RestrictWebsiteModal;
