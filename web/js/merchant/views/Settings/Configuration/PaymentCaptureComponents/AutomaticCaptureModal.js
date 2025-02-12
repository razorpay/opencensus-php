import ModalHeader from 'common/ui/ModalHeader';
import { useState } from 'react';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function AutomaticCaptureModal({ handleBack, handleDone, closeModal, selectedLateAuthType }) {
  const [selectedRow, setselectedRow] = useState(null);

  return (
    <div className="capture-settings">
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
      <div className="content content-medium">
        <div className="section">
          <div className="section-action margin-top-20">
            <input
              type="radio"
              onClick={() => {
                setselectedRow(1);
              }}
              checked={selectedRow === 1}
            />
          </div>
          <div className="section-content">
            <div className="title">Capture all payments automatically</div>
            <div className="description">
              All payments authorised within 5 days of creation will be captured automatically
            </div>
          </div>
        </div>
        <div className="section">
          <div className="section-action margin-top-2">
            <input
              type="radio"
              onClick={() => {
                setselectedRow(2);
              }}
              checked={selectedRow === 2}
            />
          </div>
          <div className="section-content">
            <div className="title">Setup custom timeout</div>
            <div className="description">
              Setup capture timeout according to your business needs. Payments authorised within
              timeout will be captured and others will be refunded to your customers
            </div>
          </div>
        </div>
        <div className="dual-actions">
          <button className="btn btn-default" onClick={handleBack}>
            Back
          </button>
          <button
            className="btn btn-primary"
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
