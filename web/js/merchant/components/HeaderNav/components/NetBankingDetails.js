import {
  showMethodOperational,
  showMethodNotOperational,
  showLowSeverityDowntime,
  showMediumSeverityDowntime,
  showHighSeverityDowntime,
} from './utilities';

const NetBankingDetails = (props) => {
  return Object.keys(props.netBankingDowntimes).length === 0 ? (
    showMethodOperational('Net Banking', props.switchToInfoView)
  ) : (
    <>
      {showMethodNotOperational('Net Banking', props.switchToInfoView)}

      {/* Banks */}

      <div class="instrument-details">
        <div class="instrument-title">Banks</div>

        {/* Low */}
        {props.netBankingDowntimes?.low && showLowSeverityDowntime(props.netBankingDowntimes?.low)}

        {/* Medium */}
        {props.netBankingDowntimes?.medium &&
          showMediumSeverityDowntime(props.netBankingDowntimes?.medium)}

        {/* High */}
        {props.netBankingDowntimes?.high &&
          showHighSeverityDowntime(props.netBankingDowntimes?.high)}
      </div>
    </>
  );
};

export default NetBankingDetails;
