import React, { useState, useMemo } from 'react';
import { connect } from 'react-redux';
import { OVERVIEW_STATUS_VIEWS, PLAN_STATUS } from '../../constants';
import PaymentAmount from './PaymentAmount';
import PaymentSelectAmount from './PaymentSelectAmount';
import PaymentMethod from './PaymentMethod';
import PaymentResult from './PaymentResult';
import { PaymentProvider } from './PaymentContext';
import Await, { usePromise } from '../../../../components/Await';
import api from '../../api';
import ClosedLoan from './ClosedLoan';

function OverviewStatus({
  installment,
  plan,
  upcomingPayments,
  lastRepayment,
  onRefresh,
  showHeader = true,
  showFooter = true,
  user,
}) {
  const isPlanClosed = plan.status !== PLAN_STATUS.CREATED;
  const [view, setView] = useState(
    isPlanClosed ? OVERVIEW_STATUS_VIEWS.LOAN_CLOSED : OVERVIEW_STATUS_VIEWS.PAYMENT_AMOUNT,
  );
  const fetchPrimaryBalance = useMemo(() => api.getPrimaryBalance(), []);
  const primaryBalance = usePromise(fetchPrimaryBalance);
  const allowCustomAmountRepayment = user.isLoanCustomAmountRepaymentEnabled;

  const updateView = (toView) => {
    setView(toView);
  };
  function renderView() {
    switch (view) {
      default:
      case OVERVIEW_STATUS_VIEWS.PAYMENT_AMOUNT:
        return (
          <PaymentAmount
            setView={updateView}
            lastRepayment={lastRepayment}
            upcomingPayments={upcomingPayments}
            installment={installment}
            plan={plan}
            allowCustomAmountRepayment={allowCustomAmountRepayment}
          />
        );
      case OVERVIEW_STATUS_VIEWS.PAYMENT_SELECT_AMOUNT:
        return (
          <Await promise={primaryBalance}>
            <PaymentSelectAmount setView={updateView} settlementBalance={primaryBalance.value} />
          </Await>
        );
      case OVERVIEW_STATUS_VIEWS.PAYMENT_METHOD:
        return (
          <Await promise={primaryBalance}>
            <PaymentMethod
              setView={updateView}
              settlementBalance={primaryBalance.value}
              allowCustomAmountRepayment={allowCustomAmountRepayment}
            />
          </Await>
        );
      case OVERVIEW_STATUS_VIEWS.PAYMENT_RESULT:
        return <PaymentResult setView={updateView} onRefresh={onRefresh} />;
      case OVERVIEW_STATUS_VIEWS.LOAN_CLOSED:
        return <ClosedLoan installment={installment} plan={plan} />;
      // AutopayDisabled screen
    }
  }

  const disbursalId = plan.product_entity_reference_id;
  const creditId = plan.credit_id;

  return (
    <div className="card overview-status">
      <PaymentProvider
        disbursalId={disbursalId}
        creditId={creditId}
        showHeader={showHeader}
        showFooter={showFooter}
      >
        {renderView()}
      </PaymentProvider>
    </div>
  );
}

export default connect((state) => ({
  user: state.session.user,
}))(OverviewStatus);
