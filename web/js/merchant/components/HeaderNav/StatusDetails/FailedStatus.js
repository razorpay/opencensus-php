const FailedStatus = (props) => {
  return (
    <div class="failed-status-container">
      <img
        class="failed-status-icon"
        src={`${window.cdnBaseUrl}/static/assets/downtimes/error-status.svg`}
      />
      <div class="failed-status-title">Failed to load Status</div>
      <div class="failed-status-text">
        There was an error while loading Payment Method status. We apologize for the inconvenience.
      </div>
      <div class="refresh" onClick={props.onUserRefresh}>
        <img
          class="refresh-icon"
          src={`${window.cdnBaseUrl}/static/assets/downtimes/refresh-cw.svg`}
        />
        <a class="refresh-text">Try again</a>
      </div>
    </div>
  );
};

export default FailedStatus;
