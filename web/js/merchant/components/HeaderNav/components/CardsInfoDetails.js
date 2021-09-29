import {
  showLowSeverityDowntime,
  showMediumSeverityDowntime,
  showHighSeverityDowntime,
  showWarningText,
} from './utilities';
import greenTickTiny from '../../../../../icons/merchant/greenTickTiny.svg';

const CardsInfoDetails = (props) => {
  return (
    <div class="status-instrument-details">
      <div class="status-method-instrument">
        Card Networks<span class="status-method-instrument-asterix">*</span>
      </div>

      <div class="status-method-instrument-info">
        {/* Low */}
        {props.cardDowntimes?.network?.low &&
          showLowSeverityDowntime(props.cardDowntimes?.network?.low)}

        {/* Medium */}
        {props.cardDowntimes?.network?.medium &&
          showMediumSeverityDowntime(props.cardDowntimes?.network?.medium)}

        {/* High */}
        {props.cardDowntimes?.network?.high &&
          showHighSeverityDowntime(props.cardDowntimes?.network?.high)}

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
        {props.cardDowntimes?.issuer?.low &&
          showLowSeverityDowntime(props.cardDowntimes?.issuer?.low)}

        {/* Medium */}
        {props.cardDowntimes?.issuer?.medium &&
          showMediumSeverityDowntime(props.cardDowntimes?.issuer?.medium)}

        {/* High */}
        {props.cardDowntimes?.issuer?.high &&
          showHighSeverityDowntime(props.cardDowntimes?.issuer?.high)}

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
      {showWarningText()}
    </div>
  );
};

export default CardsInfoDetails;
