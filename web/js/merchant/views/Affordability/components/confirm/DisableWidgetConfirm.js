import ModalHeader from 'common/ui/ModalHeader';
import { useEffect } from 'react';
import { AsyncButton } from 'react-async-button';
import { compose } from 'redux';
import rTracking from 'react-tracking';
import track from 'merchant/views/Affordability/AffordabilityWidget/PlanDetails/track';

const DisableWidgetConfirmModal = ({ closeModal, onConfirm }) => {
  useEffect(() => {
    track.disableWidgetConfirmRender();
  }, []);

  const handleClose = () => {
    closeModal();
  };

  return (
    <div className="widget-disble-modal">
      <ModalHeader title="Do you want to disable the widget" onCloseClick={handleClose} />
      <div className="modal-body">
        <p>Disabling the widget would remove the Affordability Widget from your website.</p>
        <p className="shopify-disable">
          For Shopify, please disable the widget from the Shopify App.
        </p>
        <div className="modal-footer">
          <AsyncButton
            onClick={onConfirm}
            type="submit"
            className="btn btn-border"
            text="Yes, disable"
          />
          <button onClick={closeModal} className="btn btn-primary">
            Don't disable
          </button>
        </div>
      </div>
    </div>
  );
};

export default compose(rTracking(() => window.rzpQ.component('DisableWidgetConfirmModal')))(
  DisableWidgetConfirmModal,
);
