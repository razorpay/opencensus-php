import MethodNotOperational from './MethodNotOperational';
import MethodOperational from './MethodOperational';
import OngoingDowntime from './OngoingDowntime';

const CardsDetails = (props) => {
  return Object.keys(props.cardDowntimes).length === 0 ? (
    <MethodOperational methodName="Cards" switchToInfoView={props.switchToInfoView} />
  ) : (
    <>
      <MethodNotOperational methodName="Cards" switchToInfoView={props.switchToInfoView} />
      {/* Card Networks */}
      {props.cardDowntimes?.network && (
        <div class="instrument-details">
          <div class="instrument-title">Card Networks</div>
          {/* Low */}
          {props.cardDowntimes?.network?.low && (
            <OngoingDowntime downtimes={props.cardDowntimes?.network?.low} severity="low" />
          )}
          {/* Medium */}
          {props.cardDowntimes?.network?.medium && (
            <OngoingDowntime downtimes={props.cardDowntimes?.network?.medium} severity="medium" />
          )}
          {/* High */}
          {props.cardDowntimes?.network?.high && (
            <OngoingDowntime downtimes={props.cardDowntimes?.network?.high} severity="high" />
          )}
        </div>
      )}
      {/* Card Issuers */}
      {props.cardDowntimes?.issuer && (
        <div class="instrument-details">
          <div class="instrument-title">Card Issuers</div>
          {/* Low */}
          {props.cardDowntimes?.issuer?.low && (
            <OngoingDowntime downtimes={props.cardDowntimes?.issuer?.low} severity="low" />
          )}
          {/* Medium */}
          {props.cardDowntimes?.issuer?.medium && (
            <OngoingDowntime downtimes={props.cardDowntimes?.issuer?.medium} severity="medium" />
          )}
          {/* High */}
          {props.cardDowntimes?.issuer?.high && (
            <OngoingDowntime downtimes={props.cardDowntimes?.issuer?.high} severity="high" />
          )}
        </div>
      )}
    </>
  );
};

export default CardsDetails;
