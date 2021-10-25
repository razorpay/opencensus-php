import React from 'react';
import MethodNotOperational from './MethodNotOperational';
import MethodOperational from './MethodOperational';
import OngoingDowntime from './OngoingDowntime';

const NetBankingDetails = (props) => {
  const { switchToInfoView, netBankingDowntimes } = props;
  return Object.keys(netBankingDowntimes).length === 0 ? (
    <MethodOperational methodName="Net Banking" switchToInfoView={switchToInfoView} />
  ) : (
    <>
      <MethodNotOperational methodName="Net Banking" switchToInfoView={switchToInfoView} />
      <div class="instrument-details">
        <div class="instrument-title">Banks</div>
        {netBankingDowntimes?.low && (
          <OngoingDowntime downtimes={netBankingDowntimes.low} severity="low" />
        )}
        {netBankingDowntimes?.medium && (
          <OngoingDowntime downtimes={netBankingDowntimes.medium} severity="medium" />
        )}
        {netBankingDowntimes?.high && (
          <OngoingDowntime downtimes={netBankingDowntimes.high} severity="high" />
        )}
      </div>
    </>
  );
};

export default NetBankingDetails;
