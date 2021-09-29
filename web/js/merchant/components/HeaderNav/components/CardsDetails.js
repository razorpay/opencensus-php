import {
  showMethodOperational,
  showMethodNotOperational,
  showLowSeverityDowntime,
  showMediumSeverityDowntime,
  showHighSeverityDowntime,
} from './utilities';

const CardsDetails = (props) => {
  return Object.keys(props.cardDowntimes).length === 0 ? (
    showMethodOperational('Cards', props.switchToInfoView)
  ) : (
    <>
      {showMethodNotOperational('Cards', props.switchToInfoView)}
      {/* Card Networks */}
      {props.cardDowntimes?.network && (
        <div class="instrument-details">
          <div class="instrument-title">Card Networks</div>
          {/* Low */}
          {props.cardDowntimes?.network?.low &&
            showLowSeverityDowntime(props.cardDowntimes?.network?.low)}
          {/* Medium */}
          {props.cardDowntimes?.network?.medium &&
            showMediumSeverityDowntime(props.cardDowntimes?.network?.medium)}
          {/* High */}
          {props.cardDowntimes?.network?.high &&
            showHighSeverityDowntime(props.cardDowntimes?.network?.high)}
        </div>
      )}
      {/* Card Issuers */}
      {props.cardDowntimes?.issuer && (
        <div class="instrument-details">
          <div class="instrument-title">Card Issuers</div>
          {/* Low */}
          {props.cardDowntimes?.issuer?.low &&
            showLowSeverityDowntime(props.cardDowntimes?.issuer?.low)}
          {/* Medium */}
          {props.cardDowntimes?.issuer?.medium &&
            showMediumSeverityDowntime(props.cardDowntimes?.issuer?.medium)}
          {/* High */}
          {props.cardDowntimes?.issuer?.high &&
            showHighSeverityDowntime(props.cardDowntimes?.issuer?.high)}
        </div>
      )}
    </>
  );
};

export default CardsDetails;
