import React from 'react';
import { getTimeinTwelveHourFormat } from './utilities';

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

const OngoingDowntime = (props) => {
  const { downtimes, severity } = props;
  downtimes.forEach((downtime) => {
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
      {severity === 'low' ? (
        <>
          <img
            src={`${window.cdnBaseUrl}/static/assets/downtimes/yellow-status-tiny.svg`}
            height="18px"
            width="14px"
            alt="Low Severity"
          />
          <span class="heading">Low severity downtime</span>
        </>
      ) : severity === 'medium' ? (
        <>
          <img
            src={`${window.cdnBaseUrl}/static/assets/downtimes/orange-status-tiny.svg`}
            height="18px"
            width="14px"
            alt="Medium Severity"
          />
          <span class="heading">Medium severity downtime</span>
        </>
      ) : (
        <>
          <img
            src={`${window.cdnBaseUrl}/static/assets/downtimes/red-status-tiny.svg`}
            height="18px"
            width="14px"
            alt="High Severity"
          />
          <span class="heading">High severity downtime</span>
        </>
      )}

      <div class="details">
        {downtimes.map((downtime) => {
          const { id, mapToName, providerName, instrument, started_at, started_date } = downtime;
          return (
            <span key={id}>
              <b>{mapToName ? providerName : instrument[Object.keys(instrument)[0]]}</b>
              {`(started at ${started_date ? `${started_date}, ${started_at}` : started_at})`}
            </span>
          );
        })}
      </div>
    </div>
  );
};

export default OngoingDowntime;
