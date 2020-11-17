import React from 'react';
import PropTypes from 'prop-types';
import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import trackIS from 'merchant/views/Settlements/InstantSettlements/ga';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';

const CurrentBalance = ({ balance, showOndemandSettlementForm, updatedAt, isBalanceLoading }) => {
  const handleSettleNowClick = (e) => {
    trackIS.clickCTASettleNow();
    showOndemandSettlementForm(e);
  };
  return (
    <div className="current-balance">
      <div className="current-balance--left">
        <div>
          <img src="/dist/css/assets/capital/bank-balance.svg" />
        </div>
        <div className="current-balance--cb-content">
          <div className="current-balance--cb-heading">Current Balance</div>
          <div className="current-balance--cb-date">
            Updated: <Time value={updatedAt} format="MMM DD, h:mm A" />
          </div>
        </div>
      </div>
      <div className="current-balance--right">
        <div className="current-balance--amount">
          {isBalanceLoading ? <PlaceholderLoader /> : <Amount value={balance} currency="INR" />}
        </div>
        <Button.Primary
          className="current-balance--settle-btn"
          onClick={handleSettleNowClick}
          disabled={isBalanceLoading || balance < 100}
        >
          <i className="i i-early-settlement settle-now-early" />
          Settle Now
        </Button.Primary>
      </div>
    </div>
  );
};

CurrentBalance.propTypes = {
  balance: PropTypes.number,
  showOndemandSettlementForm: PropTypes.func,
  updatedAt: PropTypes.number,
  isBalanceLoading: PropTypes.bool,
};

export default CurrentBalance;
