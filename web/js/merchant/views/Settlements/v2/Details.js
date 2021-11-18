import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import TotalAmount from './components/TotalAmount';
import SettlementInfo from './components/SettlementInfo';
import SettlementBreakup from './components/SettlementBreakup';
import SettlementEntities from './components/SettlementEntities';
import { calculateCreditDebitAmount } from './util';
import { classList } from 'common/utils/rzp-utils';
import LoaderDots from 'common/ui/LoaderDots';
import TestModeBanner from 'merchant/components/TestModeBanner';
import Amount from 'common/ui/Amount';

const SettlementDetails = (props) => {
  const [detailsCollapse, setdetailsCollapse] = useState(true);

  const calculateSettledAmount = (items, isNew) => {
    if (!isNew) {
      // summation of credit - summation of debit
      const creditSum = items.reduce((acc, item) => {
        if (item.type === 'credit') acc = acc + item.amount;
        return acc;
      }, 0);

      const debitSum = items.reduce((acc, item) => {
        if (item.type === 'debit') acc = acc + item.amount;
        return acc;
      }, 0);

      return creditSum - debitSum;
    } else {
      // summation of settled amount per component
      return items.reduce((acc, item) => {
        acc = acc + item.settled_amount;
        return acc;
      }, 0);
    }
  };

  const toggleShowMore = () => setdetailsCollapse(!detailsCollapse);

  const {
    breakupDetails: { items, isBreakupNew, loading },
  } = props;

  const shouldShowMore = () => items.length <= 2;

  const calculatedAmounts = calculateCreditDebitAmount(items, isBreakupNew);
  const totalAmount = calculateSettledAmount(items, isBreakupNew);

  return (
    <React.Fragment>
      <div
        class={classList(
          'content-sm txn-details settlements-v2',
          detailsCollapse && !shouldShowMore() && 'settlements-v2-collapse',
        )}
      >
        <div class="content-header">
          <Link to="/settlements" class="link-all">
            <i class="i i-arrow-back" /> All Settlements
          </Link>
          <i class="i i-chevron-right" /> Settlement Id: {props.match.params.id}
        </div>
        <div class="panel panel-default">
          {props.mode === 'test' && <TestModeBanner />}
          <div class="panel-heading">
            <div class="text">{props.match.params.id}</div>
            {/* added a new class as we need to add media query for the same for m-web support */}
            <div class="settlement-total-amount">
              {!loading ? (
                <TotalAmount
                  infoComp={
                    <InfoComponent
                      creditAmount={calculatedAmounts.credit}
                      debitAmount={calculatedAmounts.debit}
                      totalAmount={totalAmount}
                      isNew={isBreakupNew}
                    />
                  }
                  value={totalAmount}
                  type="settled"
                />
              ) : (
                <LoaderDots />
              )}
            </div>
          </div>
          <div class="panel-body">
            <div class="entity-details">
              <SettlementInfo settlementId={props.match.params.id} />
            </div>
            <div class="item-details">
              <SettlementBreakup settlementId={props.match.params.id} />
            </div>
          </div>
        </div>
        {!loading && !shouldShowMore() && (
          <button
            type="button"
            class="btn btn-primary btn-sm panel-collapser"
            onClick={toggleShowMore}
          >
            {detailsCollapse ? (
              <span>
                Show More <i class="i i-chevron-down" />
              </span>
            ) : (
              <span>
                Show Less <i class="i i-chevron-up" />
              </span>
            )}
          </button>
        )}
      </div>
      <div />

      <div class="content-sm txn-details settlements-v2 entity-list-table">
        <SettlementEntities settlementId={props.match.params.id} />
      </div>
    </React.Fragment>
  );
};

const InfoComponent = ({ creditAmount, debitAmount, totalAmount, isNew }) => {
  return (
    <div class="settlement-info-popup">
      <div class="amount-row">
        Total credit amount:{' '}
        <span class="amount-value">
          + <Amount value={creditAmount} currency="INR" />
        </span>
      </div>
      <div class="amount-row">
        Total debit amount:
        {isNew ? (
          <span class="amount-value">
            - <Amount value={debitAmount * -1} currency="INR" />
          </span>
        ) : (
          <span class="amount-value">
            - <Amount value={debitAmount} currency="INR" />
          </span>
        )}
      </div>
      <div class="settled-amount-row">
        Total settled amount:{' '}
        <span class="amount-value">
          <Amount value={totalAmount} currency="INR" />
        </span>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => {
  return {
    breakupDetails: state.settlement.breakupDetails,
    mode: state.session.mode,
  };
};

export default connect(mapStateToProps, null)(SettlementDetails);
