import greenTickTiny from '../../../../../icons/merchant/greenTickTiny.svg';
import OngoingDowntime from './OngoingDowntime';

const NetBankingInfoDetails = (props) => {
  return (
    <div>
      <div class="status-method-instrument">
        Banks<span class="status-method-instrument-asterix">*</span>
      </div>
      <div class="status-method-instrument-info">
        {/* Low */}
        {props.netBankingDowntimes?.low && (
          <OngoingDowntime downtimes={props.netBankingDowntimes?.low} severity="low" />
        )}

        {/* Medium */}
        {props.netBankingDowntimes?.medium && (
          <OngoingDowntime downtimes={props.netBankingDowntimes?.medium} severity="medium" />
        )}

        {/* High */}
        {props.netBankingDowntimes?.high && (
          <OngoingDowntime downtimes={props.netBankingDowntimes?.high} severity="high" />
        )}

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
    </div>
  );
};

export default NetBankingInfoDetails;
