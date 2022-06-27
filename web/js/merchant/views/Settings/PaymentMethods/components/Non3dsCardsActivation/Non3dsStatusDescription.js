import React from 'react';

// constants
import { NON_3DS_CARD_WORKFLOW_STATUS } from 'merchant/reducers/non3dsCardsActivation';

const Non3dsStatusDescription = ({ status, showLearnMoreLink, onLearnMore }) => {
  const handleLearnMoreClick = (e) => {
    e.preventDefault();
    onLearnMore();
  };

  switch (status) {
    case NON_3DS_CARD_WORKFLOW_STATUS.EXECUTED:
    case NON_3DS_CARD_WORKFLOW_STATUS.APPROVED: {
      return (
        <div>
          Non 3D Secure cards are unauthenticated cards that might have fraud risks.{' '}
          {showLearnMoreLink && (
            <a href="" onClick={handleLearnMoreClick}>
              Learn More
            </a>
          )}
        </div>
      );
    }
    case NON_3DS_CARD_WORKFLOW_STATUS.CLOSED:
    case NON_3DS_CARD_WORKFLOW_STATUS.FAILED: {
      return (
        <div>
          Non 3D Secure cards are unauthenticated cards that might have fraud risks. Non 3D Secure
          transactions are disabled for your account. If you think, this is done by mistake, please
          request to enable it.{' '}
          {showLearnMoreLink && (
            <a href="" onClick={handleLearnMoreClick}>
              Learn More
            </a>
          )}
        </div>
      );
    }
    case NON_3DS_CARD_WORKFLOW_STATUS.OPEN: {
      return <div>Non 3D Secure cards are unauthenticated cards that might have fraud risks.</div>;
    }
    case NON_3DS_CARD_WORKFLOW_STATUS.REJECTED: {
      return (
        <div>
          Razorpay fraud protection team has disabled support for non 3D Secure transactions because
          of recent chargebacks. If you think this is done by mistake, please request to enable it.{' '}
          {showLearnMoreLink && (
            <a href="" onClick={handleLearnMoreClick}>
              Learn More
            </a>
          )}
        </div>
      );
    }
    default:
      return (
        <div>
          Non 3D Secure cards are unauthenticated cards that might have fraud risks.{' '}
          {showLearnMoreLink && (
            <a href="" onClick={handleLearnMoreClick}>
              Learn More
            </a>
          )}
        </div>
      );
  }
};

export default Non3dsStatusDescription;
