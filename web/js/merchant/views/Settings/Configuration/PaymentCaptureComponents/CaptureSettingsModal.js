import ModalHeader from 'common/ui/ModalHeader';
import { useState } from 'react';

function CaptureSettingsModal({ closeModal, handleDone }) {
  const [selectedRow, setselectedRow] = useState(null);

  return (
    <div className="capture-settings">
      <ModalHeader
        title="Capture Settings"
        onCloseClick={() => {
          closeModal();
        }}
      />
      <div className="content content-small">
        <div className="section">
          <div className="section-action">
            <input
              type="radio"
              onClick={() => {
                setselectedRow(1);
              }}
              checked={selectedRow === 1}
            />
          </div>
          <div className="section-content">
            <div className="title">Automatic Capture</div>
            <div className="description">Payments will be captured by Razorpay automatically</div>
          </div>
        </div>
        <div className="section">
          <div className="section-action">
            <input
              type="radio"
              onClick={() => {
                setselectedRow(2);
              }}
              checked={selectedRow === 2}
            />
          </div>
          <div className="section-content">
            <div className="title">Manual Capture</div>
            <div className="description">
              Payments have to be captured manually by you via the API or the dashboard
            </div>
          </div>
        </div>
        <div className="actions">
          <button
            className="btn btn-primary"
            disabled={!selectedRow}
            onClick={() => {
              handleDone(selectedRow === 1 ? 'automatic' : 'manual');
            }}
          >
            Done
          </button>
        </div>
      </div>
    </div>
  );
}

export default CaptureSettingsModal;
