import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import TotalAmount from './components/TotalAmount';
import SettlementInfo from './components/SettlementInfo';
import SettlementBreakup from './components/SettlementBreakup';
import SettlementEntities from './components/SettlementEntities';
import { calculateCreditDebitAmount } from './util';
import { classList, isNone } from 'common/utils/rzp-utils';
import LoaderDots from 'common/ui/LoaderDots';
import TestModeBanner from 'merchant/components/TestModeBanner';
import Amount from 'common/ui/Amount';
import { bindActionCreators } from 'redux';
import { fetchTerminalProviders } from 'merchant/reducers/navigator/details';
import { MismatchBanner } from './components/MismatchBanner';
import { merchantFetch } from 'merchant/utils/ajax';

const SettlementDetails = (props) => {
  const [detailsCollapse, setdetailsCollapse] = useState(true);
  const [checkAmounts, setCheckAmounts] = useState({});
  const { match } = props; // better start destructuring props here
  const currency = props.user.merchant.currency;

  useEffect(() => {
    const { user, fetchProviders, match } = props;
    if (user?.isSingleReconEnabled && user?.isOptimizerEnabled) {
      fetchProviders();
      const params = {
        url: 'settlements/amount_check',
        method: 'post',
        data: {
          settlement_id: match?.params?.id?.split('?')[0],
        },
      };
      merchantFetch(params).then((response) => {
        const { settlementAmount, totalTransactionAmount } = response.data;
        setCheckAmounts({
          settlementAmount,
          totalTransactionAmount,
        });
      });
    }
  }, []);

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
    settlement,
    breakupDetails: { items, isBreakupNew, loading },
    user,
  } = props;

  const shouldShowMore = () => items.length <= 2;

  const calculatedAmounts = calculateCreditDebitAmount(items, isBreakupNew);
  const totalAmount = calculateSettledAmount(items, isBreakupNew);

  const { settlementAmount, totalTransactionAmount } = checkAmounts;

  const settlementId = match?.params?.id?.split('?')[0];

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
          <i class="i i-chevron-right" /> Settlement Id: {settlementId}
        </div>
        {user?.isSingleReconEnabled &&
          user?.isOptimizerEnabled &&
          !isNone(settlementAmount) &&
          !isNone(totalTransactionAmount) &&
          settlementAmount > totalTransactionAmount && (
            <MismatchBanner
              totalAmount={settlementAmount}
              calculatedAmounts={totalTransactionAmount}
              gatewayName={settlement?.settled_by || ''}
            />
          )}
        <div class="panel panel-default">
          {props.mode === 'test' && <TestModeBanner />}
          <div class="panel-heading">
            <div class="text">{settlementId}</div>
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
                      currency={currency}
                    />
                  }
                  value={totalAmount}
                  type="settled"
                  currency={currency}
                />
              ) : (
                <LoaderDots />
              )}
            </div>
          </div>
          <div class="panel-body">
            <div class="entity-details">
              <SettlementInfo settlementId={settlementId} />
            </div>
            <div class="item-details">
              <SettlementBreakup settlementId={settlementId} />
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
        <SettlementEntities settlementId={settlementId} />
      </div>
    </React.Fragment>
  );
};

const InfoComponent = ({ creditAmount, debitAmount, totalAmount, isNew, currency }) => {
  return (
    <div class="settlement-info-popup">
      <div class="amount-row">
        Total credit amount:{' '}
        <span class="amount-value">
          + <Amount value={creditAmount} currency={currency} />
        </span>
      </div>
      <div class="amount-row">
        Total debit amount:
        {isNew ? (
          <span class="amount-value">
            - <Amount value={debitAmount * -1} currency={currency} />
          </span>
        ) : (
          <span class="amount-value">
            - <Amount value={debitAmount} currency={currency} />
          </span>
        )}
      </div>
      <div class="settled-amount-row">
        Total settled amount:{' '}
        <span class="amount-value">
          <Amount value={totalAmount} currency={currency} />
        </span>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => {
  const { settlement, session } = state;
  return {
    breakupDetails: settlement.breakupDetails,
    settlement: settlement.settlement,
    mode: session.mode,
    user: session.user,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ fetchProviders: fetchTerminalProviders }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(SettlementDetails);
