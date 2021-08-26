import ModalHeader from 'common/ui/ModalHeader';
import { useState } from 'react';

function CaptureSettingsModal({ closeModal, handleDone }) {
  const [selectedRow, setselectedRow] = useState(null);

  return (
    <div class="capture-settings">
      <ModalHeader
        title="Capture Settings"
        onCloseClick={() => {
          closeModal();
        }}
      />
      <div class="content content-small">
        <div class="section">
          <div class="section-action">
            <input
              type="radio"
              onClick={() => {
                setselectedRow(1);
              }}
              checked={selectedRow === 1}
            />
          </div>
          <div class="section-content">
            <div class="title">Automatic Capture</div>
            <div class="description">Payments will be captured by Razorpay automatically</div>
          </div>
        </div>
        <div class="section">
          <div class="section-action">
            <input
              type="radio"
              onClick={() => {
                setselectedRow(2);
              }}
              checked={selectedRow === 2}
            />
          </div>
          <div class="section-content">
            <div class="title">Manual Capture</div>
            <div class="description">
              Payments have to be captured manually by you via the API or the dashboard
            </div>
          </div>
        </div>
        <div class="actions">
          <button
            class="btn btn-primary"
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
