import React from 'react';
import Button from 'common/new-ui/Button';

const MethodNotOperational = (props) => {
  return (
    <div className="payment-method">
      <span className="payment-method-title-not-operational">{props.methodName}</span>
      <Button.Transparent
        className="status-view-button"
        onClick={() => {
          props.switchToInfoView(props.methodName);
        }}
      >
        View Details
      </Button.Transparent>
      <img
        src={`${window.cdnBaseUrl}/static/assets/downtimes/arrow-right-blue.svg`}
        className="status-view-arrow"
        onClick={() => {
          props.switchToInfoView(props.methodName);
        }}
        alt="View Details"
      />
    </div>
  );
};

export default MethodNotOperational;
