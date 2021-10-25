import React from 'react';

const FailedStatus = (props) => {
  const { onUserRefresh } = props;
  return (
    <div className="failed-status-container">
      <img
        className="failed-status-icon"
        src={`${window.cdnBaseUrl}/static/assets/downtimes/error-status.svg`}
        alt="Error Status"
      />
      <div className="failed-status-title">Failed to load Status</div>
      <div className="failed-status-text">
        There was an error while loading Payment Method status. We apologize for the inconvenience.
      </div>
      <div className="refresh" onClick={onUserRefresh}>
        <img
          className="refresh-icon"
          src={`${window.cdnBaseUrl}/static/assets/downtimes/refresh-cw.svg`}
          alt="Refresh status"
        />
        <a className="refresh-text">Try again</a>
      </div>
    </div>
  );
};

export default FailedStatus;
