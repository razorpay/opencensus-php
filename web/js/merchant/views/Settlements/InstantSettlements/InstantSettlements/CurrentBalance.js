import React from 'react';
import PropTypes from 'prop-types';
import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import trackIS from 'merchant/views/Settlements/InstantSettlements/ga';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import Popover, { PopoverBody } from 'common/ui/Popover';

const CurrentBalance = ({
  balance,
  showOndemandSettlementForm,
  updatedAt,
  isBalanceLoading,
  isSettleNowRestricted,
  settleNowRestrictionMsg,
}) => {
  const handleSettleNowClick = (e) => {
    return false;

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
        <div>
          <Button.Primary
            className="current-balance--settle-btn settle-now"
            onClick={handleSettleNowClick}
            disabled={isSettleNowRestricted || isBalanceLoading || balance < 100 || true}
          >
            <i className="i i-early-settlement settle-now-early" />
            Settle Now
          </Button.Primary>
          {settleNowRestrictionMsg && (
            <Popover
              align="top"
              parentQuerySelector={`.current-balance--settle-btn .settle-now`}
              theme="dark"
            >
              <PopoverBody>{settleNowRestrictionMsg}</PopoverBody>
            </Popover>
          )}
        </div>
      </div>
    </div>
  );
};

CurrentBalance.propTypes = {
  balance: PropTypes.number,
  showOndemandSettlementForm: PropTypes.func,
  updatedAt: PropTypes.number,
  isBalanceLoading: PropTypes.bool,
  isSettleNowRestricted: PropTypes.bool,
};

export default CurrentBalance;
