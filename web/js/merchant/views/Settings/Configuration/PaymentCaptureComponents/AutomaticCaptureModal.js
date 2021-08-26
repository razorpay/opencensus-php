import ModalHeader from 'common/ui/ModalHeader';
import { useState } from 'react';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function AutomaticCaptureModal({ handleBack, handleDone, closeModal, selectedLateAuthType }) {
  const [selectedRow, setselectedRow] = useState(null);

  return (
    <div class="capture-settings">
      <ModalHeader
        title="Automatic Capture"
        onCloseClick={() => {
          analyticsTrack({
            objectName: 'automatic capture popup',
            actionName: 'clicked',
            screen: 'settings',
            properties: {
              location: 'configuration',
              actionName: 'close',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          closeModal();
        }}
      />
      <div class="content content-medium">
        <div class="section">
          <div class="section-action margin-top-20">
            <input
              type="radio"
              onClick={() => {
                setselectedRow(1);
              }}
              checked={selectedRow === 1}
            />
          </div>
          <div class="section-content">
            <div class="title">Capture all payments automatically</div>
            <div class="description">
              All payments authorised within 5 days of creation will be captured automatically
            </div>
          </div>
        </div>
        <div class="section">
          <div class="section-action margin-top-2">
            <input
              type="radio"
              onClick={() => {
                setselectedRow(2);
              }}
              checked={selectedRow === 2}
            />
          </div>
          <div class="section-content">
            <div class="title">Setup custom timeout</div>
            <div class="description">
              Setup capture timeout according to your business needs. Payments authorised within
              timeout will be captured and others will be refunded to your customers
            </div>
          </div>
        </div>
        <div class="dual-actions">
          <button class="btn btn-default" onClick={handleBack}>
            Back
          </button>
          <button
            class="btn btn-primary"
            disabled={!selectedRow}
            onClick={() => {
              handleDone(selectedLateAuthType, selectedRow !== 1);
            }}
          >
            Done
          </button>
        </div>
      </div>
    </div>
  );
}

export default AutomaticCaptureModal;
