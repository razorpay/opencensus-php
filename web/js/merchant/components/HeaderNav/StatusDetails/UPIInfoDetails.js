import React from 'react';
import { NoIssuesStatus } from './NoIssuesStatus';
import OngoingDowntime from './OngoingDowntime';

const VPADetails = ({ downtimes, operational }) => {
  return (
    <>
      <p className="title">VPA</p>
      <div className="description border">
        {downtimes?.vpa_handle?.low && (
          <OngoingDowntime downtimes={downtimes.vpa_handle.low} severity="low" />
        )}
        {downtimes?.vpa_handle?.medium && (
          <OngoingDowntime downtimes={downtimes.vpa_handle.medium} severity="medium" />
        )}
        {downtimes?.vpa_handle?.high && (
          <OngoingDowntime downtimes={downtimes.vpa_handle.high} severity="high" />
        )}
        <NoIssuesStatus />
        <p className="status-list">{operational?.join(', ')}</p>
      </div>
    </>
  );
};

const PSPDetails = ({ downtimes, operational }) => {
  return (
    <>
      <p className="title">PSP</p>
      <div className="description border">
        {downtimes?.psp?.low && <OngoingDowntime downtimes={downtimes.psp.low} severity="low" />}
        {downtimes?.psp?.medium && (
          <OngoingDowntime downtimes={downtimes.psp.medium} severity="medium" />
        )}
        {downtimes?.psp?.high && <OngoingDowntime downtimes={downtimes.psp.high} severity="high" />}
        <NoIssuesStatus />
        <p className="status-list">{operational?.map(({ pspName }) => pspName)?.join(', ')}</p>
      </div>
    </>
  );
};

const UPIInfoDetails = (props) => {
  const { upiDowntimes, vpaOperational, pspOperational } = props;
  return (
    <>
      <VPADetails downtimes={upiDowntimes} operational={vpaOperational} />
      <PSPDetails downtimes={upiDowntimes} operational={pspOperational} />
    </>
  );
};

export default UPIInfoDetails;
