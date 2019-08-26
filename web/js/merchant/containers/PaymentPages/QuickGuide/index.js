import { connect } from 'react-redux';

import { PossibleStatuses, RZPFeatures } from 'rzp/utils/constants';

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

@connect(state => ({
  user: state.session.user,
  mode: state.session.mode,
  invoices: state.invoices,
}))
@QuickGuide({
  feature: RZPFeatures.PP,
  data_points: ['paymentPages'],
  dataTransformer: (key, state) => {
    return {
      ...state.invoices,
      items: state.invoices.paymentPages,
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
    const { paymentPageStatus, paymentReceiveStatus } = getStatus(this.props);

    const CloseBtn = this.getCloseBtn(paymentReceiveStatus === done);

    let activeStep = 0;

    if (paymentReceiveStatus === done) {
      activeStep = 1;
    }

    return (
      <QuickStepGuide
        activeStep={activeStep}
        class="PaymetPages"
        title={Title}
        closeBtn={CloseBtn}
      >
        <QuickGuideStep
          status={paymentPageStatus}
          step="PaymentPage"
          feature={RZPFeatures.PP}
          {...getQuickGuideData.PaymentPage(paymentPageStatus)}
        />

        <QuickGuideStep
          status={paymentReceiveStatus}
          step="PaymentReceive"
          feature={RZPFeatures.PP}
          {...getQuickGuideData.ReceivePayments(paymentReceiveStatus)}
        />
      </QuickStepGuide>
    );
  }
}

const Title = <QuickGuideTitle />;

export const getPaymentPageQuickGuideIsClosed = props => {
  let isClosed = getQuickGuideIsClosedFromLocalStorage(RZPFeatures.PP);

  // Check if transfers non created state count is more then or equal to 2
  if (isClosed || props.paymentPages.length <= 2) {
    return isClosed;
  }

  if (props.invoices.loading) {
    return;
  }

  setQuickGuideIsClosedInLocalStorage(RZPFeatures.PP, true);
  return true;
};

const getStatus = ({ paymentPages, invoices }) => {
  let paymentPageStatus = loading,
    paymentReceiveStatus = loading;

  if (invoices.loading) {
    return {
      paymentPageStatus,
      paymentReceiveStatus,
    };
  }

  if (paymentPages.items.length) {
    paymentPageStatus = done;
    paymentReceiveStatus = active;

    paymentPages.items.forEach(page => {
      if (page.times_paid) {
        paymentReceiveStatus = done;

        return false;
      }
    });
  } else {
    paymentPageStatus = active;
    paymentReceiveStatus = locked;
  }

  return {
    paymentPageStatus,
    paymentReceiveStatus,
  };
};
