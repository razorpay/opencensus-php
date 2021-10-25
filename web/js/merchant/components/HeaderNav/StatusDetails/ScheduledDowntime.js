import React from 'react';
import { getTimeinTwelveHourFormat } from './utilities';

const ScheduledDowntime = (props) => {
  const { scheduledDowntime } = props;
  const dateObj = new Date(scheduledDowntime?.begin * 1000);
  const time = getTimeinTwelveHourFormat(dateObj);
  return (
    <div key={scheduledDowntime.id} className="scheduled-downtime">
      <div className="scheduled-downtime-heading">
        {`${Object.keys(scheduledDowntime.instrument)[0]} - ${
          scheduledDowntime.mapToName
            ? scheduledDowntime.providerName
            : scheduledDowntime.instrument[Object.keys(scheduledDowntime.instrument)[0]]
        }`}
      </div>
      <div className="scheduled-downtime-details">
        {`On ${dateObj.getDate()} ${dateObj.toLocaleString('default', {
          month: 'long',
        })}'${dateObj.getFullYear()}, ${time}      |  `}
        {scheduledDowntime?.severity === 'low' ? (
          <img
            src={`${window.cdnBaseUrl}/static/assets/downtimes/yellow-status-tiny.svg`}
            className="scheduled-downtime-icon"
          />
        ) : scheduledDowntime?.severity === 'medium' ? (
          <img
            src={`${window.cdnBaseUrl}/static/assets/downtimes/orange-status-tiny.svg`}
            className="scheduled-downtime-icon"
          />
        ) : (
          <img
            src={`${window.cdnBaseUrl}/static/assets/downtimes/red-status-tiny.svg`}
            className="scheduled-downtime-icon"
          />
        )}

        <span className="scheduled-downtime-severity">{`${scheduledDowntime?.severity} Severity`}</span>
      </div>
    </div>
  );
};

export default ScheduledDowntime;
