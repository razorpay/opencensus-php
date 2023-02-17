import React from 'react';

const CurlecSuccessContainer = (): JSX.Element => {
  return (
    <div className="step merchant-added-container">
      <div className="success-container">
        <div className="left-icon-container">
          <i className="i i-done ModeIndicator--live-icon" />
        </div>
        <div className="text-container">
          <div>
            <span className="success-text">
              Thank you for refferring! Our Agent will verify the details and reachout for further
              steps.
            </span>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CurlecSuccessContainer;
