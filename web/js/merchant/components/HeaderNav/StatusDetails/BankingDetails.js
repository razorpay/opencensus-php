import React from 'react';
import MethodNotOperational from './MethodNotOperational';
import MethodOperational from './MethodOperational';
import OngoingDowntime from './OngoingDowntime';

const BankingDetails = (props) => {
  const { switchToInfoView, downtimes, methodName } = props;
  return Object.keys(downtimes).length === 0 ? (
    <MethodOperational methodName={methodName} switchToInfoView={switchToInfoView} />
  ) : (
    <>
      <MethodNotOperational methodName={methodName} switchToInfoView={switchToInfoView} />
      <div className="instrument-details">
        <div className="instrument-title">Banks</div>
        {downtimes?.low && <OngoingDowntime downtimes={downtimes.low} severity="low" />}
        {downtimes?.medium && <OngoingDowntime downtimes={downtimes.medium} severity="medium" />}
        {downtimes?.high && <OngoingDowntime downtimes={downtimes.high} severity="high" />}
      </div>
    </>
  );
};

export default BankingDetails;
