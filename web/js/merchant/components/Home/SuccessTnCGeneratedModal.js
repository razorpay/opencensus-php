import React from 'react';
import { Link } from 'react-router-dom';
import Button from 'common/new-ui/Button';
import { Modal } from 'common/new-ui/Modal';

const SuccessTnCGeneratedModal = ({ onCloseModal, data }) => {
  return (
    <Modal
      className="pan-status-modal"
      onClose={onCloseModal}
      style={{ transform: 'translate(-50%, 10%)' }}
      showCloseBtn={false}
    >
      <div className="modal-header completed">
        <h1>Page Generated Successfully</h1>
      </div>
      <div className="modal-body">
        <div className="modal-description tnc-body">
          <div className="tnc-success">
            <p className="tnc-success__text">
              You can use this link to visit the page and edit the Terms and Conditions if required.
            </p>
            <div className="tnc-success__link">
              <a href={data.link} target="_blank" rel="noreferrer noopener">
                <span>{data.link}</span> <i class="i i-external-link" />
              </a>
            </div>
            <p style={{ color: '#162f5661' }}>
              In case you change your company policies in future, you can make the changes to the
              page from{' '}
              <Link to="/profile" onClick={onCloseModal}>
                My account
              </Link>{' '}
              section
            </p>
            <div className="warning-text">
              This page link will be displayed on the apps that you would use to accept payments.
              (payment links, payment pages etc )
            </div>
            <Button.Primary
              onClick={() => {
                onCloseModal();
                location.href = '/app/dashboard';
              }}
              className="backto-dashboard"
            >
              Back to dashboard
            </Button.Primary>
          </div>
        </div>
      </div>
    </Modal>
  );
};

export default SuccessTnCGeneratedModal;
