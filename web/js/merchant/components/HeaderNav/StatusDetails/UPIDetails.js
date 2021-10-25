import React from 'react';
import MethodNotOperational from './MethodNotOperational';
import MethodOperational from './MethodOperational';
import OngoingDowntime from './OngoingDowntime';

const UPIDetails = (props) => {
  const { upiDowntimes, switchToInfoView } = props;
  return Object.keys(upiDowntimes).length === 0 ? (
    <MethodOperational methodName="UPI" switchToInfoView={switchToInfoView} />
  ) : (
    <>
      <MethodNotOperational methodName="UPI" switchToInfoView={switchToInfoView} />
      {upiDowntimes?.vpa_handle && (
        <div className="instrument-details">
          <div className="instrument-title">VPA</div>
          {upiDowntimes?.vpa_handle?.low && (
            <OngoingDowntime downtimes={upiDowntimes.vpa_handle.low} severity="low" />
          )}
          {upiDowntimes?.vpa_handle?.medium && (
            <OngoingDowntime downtimes={upiDowntimes.vpa_handle.medium} severity="medium" />
          )}
          {upiDowntimes?.vpa_handle?.high && (
            <OngoingDowntime downtimes={upiDowntimes.vpa_handle.high} severity="high" />
          )}
        </div>
      )}
      {upiDowntimes?.psp && (
        <div className="instrument-details">
          <div className="instrument-title">PSP</div>
          {upiDowntimes?.psp?.low && (
            <OngoingDowntime downtimes={upiDowntimes.psp.low} severity="low" />
          )}
          {upiDowntimes?.psp?.medium && (
            <OngoingDowntime downtimes={upiDowntimes.psp.medium} severity="medium" />
          )}
          {upiDowntimes?.psp?.high && (
            <OngoingDowntime downtimes={upiDowntimes.psp.high} severity="high" />
          )}
        </div>
      )}
    </>
  );
};

export default UPIDetails;
