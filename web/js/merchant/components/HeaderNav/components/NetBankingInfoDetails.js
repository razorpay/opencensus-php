import {
  showLowSeverityDowntime,
  showMediumSeverityDowntime,
  showHighSeverityDowntime,
  showWarningText,
} from './utilities';
import greenTickTiny from '../../../../../icons/merchant/greenTickTiny.svg';

const NetBankingInfoDetails = (props) => {
  return (
    <div class="status-instrument-details">
      <div class="status-method-instrument">
        Banks<span class="status-method-instrument-asterix">*</span>
      </div>
      <div class="status-method-instrument-info">
        {/* Low */}
        {props.netBankingDowntimes?.low && showLowSeverityDowntime(props.netBankingDowntimes?.low)}

        {/* Medium */}
        {props.netBankingDowntimes?.medium &&
          showMediumSeverityDowntime(props.netBankingDowntimes?.medium)}

        {/* High */}
        {props.netBankingDowntimes?.high &&
          showHighSeverityDowntime(props.netBankingDowntimes?.high)}

        {/* No issues */}
        <img src={greenTickTiny} />
        <span class="status-item">
          <b>No issues noticed</b>
        </span>
        <div class="status-list">
          {props.netBankingOperational.map((bank, index) => (
            <span class="status-list-text" key={bank.code}>
              {bank.bankName}
              {index != props.netBankingOperational.length - 1 && ', '}
            </span>
          ))}
        </div>
      </div>
      {showWarningText()}
    </div>
  );
};

export default NetBankingInfoDetails;
