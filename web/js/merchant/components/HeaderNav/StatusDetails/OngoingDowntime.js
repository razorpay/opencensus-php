import { getTimeinTwelveHourFormat } from './utilities';

const OngoingDowntime = (props) => {
  const checkWhetherStartedToday = (dateObj) => {
    const todayDate = new Date();
    if (
      todayDate.getDate() === dateObj.getDate() &&
      todayDate.getMonth() === dateObj.getMonth() &&
      todayDate.getFullYear() === dateObj.getFullYear()
    ) {
      return true;
    } else return false;
  };

  props.downtimes.forEach((downtime) => {
    const begin = downtime.begin;
    const corresDateTime = new Date(begin * 1000);
    const timeInFormat = getTimeinTwelveHourFormat(corresDateTime);
    downtime.started_at = timeInFormat;
    const startedToday = checkWhetherStartedToday(corresDateTime);
    if (!startedToday) {
      downtime.started_date = `${corresDateTime.getDate()}
      /${
        String(Number(corresDateTime.getMonth()) + 1).length === 1
          ? `0${Number(corresDateTime.getMonth()) + 1}`
          : Number(corresDateTime.getMonth()) + 1
      }/${corresDateTime.getFullYear()}`;
    }
  });
  return (
    <div class="downtime-container">
      {props.severity === 'low' ? (
        <>
          <img src={`${window.cdnBaseUrl}/static/assets/downtimes/yellow-status-tiny.svg`} />
          <span class="downtime-heading">Low severity downtime</span>
        </>
      ) : props.severity === 'medium' ? (
        <>
          <img src={`${window.cdnBaseUrl}/static/assets/downtimes/orange-status-tiny.svg`} />
          <span class="downtime-heading">Medium severity downtime</span>
        </>
      ) : (
        <>
          <img src={`${window.cdnBaseUrl}/static/assets/downtimes/red-status-tiny.svg`} />
          <span class="downtime-heading">High severity downtime</span>
        </>
      )}

      <div class="downtime-details">
        {props.downtimes.map((downtime, index) => (
          <span key={downtime.id}>
            <b>
              {downtime.mapToName
                ? downtime.providerName
                : downtime.instrument[Object.keys(downtime.instrument)[0]]}
            </b>
            {` (started at `}
            {downtime.started_date
              ? `${downtime.started_date}, ${downtime.started_at}`
              : downtime.started_at}
            {index === props.downtimes.length - 1 ? `)` : `), `}
          </span>
        ))}
      </div>
    </div>
  );
};

export default OngoingDowntime;
