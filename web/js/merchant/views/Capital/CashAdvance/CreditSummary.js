import React from 'react';
import { Link } from 'react-router-dom';

import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';
import { CASH_ADVANCE_BASE_URL, CASH_ADVANCE_SECTIONS, ONHOLD_REASONS } from './constants';
import Popover, { PopoverBody } from 'common/ui/Popover';

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
  haveWithdrawals,
  isWithdrawalOnhold,
  reason,
}) {
  if (loading) return <Loader />;

  const { configuration: { internal_credit_limit = 0 } = {} } = withdrawalConfiguration;
  const isReasonCldRiskPolicy = reason === ONHOLD_REASONS.CLD_RISK_POLICY;
  const isReasonEndOfCreditLine = reason === ONHOLD_REASONS.END_OF_CREDIT_LINE_TENURE;
  const isReasonKudosNotMigrated = reason === ONHOLD_REASONS.NOT_MIGRATED_TO_GROMOR;

  const renderOnHoldSection = () => {
    return (
      <>
        <div className="flex withdrawals__status__wrapper-parent">
          <p className="title">Credit Limit</p>
          <div className="withdrawals__status__wrapper">
            <div
              className={`small ${isReasonCldRiskPolicy ? 'help-content' : ''}`}
              style={{ position: 'relative' }}
            >
              <p className="withdrawals__status">on hold</p>
              {isReasonCldRiskPolicy && (
                <Popover align="top" theme="dark" parentQuerySelector=".withdrawals__top-summary">
                  <PopoverBody>
                    <div className="text-left">
                      Your Cash Advance has been disabled due to perceived risk of decrease in
                      payments volume. Your line will be enabled once your payments volume increase.
                    </div>
                  </PopoverBody>
                </Popover>
              )}
            </div>
          </div>
        </div>
        {isReasonCldRiskPolicy && (
          <p className="summary">
            Your credit limit will be activated once your payments volumes are regular.
          </p>
        )}
      </>
    );
  };

  return (
    <div className="withdrawals__credit-meta">
      {isWithdrawalOnhold && (isReasonCldRiskPolicy || isReasonKudosNotMigrated) ? (
        renderOnHoldSection()
      ) : (
        <>
          <div className="withdrawals__credit-meta__header">
            <div className="title__wrapper">
              <p className="title">Withdrawal Balance</p>
            </div>
            {isWithdrawalOnhold && (
              <div className="withdrawals__status__wrapper">
                <div className="help-content small" style={{ position: 'relative' }}>
                  <p className="withdrawals__status">Blocked</p>
                  <Popover align="top" theme="dark" parentQuerySelector=".withdrawals__top-summary">
                    <PopoverBody>
                      <div className="text-left">
                        {isReasonEndOfCreditLine
                          ? 'Your credit line has been disabled as it has reached the end of tenure.'
                          : ' Your withdrawals are temporarily blocked due to missed repayments. Please repay to continue withdrawing from your credit line.'}
                      </div>
                    </PopoverBody>
                  </Popover>
                </div>
              </div>
            )}
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
        </>
      )}
      <div className="withdrawals__footer">
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
