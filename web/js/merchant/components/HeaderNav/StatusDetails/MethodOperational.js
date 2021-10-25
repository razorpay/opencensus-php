import React from 'react';
import Button from 'common/new-ui/Button';

const MethodOperational = (props) => {
  const { methodName, switchToInfoView } = props;
  return (
    <div className="payment-method border">
      <img
        src={`${window.cdnBaseUrl}/static/assets/downtimes/green-tick-small.svg`}
        alt="No issues found"
      />
      <span className="payment-method-title">{methodName}</span>
      <Button.Transparent
        className="status-view-button"
        onClick={() => {
          switchToInfoView(methodName);
        }}
      >
        View Details
      </Button.Transparent>
      <img
        src={`${window.cdnBaseUrl}/static/assets/downtimes/arrow-right-blue.svg`}
        className="status-view-arrow"
        onClick={() => {
          switchToInfoView(methodName);
        }}
        alt="Refresh button"
      />
    </div>
  );
};

export default MethodOperational;
