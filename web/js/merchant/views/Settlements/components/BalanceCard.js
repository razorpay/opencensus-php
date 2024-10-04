import React from 'react';

import Amount from 'common/ui/Amount';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import CashAdvanceNudge from 'merchant/views/Capital/CashAdvanceNudges';

import SettleNow from './SettleNow';
import SettlementCard from './SettlementCard';
import { CashAdvanceWrapper } from './styledUtils';
import { HEADING_INFO } from './utils';

const BalanceCard = ({
  user,
  current_balance,
  isSettlementOnHold,
  settlementExists,
  esOndemandSettlementEnabled,
  checkIfFirstEverSettlement,
  isNodalAccountBalanceLowBlocked,
  balanceCurrency,
}) => {
  let amount = current_balance?.data?.balance || 0;
  let amountClassName = 'amount-current-balance';

  if (amount < 0) {
    amount = Math.abs(amount);
    amountClassName += ' negative-balance';
  }

  const content = (
    <>
      {balanceCurrency ? (
        <Amount
          aria-label="amount"
          value={amount}
          currency={balanceCurrency}
          className={amountClassName}
        />
      ) : (
        <PlaceholderLoader />
      )}

      <CashAdvanceWrapper>
        <CashAdvanceNudge />
      </CashAdvanceWrapper>
    </>
  );

  const footer = !isSettlementOnHold &&
    user?.isOndemandSettlementEnabled &&
    user?.isAllowedView('early_settlement') && (
      <SettleNow
        settlementExists={settlementExists}
        esOndemandSettlementEnabled={esOndemandSettlementEnabled}
        checkIfFirstEverSettlement={checkIfFirstEverSettlement}
        isNodalAccountLowBalanceBlocked={isNodalAccountBalanceLowBlocked}
        showLeftBorder={false}
      />
    );

  const customCardStyle = `
    border: 0;
  `;

  return (
    <SettlementCard
      heading="Current balance"
      headingInfo={HEADING_INFO.CURRENT_BALANCE}
      content={content}
      footer={footer}
      customCardStyle={customCardStyle}
    />
  );
};

export default BalanceCard;
