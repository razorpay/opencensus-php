import greenTickTiny from '../../../../../icons/merchant/greenTickTiny.svg';
import OngoingDowntime from './OngoingDowntime';

const UPIInfoDetails = (props) => {
  return (
    <div>
      <div class="status-method-instrument">
        VPA<span class="status-method-instrument-asterix">*</span>
      </div>

      <div class="status-method-instrument-info">
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
    </div>
  );
};

export default UPIInfoDetails;
