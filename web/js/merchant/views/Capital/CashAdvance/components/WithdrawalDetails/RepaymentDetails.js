import React, { useEffect, useState } from 'react';
import moment from 'moment';
import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';
import Spinner from 'common/ui/Spinner';
import Repayments from 'merchant/models/Capital/Repayments';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { STATUSES } from 'merchant/views/Capital/CashAdvance/constants';
import {
  getLastRepaidDate,
  gaEventDispatcher,
  getAmountFromBalances,
  getAmountFromCollectedAmount,
} from './utils';

const BALANCE_TYPES = {
  BALANCE_TYPE_INTEREST: 'BALANCE_TYPE_INTEREST',
  BALANCE_TYPE_PRINCIPAL: 'BALANCE_TYPE_PRINCIPAL',
};

export const RepaymentDetails = ({ id, withdrawalDetails, withdrawalConfigurationDetails }) => {
  const { data } = withdrawalDetails;
  const isStatusPartiallyRepaid = data?.status === STATUSES.PARTIALLY_REPAID;
  const isStatusRepaid = data?.status === STATUSES.REPAID;
  const isStatusPartiallyRepaidOrRepaid = isStatusPartiallyRepaid || isStatusRepaid;
  const [loading, setLoading] = useState(true);
  const [showBreakdown, setShowBreakdown] = useState(false);
  const [showPartialRepaymentBreakdown, setShowPartialRepaymentBreakdown] = useState(false);
  const [amountToBeRepaid, setAmountToBeRepaid] = useState({
    total: 0,
    principal: 0,
    interest: 0,
  });
  const [amountRepaid, setAmountRepaid] = useState({
    total: 0,
    principal: 0,
    interest: 0,
  });

  const toggleBreakdownVisibility = () => {
    gaEventDispatcher({
      eventAction: showBreakdown ? 'Details | Hide Breakup' : 'Details | Show Breakup',
    });

    setShowBreakdown((prev) => !prev);
  };

  const toggleRepaidBreakdownVisibility = () => {
    setShowPartialRepaymentBreakdown((prev) => !prev);
  };

  const loadRepaymentDetails = async () => {
    setLoading(true);
    const repaymentInstance = new Repayments();

    try {
      const response = await repaymentInstance.fetchBalances({
        product_entity_type: 'PRODUCT_ENTITY_TYPE_WITHDRAWAL',
        product_entity_reference_id: id,
      });

      const principal = getAmountFromBalances(
        response?.data?.balances,
        BALANCE_TYPES.BALANCE_TYPE_PRINCIPAL,
      );
      const interest = getAmountFromBalances(
        response?.data?.balances,
        BALANCE_TYPES.BALANCE_TYPE_INTEREST,
      );

      setAmountToBeRepaid({
        total: parseFloat(principal) + parseFloat(interest),
        principal,
        interest,
      });

      if (isStatusPartiallyRepaidOrRepaid) {
        const res = await repaymentInstance.fetchCollectedAmount(withdrawalDetails?.data?.plan_id);
        const collectedAmounts = res?.data?.collected_amounts || [];
        const amountBreakups = collectedAmounts?.[0]?.amount_breakups;

        const principalRepaid = getAmountFromCollectedAmount(
          amountBreakups,
          BALANCE_TYPES.BALANCE_TYPE_PRINCIPAL,
        );
        const interestRepaid = getAmountFromCollectedAmount(
          amountBreakups,
          BALANCE_TYPES.BALANCE_TYPE_INTEREST,
        );

        setAmountRepaid({
          total: parseFloat(principalRepaid) + parseFloat(interestRepaid),
          principal: principalRepaid,
          interest: interestRepaid,
        });
      }
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadRepaymentDetails();
  }, []);

  if (loading) {
    return <Spinner />;
  }

  return (
    <React.Fragment>
      <hr />
      <div className="block-note warning m-b">
        <strong>Repayment Details</strong>
      </div>
      {isStatusPartiallyRepaidOrRepaid ? (
        <React.Fragment>
          <EntityDetailRow label="Amount Repaid">
            <Amount value={amountRepaid.total} />
            {!showPartialRepaymentBreakdown && (
              <div>
                <Button.Transparent onClick={toggleRepaidBreakdownVisibility}>
                  Show Breakdown
                  <i className="i i-chevron-down" />
                </Button.Transparent>
              </div>
            )}
          </EntityDetailRow>
          {showPartialRepaymentBreakdown && (
            <div>
              <div className="block-note purple m-b">
                <EntityDetailRow label="Principal Amount">
                  <Amount value={amountRepaid.principal} />
                </EntityDetailRow>
                <EntityDetailRow label="Interest">
                  <div>
                    <Amount value={amountRepaid.interest} />
                  </div>
                  <Button.Transparent onClick={toggleRepaidBreakdownVisibility}>
                    Hide Breakdown
                    <i className="i i-chevron-up" />
                  </Button.Transparent>
                </EntityDetailRow>
              </div>
            </div>
          )}
          {isStatusPartiallyRepaid && (
            <EntityDetailRow label="Amount to be Repaid">
              <Amount value={parseFloat(amountToBeRepaid.total)} />
              {!showBreakdown && (
                <div>
                  <Button.Transparent onClick={toggleBreakdownVisibility}>
                    Show Breakdown
                    <i className="i i-chevron-down" />
                  </Button.Transparent>
                </div>
              )}
            </EntityDetailRow>
          )}
        </React.Fragment>
      ) : (
        <EntityDetailRow label="Amount to be Repaid">
          <Amount value={parseFloat(amountToBeRepaid.total)} />
          {!showBreakdown && (
            <div>
              <Button.Transparent onClick={toggleBreakdownVisibility}>
                Show Breakdown
                <i className="i i-chevron-down" />
              </Button.Transparent>
            </div>
          )}
        </EntityDetailRow>
      )}
      {showBreakdown && (
        <div>
          <div className="block-note purple m-b">
            <EntityDetailRow label="Principal Amount">
              <Amount value={amountToBeRepaid.principal} />
            </EntityDetailRow>
            <EntityDetailRow label="Interest">
              <div>
                <Amount value={amountToBeRepaid.interest} />
              </div>
              <Button.Transparent onClick={toggleBreakdownVisibility}>
                Hide Breakdown
                <i className="i i-chevron-up" />
              </Button.Transparent>
            </EntityDetailRow>
          </div>
        </div>
      )}
      {isStatusRepaid ? (
        <EntityDetailRow label="Repaid at">{getLastRepaidDate(withdrawalDetails)}</EntityDetailRow>
      ) : (
        <EntityDetailRow label="To be Repaid at">
          {moment(data.due_date).utc().format('LL')}
        </EntityDetailRow>
      )}
      <EntityDetailRow label="Rate of Interest">
        {Number(withdrawalConfigurationDetails?.configuration?.interest) / 100}% per day
      </EntityDetailRow>
      <EntityDetailRow label="Repayment Method">
        <p className="no-margin text--secondary KeyboardShortcutRow_action">
          Repayment amount will be deducted from your settlement Balance
        </p>
      </EntityDetailRow>
    </React.Fragment>
  );
};
