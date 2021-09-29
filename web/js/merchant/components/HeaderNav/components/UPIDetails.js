import {
  showMethodOperational,
  showMethodNotOperational,
  showLowSeverityDowntime,
  showMediumSeverityDowntime,
  showHighSeverityDowntime,
} from './utilities';

const UPIDetails = (props) => {
  return Object.keys(props.upiDowntimes).length === 0 ? (
    showMethodOperational('UPI', props.switchToInfoView)
  ) : (
    <>
      {showMethodNotOperational('UPI', props.switchToInfoView)}

      {/* VPA */}
      {props.upiDowntimes?.vpa_handle && (
        <div class="instrument-details">
          <div class="instrument-title">VPA</div>

          {/* Low */}
          {props.upiDowntimes?.vpa_handle?.low &&
            showLowSeverityDowntime(props.upiDowntimes?.vpa_handle?.low)}

          {/* Medium */}
          {props.upiDowntimes?.vpa_handle?.medium &&
            showMediumSeverityDowntime(props.upiDowntimes?.vpa_handle?.medium)}

          {/* High */}
          {props.upiDowntimes?.vpa_handle?.high &&
            showHighSeverityDowntime(props.upiDowntimes?.vpa_handle?.high)}
        </div>
      )}

      {/* PSP */}
      {props.upiDowntimes?.psp && (
        <div class="instrument-details">
          <div class="instrument-title">PSP</div>

          {/* Low */}
          {props.upiDowntimes?.psp?.low && showLowSeverityDowntime(props.upiDowntimes?.psp?.low)}

          {/* Medium */}
          {props.upiDowntimes?.psp?.medium &&
            showMediumSeverityDowntime(props.upiDowntimes?.psp?.medium)}

          {/* High */}
          {props.upiDowntimes?.psp?.high && showHighSeverityDowntime(props.upiDowntimes?.psp?.high)}
        </div>
      )}
    </>
  );
};

export default UPIDetails;
