import React from 'react';
import { NoIssuesStatus } from './NoIssuesStatus';
import OngoingDowntime from './OngoingDowntime';

const CardNetworks = ({ network, operationalNetworks }) => {
  return (
    <>
      <p className="title">Card Networks</p>
      <div className="description border">
        {network?.low && <OngoingDowntime downtimes={network.low} severity="low" />}
        {network?.medium && <OngoingDowntime downtimes={network.medium} severity="medium" />}
        {network?.high && <OngoingDowntime downtimes={network.high} severity="high" />}
        <NoIssuesStatus />
        <p className="status-list">{operationalNetworks?.join(', ')}</p>
      </div>
    </>
  );
};

const CardIssuers = ({ issuer, operationalIssuers }) => {
  return (
    <>
      <p className="title">Card Issuers</p>
      <div className="description border">
        {issuer?.low && <OngoingDowntime downtimes={issuer.low} severity="low" />}
        {issuer?.medium && <OngoingDowntime downtimes={issuer.medium} severity="medium" />}
        {issuer?.high && <OngoingDowntime downtimes={issuer.high} severity="high" />}
        <NoIssuesStatus />
        <p className="status-list">
          {operationalIssuers?.map(({ issuerName }) => issuerName)?.join(', ')}
        </p>
      </div>
    </>
  );
};

const CardsInfoDetails = (props) => {
  const {
    cardDowntimes: { network, issuer },
    cardNetworksOperational,
    cardIssuersOperational,
  } = props;
  return (
    <>
      <CardNetworks network={network} operationalNetworks={cardNetworksOperational} />
      <CardIssuers issuer={issuer} operationalIssuers={cardIssuersOperational} />
    </>
  );
};

export default CardsInfoDetails;
