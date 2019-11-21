import { RZPFeatures, PossibleStatuses } from 'merchant/helpers/data';

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

@QuickGuide({
  feature: RZPFeatures.PL,
  data_points: ['invoices'],
  dataTransformer: (key, state) => {
    return {
      ...state.invoices,
      items: state.invoices.invoices,
    };
  },
})
export default class PaymentPagesQuickGuide extends React.Component {
  getCloseBtn = isCompleted => {
    return (
      <QuickGuideCloseBtn
        isCompleted={isCompleted}
        onClick={this.props.onClickClose}
      />
    );
  };

  render() {
    const { paymentLinkStatus, paymentReceiveStatus } = getStatus(this.props);

    const CloseBtn = this.getCloseBtn(paymentReceiveStatus === done);

    let activeStep = 0;

    if (paymentReceiveStatus === done) {
      activeStep = 1;
    }

    return (
      <QuickStepGuide
        activeStep={activeStep}
        class="Route"
        title={Title}
        closeBtn={CloseBtn}
      >
        <QuickGuideStep
          status={paymentLinkStatus}
          step="PaymentLink"
          feature={RZPFeatures.PL}
          {...getQuickGuideData.PaymentLinks(paymentLinkStatus)}
        />

        <QuickGuideStep
          status={paymentReceiveStatus}
          step="PaymentReceive"
          feature={RZPFeatures.PL}
          {...getQuickGuideData.ReceivePayments(paymentReceiveStatus)}
        />
      </QuickStepGuide>
    );
  }
}

const Title = <QuickGuideTitle />;

export const getPaymentLinksQuickGuideIsClosed = props => {
  let isClosed = getQuickGuideIsClosedFromLocalStorage(RZPFeatures.PL);

  // Check if transfers non created state count is more then or equal to 2
  if (isClosed || props.invoices.invoices.length <= 2) {
    return isClosed;
  }

  props.invoices.invoices.forEach(page => {
    if (page.status === 'paid' || page.status === 'partially_paid') {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.PL, true);
      return false;
    }
  });

  return true;
};

const getStatus = ({ invoices }) => {
  let paymentLinkStatus = loading,
    paymentReceiveStatus = loading;

  if (invoices.loading) {
    return {
      paymentLinkStatus,
      paymentReceiveStatus,
    };
  }

  if (invoices.items.length) {
    paymentLinkStatus = done;
    paymentReceiveStatus = active;

    invoices.items.forEach(page => {
      if (page.status === 'paid' || page.status === 'partially_paid') {
        paymentReceiveStatus = done;

        return false;
      }
    });
  } else {
    paymentLinkStatus = active;
    paymentReceiveStatus = locked;
  }

  return {
    paymentLinkStatus,
    paymentReceiveStatus,
  };
};
