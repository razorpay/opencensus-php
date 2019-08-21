import { PossibleStatuses, RZPFeatures } from 'rzp/utils/constants';

import Step from 'merchant/components/StepGuide/Step';
import QuickGuide, {
  getQuickGuideIsClosedFromLocalStorage,
} from 'merchant/components/QuickGuide';
import QuickStepGuide, {
  QuickGuideTitle,
  QuickGuideCloseBtn,
} from 'merchant/components/QuickGuide/QuickStepGuide';

import { getQuickGuideData } from './data';

const { done, locked, active, loading } = PossibleStatuses;

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
  getCloseBtn = isCompleted => {
    return (
      <QuickGuideCloseBtn
        isCompleted={isCompleted}
        onClick={this.props.onClickClose}
      />
    );
  };

  render() {
    const { invoiceStatus, paymentReceiveStatus } = getStatus(this.props);

    const CloseBtn = this.getCloseBtn(paymentReceiveStatus === done);

    let activeStep = 0;

    if (paymentReceiveStatus === done) {
      activeStep = 1;
    }

    return (
      <QuickStepGuide
        activeStep={activeStep}
        class="Invoices"
        title={Title}
        closeBtn={CloseBtn}
      >
        <Step
          status={invoiceStatus}
          {...getQuickGuideData.Invoice(invoiceStatus)}
        />

        <Step
          status={paymentReceiveStatus}
          {...getQuickGuideData.ReceivePayments(paymentReceiveStatus)}
        />
      </QuickStepGuide>
    );
  }
}

const Title = <QuickGuideTitle />;

export const getInvoicesQuickGuideIsClosed = props => {
  if (
    props.invoicesProductOnBoarding &&
    props.invoicesProductOnBoarding.showOnboarding
  ) {
    return false;
  }

  let isClosed = getQuickGuideIsClosedFromLocalStorage(RZPFeatures.INVOICE);

  // Check if transfers non created state count is more then or equal to 2
  if (isClosed || props.invoices.invoices.length <= 2) {
    return isClosed;
  }

  let count = 0;

  props.invoices.invoices.forEach(invoice => {
    if (invoice.status === 'paid' || invoice.status === 'partially_paid') {
      count += 1;
    }

    if (count >= 2) {
      isClosed = true;

      return false;
    }
  });

  return isClosed;
};

const getStatus = ({ invoices }) => {
  let invoiceStatus = loading,
    paymentReceiveStatus = loading;

  if (invoices.loading) {
    return {
      invoiceStatus,
      paymentReceiveStatus,
    };
  }

  if (invoices.items.length) {
    invoiceStatus = done;
    paymentReceiveStatus = active;

    invoices.items.forEach(invoice => {
      if (invoice.status === 'paid' || invoice.status === 'partially_paid') {
        paymentReceiveStatus = done;

        return false;
      }
    });
  } else {
    invoiceStatus = active;
    paymentReceiveStatus = locked;
  }

  return {
    invoiceStatus,
    paymentReceiveStatus,
  };
};
