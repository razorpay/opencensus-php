import MethodNotOperational from './MethodNotOperational';
import MethodOperational from './MethodOperational';
import OngoingDowntime from './OngoingDowntime';

const UPIDetails = (props) => {
  return Object.keys(props.upiDowntimes).length === 0 ? (
    <MethodOperational methodName="UPI" switchToInfoView={props.switchToInfoView} />
  ) : (
    <>
      <MethodNotOperational methodName="UPI" switchToInfoView={props.switchToInfoView} />

      {/* VPA */}
      {props.upiDowntimes?.vpa_handle && (
        <div class="instrument-details">
          <div class="instrument-title">VPA</div>

          {/* Low */}
          {props.upiDowntimes?.vpa_handle?.low && (
            <OngoingDowntime downtimes={props.upiDowntimes?.vpa_handle?.low} severity="low" />
          )}

          {/* Medium */}
          {props.upiDowntimes?.vpa_handle?.medium && (
            <OngoingDowntime downtimes={props.upiDowntimes?.vpa_handle?.medium} severity="medium" />
          )}

          {/* High */}
          {props.upiDowntimes?.vpa_handle?.high && (
            <OngoingDowntime downtimes={props.upiDowntimes?.vpa_handle?.high} severity="high" />
          )}
        </div>
      )}

      {/* PSP */}
      {props.upiDowntimes?.psp && (
        <div class="instrument-details">
          <div class="instrument-title">PSP</div>

          {/* Low */}
          {props.upiDowntimes?.psp?.low && (
            <OngoingDowntime downtimes={props.upiDowntimes?.psp?.low} severity="low" />
          )}

          {/* Medium */}
          {props.upiDowntimes?.psp?.medium && (
            <OngoingDowntime downtimes={props.upiDowntimes?.psp?.medium} severity="medium" />
          )}

          {/* High */}
          {props.upiDowntimes?.psp?.high && (
            <OngoingDowntime downtimes={props.upiDowntimes?.psp?.high} severity="high" />
          )}
        </div>
      )}
    </>
  );
};

export default UPIDetails;
