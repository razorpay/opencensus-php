import React from 'react';
import ModalHeader from 'common/ui/ModalHeader';

function ConfirmAddressUpdate({ handleUpdate, closeModal, updateOptOutSuccess, showNotification }) {
  const handleClick = async () => {
    try {
      const response = await handleUpdate();
      // Truly successful
      if (response.success === true) {
        updateOptOutSuccess(true);
        showNotification({
          type: 'success',
          message: 'Successfully updated',
        });
        closeModal();
      }
    } catch (error) {
      const msg = error.errors.join(' ');
      updateOptOutSuccess(false);
      showNotification({
        type: 'error',
        message: `${msg}`,
      });
    }
  };

  return (
    <div>
      <ModalHeader
        title="Are you sure you don’t want to update the address?"
        extraClass="gst-confirmation-header"
      />

      <div className="Modal__actions">
        <div className="modal-body rzp-gst-content">
          <div className="gst-help-block">
            <span>
              You have chosen not to update your business address to the same address mentioned on
              your GST certificate. In such cases, we will not be able to register your invoice on
              the GST portal, resulting in you losing the tax benefits of GST input credit. Click{' '}
              <a
                href="https://razorpay.com/docs/announcements/gst-changes/"
                target="_blank"
                rel="noopener noreferrer"
              >
                here
              </a>{' '}
              to learn more about GST E-Invoicing.
            </span>
          </div>
          <button className="btn btn-primary btn-block" onClick={handleClick}>
            I understand and don’t wish to update
          </button>
          <button className="btn btn-default btn-block btn-highlight" onClick={closeModal}>
            No, cancel
          </button>
        </div>
      </div>
    </div>
  );
}

export default ConfirmAddressUpdate;
