import React from 'react';

export const NoIssuesStatus = () => {
  return (
    <>
      <img
        src={`${window.cdnBaseUrl}/static/assets/downtimes/green-tick.svg`}
        height="14px"
        width="14px"
        alt="No Issues found"
      />
      <span className="status">No issues noticed</span>
    </>
  );
};
