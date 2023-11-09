import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { calculateCreditDebitAmount } from 'merchant/views/Settlements/v2/util';
import ComponentRow from './ComponentRow';
import TotalAmount from './TotalAmount';
import * as SettlementActions from 'merchant/reducers/settlements/details';
import Spinner from 'common/ui/Spinner';
import { showNotification } from 'merchant_common/reducers/notifications';

const SettlementBreakup = (props) => {
  const {
    breakupDetails: { items, isBreakupNew, loading, error },
    currency,
  } = props;
  useEffect(() => {
    props.fetchBreakupDetails({
      id: props.settlementId,
    });
  }, []);

  useEffect(() => {
    if (error)
      props.showNotification({
        type: 'error',
        message: error,
      });
  }, [error]);

  // show spinner unless settlements and breakup data is available
  if (loading) {
    return (
      <div class="div--loading">
        <Spinner />
      </div>
    );
  }

  if (error) return null;

  const calculatedAmounts = calculateCreditDebitAmount(items, isBreakupNew);

  return (
    <React.Fragment>
      <TotalAmount
        infoText="Total amount that has been credited to your account"
        value={calculatedAmounts.credit}
        type="credit"
        isNew={isBreakupNew}
        currency={currency}
      />
      <table>
        <tbody>
          {items.map((breakupItem, index) => {
            return breakupItem.type === 'credit' ? (
              <ComponentRow
                key={index}
                breakupItem={breakupItem}
                newResponse={isBreakupNew}
                currency={currency}
              />
            ) : null;
          })}
        </tbody>
      </table>
      {calculatedAmounts.debit !== 0 && (
        <TotalAmount
          infoText="Total amount that has been debited to your account"
          value={calculatedAmounts.debit}
          type="debit"
          isNew={isBreakupNew}
          currency={currency}
        />
      )}
      <table>
        <tbody>
          {items.map((breakupItem, index) => {
            return breakupItem.type === 'debit' ? (
              <ComponentRow
                key={index}
                breakupItem={breakupItem}
                newResponse={isBreakupNew}
                currency={currency}
              />
            ) : null;
          })}
        </tbody>
      </table>
    </React.Fragment>
  );
};

const mapStateToProps = (state) => {
  return {
    breakupDetails: state.settlement.breakupDetails,
    user: state.session.user,
  };
};

export default connect(mapStateToProps, { ...SettlementActions, showNotification })(
  SettlementBreakup,
);
