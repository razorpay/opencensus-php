import MethodNotOperational from './MethodNotOperational';
import MethodOperational from './MethodOperational';
import OngoingDowntime from './OngoingDowntime';

const NetBankingDetails = (props) => {
  return Object.keys(props.netBankingDowntimes).length === 0 ? (
    <MethodOperational methodName="Net Banking" switchToInfoView={props.switchToInfoView} />
  ) : (
    <>
      <MethodNotOperational methodName="Net Banking" switchToInfoView={props.switchToInfoView} />

      {/* Banks */}

      <div class="instrument-details">
        <div class="instrument-title">Banks</div>

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
      </div>
    </>
  );
};

export default NetBankingDetails;
