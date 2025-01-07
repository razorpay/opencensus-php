import React from 'react';
import PropTypes from 'prop-types';

import Button from 'common/new-ui/Button';
import trackIS from 'merchant/views/Settlements/InstantSettlements/ga';
import { trackSettleNowClicked } from 'merchant/views/Settlements/trackEvents';

const EmptySettleNow = ({ showOndemandSettlementForm }) => {
  const handleSettleNowClick = (e) => {
    trackIS.clickCTAEmptySettleNow();
    trackSettleNowClicked('Empty State');
    showOndemandSettlementForm(e);
  };
  return (
    <div className="no-transaction-banner">
      <div className="no-transaction-banner--content">
        <div className="no-transaction-banner__heading">The wait is over...</div>
        <img
          className="mr-4"
          src={require('assets/capital/get_settlements_instantly.svg')}
          alt="Get settlements instantly"
        />
        <div className="no-transaction-banner--description">
          Delayed settlements can cause operational gaps for a business. Instant Settlements enables
          inflow of cash to amplify your business and meet cash requirements without any hassles -
          Instantly!
        </div>
        <Button.Secondary
          className="no-transaction-banner--settle-btn"
          onClick={handleSettleNowClick}
        >
          <img
            className="no-transaction-settle-now"
            src={require('assets/capital/early-settlement-light-blue.svg')}
            alt="Settle Now"
          />
          Settle Now
        </Button.Secondary>
      </div>
      <div className="no-transaction-banner--image">
        <img
          src={require('assets/capital/instant_settlement_no_transaction.svg')}
          alt="No transactions"
          width="462.3"
          height="448"
        />
      </div>
    </div>
  );
};

EmptySettleNow.propTypes = {
  showOndemandSettlementForm: PropTypes.func,
};

export default EmptySettleNow;
