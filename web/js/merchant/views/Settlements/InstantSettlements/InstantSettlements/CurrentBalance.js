import React from 'react';
import PropTypes from 'prop-types';

import Amount from 'common/ui/Amount';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import Time from 'common/ui/Time';
import trackIS from 'merchant/views/Settlements/InstantSettlements/ga';
import SettleNowButton from 'merchant/views/Settlements/Settlements/components/SettleNowButton';

const CurrentBalance = ({
  balance,
  showOndemandSettlementForm,
  updatedAt,
  isBalanceLoading,
  isSettleNowRestricted,
  settleNowRestrictionMsg,
  settlementExists,
  esOndemandSettlementEnabled,
  merchantId,
  checkIfFirstEverSettlement,
  isOnDemandDisabled,
}) => {
  const checkIfSettlementDisabled =
    isSettleNowRestricted || isBalanceLoading || balance < 100 || isOnDemandDisabled;

  const handleSettleNowClick = (e) => {
    trackIS.clickCTASettleNow();
    showOndemandSettlementForm(e);
  };

  return (
    <div className="current-balance">
      <div className="current-balance--left">
        <div>
          <img src={require('assets/capital/bank-balance.svg')} />
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
        <div className="settlenow-container">
          <SettleNowButton
            disabled={checkIfSettlementDisabled}
            merchantId={merchantId}
            fromWhere="Instant Settlements"
            settlementExists={settlementExists}
            esOndemandSettlementEnabled={esOndemandSettlementEnabled}
            showOndemandSettlementForm={handleSettleNowClick}
            checkIfFirstEverSettlement={checkIfFirstEverSettlement}
          />

          {settleNowRestrictionMsg && (
            <PopoverComponent
              align="top"
              parentQuerySelector=".current-balance--settle-btn .settle-now"
              theme="dark"
            >
              <PopoverBody>{settleNowRestrictionMsg}</PopoverBody>
            </PopoverComponent>
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
  settlementExists: PropTypes.bool,
};

export default CurrentBalance;
