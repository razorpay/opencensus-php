import React, { useState, useMemo } from 'react';
import { OVERVIEW_STATUS_VIEWS, PLAN_STATUS } from '../../constants';
import PaymentAmount from './PaymentAmount';
import PaymentMethod from './PaymentMethod';
import PaymentSuccess from './PaymentSuccess';
import PaymentFailure from './PaymentFailure';
import { PaymentProvider } from './PaymentContext';
import Await, { usePromise } from '../../../../components/Await';
import api from '../../api';
import ClosedLoan from './ClosedLoan';

export default function OverviewStatus({
  installment,
  plan,
  upcomingPayments,
  lastRepayment,
  onRefresh,
  showHeader = true,
  showFooter = true,
}) {
  const isPlanClosed = plan.status !== PLAN_STATUS.CREATED;
  const [view, setView] = useState(
    isPlanClosed ? OVERVIEW_STATUS_VIEWS.LOAN_CLOSED : OVERVIEW_STATUS_VIEWS.PAYMENT_AMOUNT,
  );
  const fetchPrimaryBalance = useMemo(() => api.getPrimaryBalance(), []);
  const primaryBalance = usePromise(fetchPrimaryBalance);
  function renderView() {
    switch (view) {
      default:
      case OVERVIEW_STATUS_VIEWS.PAYMENT_AMOUNT:
        return (
          <PaymentAmount
            setView={setView}
            lastRepayment={lastRepayment}
            upcomingPayments={upcomingPayments}
            installment={installment}
            plan={plan}
          />
        );
      case OVERVIEW_STATUS_VIEWS.PAYMENT_METHOD:
        return (
          <Await promise={primaryBalance}>
            <PaymentMethod setView={setView} settlementBalance={primaryBalance.value} />
          </Await>
        );
      case OVERVIEW_STATUS_VIEWS.PAYMENT_SUCCESS:
        return <PaymentSuccess onRefresh={onRefresh} />;
      case OVERVIEW_STATUS_VIEWS.PAYMENT_FAILURE:
        return <PaymentFailure setView={setView} onRefresh={onRefresh} />;
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
