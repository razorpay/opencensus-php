import React from 'react';
import { NoIssuesStatus } from './NoIssuesStatus';
import OngoingDowntime from './OngoingDowntime';

const BankingInfoDetails = (props) => {
  const { downtimes, operational } = props;
  return (
    <div>
      <p className="title">Banks</p>
      <div className="description border">
        {downtimes?.low && <OngoingDowntime downtimes={downtimes.low} severity="low" />}
        {downtimes?.medium && <OngoingDowntime downtimes={downtimes.medium} severity="medium" />}
        {downtimes?.high && <OngoingDowntime downtimes={downtimes.high} severity="high" />}
        <NoIssuesStatus />
        <p className="status-list">{operational?.map(({ bankName }) => bankName)?.join(', ')}</p>
      </div>
    </div>
  );
};

export default BankingInfoDetails;
