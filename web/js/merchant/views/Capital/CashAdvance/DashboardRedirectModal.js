import React, { useEffect } from 'react';
import { getXCardsBaseURL } from '../utils/index';

export default function DashboardRedirectModal({ closeModal }) {
  useEffect(() => {
    setTimeout(() => {
      window.open(getXCardsBaseURL());
      closeModal();
    }, 2000);
  }, []);

  return (
    <div className="dashboard-redirect-modal-wrapper">
      <div className="modal-title">Corporate Card Dashboard</div>
      <div className="dashboard-modal-inner-card">
        <div className="rzp-logo">
          <img
            className="rzp-icon"
            src={`${window.cdnBaseUrl}/static/assets/capital/cash_on_card/rzp_logo.svg`}
            alt="rzp icon"
          />
        </div>
        <div className="redirect-msg">
          You are being redirected to the
          <br />
          <strong>Corporate Card Dashboard</strong>
        </div>
      </div>
    </div>
  );
}
