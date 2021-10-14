import greenTickTiny from '../../../../../icons/merchant/greenTickTiny.svg';
import OngoingDowntime from './OngoingDowntime';

const CardsInfoDetails = (props) => {
  return (
    <div>
      <div class="status-method-instrument">
        Card Networks<span class="status-method-instrument-asterix">*</span>
      </div>

      <div class="status-method-instrument-info">
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

        {/* No issues */}
        <img src={greenTickTiny} />
        <span class="status-item">
          <b>No issues noticed</b>
        </span>
        <div class="status-list">
          {props.cardNetworksOperational.map((network, index) => (
            <span class="status-list-text" key={network}>
              {network}
              {index != props.cardNetworksOperational.length - 1 && ', '}
            </span>
          ))}
        </div>
      </div>
      <div class="status-method-instrument">
        Card Issuers<span class="status-method-instrument-asterix">*</span>
      </div>

      <div class="status-method-instrument-info">
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

        {/* No Issues */}
        <img src={greenTickTiny} />
        <span class="status-item">
          <b>No issues noticed</b>
        </span>
        <div class="status-list">
          {props.cardIssuersOperational.map((issuer, index) => (
            <span class="status-list-text" key={issuer.code}>
              {issuer.issuerName}
              {index != props.cardIssuersOperational.length - 1 && ', '}
            </span>
          ))}
        </div>
      </div>
    </div>
  );
};

export default CardsInfoDetails;
