import React from 'react';

const StatusLabel = ({ statusCode }) => {
  let labelClass = 'label-info';

  if (statusCode >= 500) {
    labelClass = 'label-danger';
  } else if (statusCode >= 400 && statusCode < 500) {
    labelClass = 'label-pending';
  } else if (statusCode >= 200 && statusCode < 300) {
    labelClass = 'label-success';
  }

  return <span className={`status-label label ${labelClass}`}>{statusCode}</span>;
};

export default StatusLabel;
