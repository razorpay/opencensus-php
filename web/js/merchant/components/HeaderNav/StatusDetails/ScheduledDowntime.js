import { getTimeinTwelveHourFormat } from './utilities';

const ScheduledDowntime = (props) => {
  const dateObj = new Date(props.scheduledDowntime?.begin * 1000);
  const time = getTimeinTwelveHourFormat(dateObj);
  return (
    <div key={props.scheduledDowntime.id} class="scheduled-downtime">
      <div class="scheduled-downtime-heading">
        {`${Object.keys(props.scheduledDowntime.instrument)[0]} - ${
          props.scheduledDowntime.mapToName
            ? props.scheduledDowntime.providerName
            : props.scheduledDowntime.instrument[Object.keys(props.scheduledDowntime.instrument)[0]]
        }`}
      </div>
      <div class="scheduled-downtime-details">
        {`On ${dateObj.getDate()} ${dateObj.toLocaleString('default', {
          month: 'long',
        })}'${dateObj.getFullYear()}, ${time}      |  `}
        {props.scheduledDowntime.severity === 'low' ? (
          <img
            src={`${window.cdnBaseUrl}/static/assets/downtimes/yellow-status-tiny.svg`}
            class="scheduled-downtime-icon"
          />
        ) : props.scheduledDowntime.severity === 'medium' ? (
          <img
            src={`${window.cdnBaseUrl}/static/assets/downtimes/orange-status-tiny.svg`}
            class="scheduled-downtime-icon"
          />
        ) : (
          <img
            src={`${window.cdnBaseUrl}/static/assets/downtimes/red-status-tiny.svg`}
            class="scheduled-downtime-icon"
          />
        )}

        <span class="scheduled-downtime-severity">{`   ${props.scheduledDowntime.severity} Severity`}</span>
      </div>
    </div>
  );
};

export default ScheduledDowntime;
