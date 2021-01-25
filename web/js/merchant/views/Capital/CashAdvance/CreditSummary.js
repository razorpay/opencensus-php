import React from 'react';
import { Link } from 'react-router-dom';

import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';
import { CASH_ADVANCE_BASE_URL, CASH_ADVANCE_SECTIONS } from './constants';

const Loader = () => (
  <div class="flex">
    <p className="PlaceholderLoader" />
    <p className="PlaceholderLoader" />
  </div>
);

export default function CreditSummary({
  internalCreditBalance,
  withdrawalConfiguration,
  loading,
  user,
  history,
  haveWithdrawals,
}) {
  if (loading) return <Loader />;

  const { configuration: { internal_credit_limit = 0 } = {} } = withdrawalConfiguration;

  return (
    <div className="withdrawals__credit-meta">
      <div className="title__wrapper">
        <p className="title">Withdrawal Balance</p>
      </div>
      <div className="amount__wrapper">
        <Amount value={internalCreditBalance} parentQuerySelector=".withdrawals__top-summary" />
      </div>
      <div className="withdrawals__credit-meta__list">
        <div className="withdrawals__credit-meta__list-item">
          <div className="description__wrapper">
            <div className="description">
              <p>Total credit limit</p>
            </div>
            <Amount
              value={Number(internal_credit_limit)}
              parentQuerySelector=".withdrawals__top-summary"
            />
          </div>
        </div>
      </div>
      <div className="withdrawals__footer">
        {user && user.isLOSEnabled && user.isLOCEnabled && (
          <Button.Transparent
            onClick={() => history.push(`${CASH_ADVANCE_BASE_URL}apply?action=open`)}
          >
            More Details
          </Button.Transparent>
        )}
        {haveWithdrawals ? (
          <Button.Transparent>
            <Link to={`${CASH_ADVANCE_BASE_URL}${CASH_ADVANCE_SECTIONS.WITHDRAWALS}`}>
              View Withdrawals
            </Link>
          </Button.Transparent>
        ) : null}
      </div>
    </div>
  );
}
