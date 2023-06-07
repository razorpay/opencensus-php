import { RZPFeatures, PossibleStatuses } from 'merchant/helpers/data';
import React from 'react';
import QuickGuide, {
  setQuickGuideIsClosedInLocalStorage,
  getQuickGuideIsClosedFromLocalStorage,
} from 'merchant/components/QuickGuide';
import QuickStepGuide, {
  QuickGuideStep,
  QuickGuideTitle,
  QuickGuideCloseBtn,
} from 'merchant/components/QuickGuide/QuickStepGuide';

import { getQuickGuideData } from './data';

const { done, locked, active, loading } = PossibleStatuses;

const Title = <QuickGuideTitle />;

const getStatus = ({ invoices }) => {
  let invoiceStatus = loading;
  let paymentReceiveStatus = loading;

  if (invoices.loading) {
    return {
      invoiceStatus,
      paymentReceiveStatus,
    };
  }

  if (invoices.items.length) {
    invoiceStatus = done;
    paymentReceiveStatus = active;

    for (const invoice of invoices.items) {
      if (invoice.status === 'paid' || invoice.status === 'partially_paid') {
        paymentReceiveStatus = done;

        return false;
      }
    }
  } else {
    invoiceStatus = active;
    paymentReceiveStatus = locked;
  }

  return {
    invoiceStatus,
    paymentReceiveStatus,
  };
};
@QuickGuide({
  feature: RZPFeatures.INVOICE,
  data_points: ['invoices'],
  dataTransformer: (key, state) => {
    return {
      ...state.invoices,
      items: state.invoices.invoices,
    };
  },
})
export default class InvoicesQuickGuide extends React.Component {
  getCloseBtn = (isCompleted) => {
    return <QuickGuideCloseBtn isCompleted={isCompleted} onClick={this.props.onClickClose} />;
  };

  render() {
    const { invoiceStatus, paymentReceiveStatus } = getStatus(this.props);
    const { org } = this.props;

    const CloseBtn = this.getCloseBtn(paymentReceiveStatus === done);

    let activeStep = 0;

    if (paymentReceiveStatus === done) {
      activeStep = 1;
    }
    const orgCustomCode = org.custom_code;

    return (
      <QuickStepGuide activeStep={activeStep} class="Invoices" title={Title} closeBtn={CloseBtn}>
        <QuickGuideStep
          status={invoiceStatus}
          step="Invoices"
          feature={RZPFeatures.INVOICE}
          {...getQuickGuideData.invoice(invoiceStatus, orgCustomCode)}
        />

        <QuickGuideStep
          status={paymentReceiveStatus}
          step="PaymentReceive"
          feature={RZPFeatures.INVOICE}
          {...getQuickGuideData.receivePayments(paymentReceiveStatus)}
        />
      </QuickStepGuide>
    );
  }
}

export const getInvoicesQuickGuideIsClosed = (props) => {
  let isClosed = getQuickGuideIsClosedFromLocalStorage(RZPFeatures.INVOICE);

  // Check if transfers non created state count is more then or equal to 2
  if (isClosed || props.invoices.invoices.length <= 2) {
    return isClosed;
  }

  let count = 0;

  for (const invoice of props.invoices.invoices) {
    if (invoice.status === 'paid' || invoice.status === 'partially_paid') {
      count += 1;
    }

    if (count >= 2) {
      isClosed = true;

      setQuickGuideIsClosedInLocalStorage(RZPFeatures.INVOICE, true);

      return false;
    }
  }

  return isClosed;
};
