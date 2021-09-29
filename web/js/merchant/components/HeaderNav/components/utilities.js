import greenTickSmall from '../../../../../icons/merchant/greenTickSmall.svg';
import Button from '../../../../common/new-ui/Button';
import arrowRight from '../../../../../icons/merchant/arrow_right_blue.svg';
import yellowStatusTiny from '../../../../../icons/merchant/yellowStatusTiny.svg';
import orangeStatusTiny from '../../../../../icons/merchant/orangeStatusTiny.svg';
import redStatusTiny from '../../../../../icons/merchant/redStatusTiny.png';

export const getTimeinTwelveHourFormat = (dateObj) => {
  let hours = dateObj.getHours();
  let meridian = 'am';
  if (hours > 12) {
    hours = hours - 12;
    meridian = 'pm';
  }
  let minutes = dateObj.getMinutes();
  if (String(minutes).length === 1) {
    minutes = `0${minutes}`;
  }
  const time = `${hours}:${minutes} ${meridian}`;
  return time;
};

export const showMethodOperational = (methodName, switchToInfoView) => {
  return (
    <div
      class={methodName === 'Net Banking' ? 'payment-method' : 'payment-method with-bottom-border'}
    >
      <img src={greenTickSmall} />
      <span className="payment-method-title"> {methodName}</span>
      <Button.Transparent
        class="status-view-button"
        onClick={() => {
          switchToInfoView(methodName);
        }}
      >
        View Details
      </Button.Transparent>
      <img
        src={arrowRight}
        class="status-view-arrow"
        onClick={() => {
          switchToInfoView(methodName);
        }}
      />
    </div>
  );
};

export const showMethodNotOperational = (methodName, switchToInfoView) => {
  return (
    <div class="payment-method">
      <span className="payment-method-title-not-operational">{methodName}</span>
      <Button.Transparent
        class="status-view-button"
        onClick={() => {
          switchToInfoView(methodName);
        }}
      >
        View Details
      </Button.Transparent>
      <img
        src={arrowRight}
        class="status-view-arrow"
        onClick={() => {
          switchToInfoView(methodName);
        }}
      />
    </div>
  );
};

export const showLowSeverityDowntime = (downtimes) => {
  downtimes.forEach((downtime) => {
    const begin = downtime.begin;
    const corresDateTime = new Date(begin * 1000);
    const timeInFormat = getTimeinTwelveHourFormat(corresDateTime);
    downtime.started_at = timeInFormat;
  });
  return (
    <div class="downtime-container">
      <img src={yellowStatusTiny} />
      <span class="downtime-heading">Low severity downtime</span>
      <div class="downtime-details">
        {downtimes.map((downtime, index) =>
          index === downtimes.length - 1 ? (
            <span key={downtime.id}>
              <b>
                {downtime.mapToName
                  ? downtime.providerName
                  : downtime.instrument[Object.keys(downtime.instrument)[0]]}
              </b>
              {` (started at ${downtime.started_at})`}
            </span>
          ) : (
            <span key={downtime.id}>
              <b>
                {downtime.mapToName
                  ? downtime.providerName
                  : downtime.instrument[Object.keys(downtime.instrument)[0]]}
              </b>
              {` (started at ${downtime.started_at}), `}
            </span>
          ),
        )}
      </div>
    </div>
  );
};

export const showMediumSeverityDowntime = (downtimes) => {
  downtimes.forEach((downtime) => {
    const begin = downtime.begin;
    const corresDateTime = new Date(begin * 1000);
    const timeInFormat = getTimeinTwelveHourFormat(corresDateTime);
    downtime.started_at = timeInFormat;
  });
  return (
    <div class="downtime-container">
      <img src={orangeStatusTiny} />
      <span class="downtime-heading">Medium severity downtime</span>
      <div class="downtime-details">
        {downtimes.map((downtime, index) =>
          index === downtimes.length - 1 ? (
            <span key={downtime.id}>
              <b>
                {downtime.mapToName
                  ? downtime.providerName
                  : downtime.instrument[Object.keys(downtime.instrument)[0]]}
              </b>
              {` (started at ${downtime.started_at})`}
            </span>
          ) : (
            <span key={downtime.id}>
              <b>
                {downtime.mapToName
                  ? downtime.providerName
                  : downtime.instrument[Object.keys(downtime.instrument)[0]]}{' '}
              </b>
              {` (started at ${downtime.started_at}), `}
            </span>
          ),
        )}
      </div>
    </div>
  );
};

export const showHighSeverityDowntime = (downtimes) => {
  downtimes.forEach((downtime) => {
    const begin = downtime.begin;
    const corresDateTime = new Date(begin * 1000);
    const timeInFormat = getTimeinTwelveHourFormat(corresDateTime);
    downtime.started_at = timeInFormat;
  });
  return (
    <div class="downtime-container">
      <img src={redStatusTiny} />
      <span class="downtime-heading">High severity downtime</span>
      <div class="downtime-details">
        {downtimes.map((downtime, index) =>
          index === downtimes.length - 1 ? (
            <span key={downtime.id}>
              <b>
                {downtime.mapToName
                  ? downtime.providerName
                  : downtime.instrument[Object.keys(downtime.instrument)[0]]}
              </b>
              {` (started at ${downtime.started_at})`}
            </span>
          ) : (
            <span key={downtime.id}>
              <b>
                {downtime.mapToName
                  ? downtime.providerName
                  : downtime.instrument[Object.keys(downtime.instrument)[0]]}{' '}
              </b>
              {` (started at ${downtime.started_at}), `}
            </span>
          ),
        )}
      </div>
    </div>
  );
};

export const showWarningText = () => {
  return (
    <div class="status-details-warning">
      <span class="status-warning-asterix">{`* `}</span>We only detect downtime fluctuations for the
      issuers and networks which have high payment volume.
    </div>
  );
};

export const showScheduledDowntime = (scheduledDowntime) => {
  const dateObj = new Date(scheduledDowntime?.begin * 1000);
  const time = getTimeinTwelveHourFormat(dateObj);
  return (
    <div key={scheduledDowntime.id} class="scheduled-downtime">
      <div class="scheduled-downtime-heading">
        {`${Object.keys(scheduledDowntime.instrument)[0]} - ${
          scheduledDowntime.mapToName
            ? scheduledDowntime.providerName
            : scheduledDowntime.instrument[Object.keys(scheduledDowntime.instrument)[0]]
        }`}
      </div>
      <div class="scheduled-downtime-details">
        {`On ${dateObj.getDate()} ${dateObj.toLocaleString('default', {
          month: 'long',
        })}'${dateObj.getFullYear()}, ${time}      |  `}
        {scheduledDowntime.severity === 'low' ? (
          <img src={yellowStatusTiny} class="scheduled-downtime-icon" />
        ) : scheduledDowntime.severity === 'medium' ? (
          <img src={orangeStatusTiny} class="scheduled-downtime-icon" />
        ) : (
          <img src={redStatusTiny} class="scheduled-downtime-icon" />
        )}

        <span class="scheduled-downtime-severity">{`   ${scheduledDowntime.severity} Severity`}</span>
      </div>
    </div>
  );
};
