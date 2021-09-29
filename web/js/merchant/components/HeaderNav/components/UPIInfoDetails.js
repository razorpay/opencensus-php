import {
  showLowSeverityDowntime,
  showMediumSeverityDowntime,
  showHighSeverityDowntime,
  showWarningText,
} from './utilities';
import greenTickTiny from '../../../../../icons/merchant/greenTickTiny.svg';

const UPIInfoDetails = (props) => {
  return (
    <div class="status-instrument-details">
      <div class="status-method-instrument">
        VPA<span class="status-method-instrument-asterix">*</span>
      </div>

      <div class="status-method-instrument-info">
        {/* Low */}
        {props.upiDowntimes?.vpa_handle?.low &&
          showLowSeverityDowntime(props.upiDowntimes?.vpa_handle?.low)}

        {/* Medium */}
        {props.upiDowntimes?.vpa_handle?.medium &&
          showMediumSeverityDowntime(props.upiDowntimes?.vpa_handle?.medium)}

        {/* High */}
        {props.upiDowntimes?.vpa_handle?.high &&
          showHighSeverityDowntime(props.upiDowntimes?.vpa_handle?.high)}

        {/* No issues */}
        <img src={greenTickTiny} />
        <span class="status-item">
          <b>No issues noticed</b>
        </span>
        <div class="status-list">
          {props.vpaOperational.map((vpa, index) => (
            <span class="status-list-text" key={vpa}>
              {vpa}
              {index != props.vpaOperational.length - 1 && ', '}
            </span>
          ))}
        </div>
      </div>

      <div class="status-method-instrument">
        PSP<span class="status-method-instrument-asterix">*</span>
      </div>

      <div class="status-method-instrument-info">
        {/* Low */}
        {props.upiDowntimes?.psp?.low && showLowSeverityDowntime(props.upiDowntimes?.psp?.low)}

        {/* Medium */}
        {props.upiDowntimes?.psp?.medium &&
          showMediumSeverityDowntime(props.upiDowntimes?.psp?.medium)}

        {/* High */}
        {props.upiDowntimes?.psp?.high && showHighSeverityDowntime(props.upiDowntimes?.psp?.high)}

        {/* No issues */}
        <img src={greenTickTiny} />
        <span class="status-item">
          <b>No issues noticed</b>
        </span>
        <div class="status-list">
          {props.pspOperational.map((psp, index) => (
            <span class="status-list-text" key={psp.code}>
              {psp.pspName}
              {index != props.pspOperational.length - 1 && ', '}
            </span>
          ))}
        </div>
      </div>

      {showWarningText()}
    </div>
  );
};

export default UPIInfoDetails;
