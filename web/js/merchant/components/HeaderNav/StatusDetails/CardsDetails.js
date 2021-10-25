import React from 'react';
import MethodNotOperational from './MethodNotOperational';
import MethodOperational from './MethodOperational';
import OngoingDowntime from './OngoingDowntime';

const CardsDetails = (props) => {
  const { cardDowntimes, switchToInfoView } = props;
  return Object.keys(cardDowntimes).length === 0 ? (
    <MethodOperational methodName="Cards" switchToInfoView={switchToInfoView} />
  ) : (
    <>
      <MethodNotOperational methodName="Cards" switchToInfoView={switchToInfoView} />
      {cardDowntimes?.network && (
        <div className="instrument-details">
          <div className="instrument-title">Card Networks</div>
          {cardDowntimes?.network?.low && (
            <OngoingDowntime downtimes={cardDowntimes.network.low} severity="low" />
          )}
          {cardDowntimes?.network?.medium && (
            <OngoingDowntime downtimes={cardDowntimes.network.medium} severity="medium" />
          )}
          {cardDowntimes?.network?.high && (
            <OngoingDowntime downtimes={cardDowntimes.network.high} severity="high" />
          )}
        </div>
      )}
      {cardDowntimes?.issuer && (
        <div className="instrument-details">
          <div className="instrument-title">Card Issuers</div>
          {cardDowntimes.issuer.low && (
            <OngoingDowntime downtimes={cardDowntimes.issuer.low} severity="low" />
          )}
          {cardDowntimes.issuer.medium && (
            <OngoingDowntime downtimes={cardDowntimes.issuer?.medium} severity="medium" />
          )}
          {cardDowntimes.issuer.high && (
            <OngoingDowntime downtimes={cardDowntimes.issuer.high} severity="high" />
          )}
        </div>
      )}
    </>
  );
};

export default CardsDetails;
