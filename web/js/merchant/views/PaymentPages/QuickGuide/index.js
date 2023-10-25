import React from 'react';
import { connect } from 'react-redux';

import { RZPFeatures, PossibleStatuses } from 'merchant/helpers/data';

import withQuickGuide, {
  setQuickGuideIsClosedInLocalStorage,
  getQuickGuideIsClosedFromLocalStorage,
} from 'merchant/components/QuickGuide';
import QuickStepGuide, {
  QuickGuideStep,
  QuickGuideTitle,
  QuickGuideCloseBtn,
} from 'merchant/components/QuickGuide/QuickStepGuide';

import { getQuickGuideData, getBatchQuickGuideData } from './data';

const { done, locked, active, loading } = PossibleStatuses;

const Title = <QuickGuideTitle />;

class PaymentPagesQuickGuide extends React.Component {
  getCloseBtn = (isCompleted) => {
    return <QuickGuideCloseBtn isCompleted={isCompleted} onClick={this.props.onClickClose} />;
  };

  render() {
    const { className, isBatchPaymentPages } = this.props;
    const { paymentPageStatus, paymentReceiveStatus } = getStatus(this.props);

    const CloseBtn = this.getCloseBtn(paymentReceiveStatus === done);

    let activeStep = 0;

    if (paymentReceiveStatus === done) {
      activeStep = 1;
    }

    return isBatchPaymentPages ? (
      <QuickStepGuide
        activeStep={activeStep}
        class={`PaymetPages ${className} batch-payment-page`}
        title={Title}
        closeBtn={CloseBtn}
      >
        <QuickGuideStep
          status="done"
          step="PaymentPage"
          feature={RZPFeatures.PP}
          {...getBatchQuickGuideData.paymentPage}
        />

        <QuickGuideStep
          status="locked"
          step="PaymentPage"
          feature={RZPFeatures.PP}
          {...getBatchQuickGuideData.uploadFile}
        />

        <QuickGuideStep
          status="active"
          step="PaymentReceive"
          feature={RZPFeatures.PP}
          {...getBatchQuickGuideData.receivePayments}
        />
      </QuickStepGuide>
    ) : (
      <QuickStepGuide
        activeStep={activeStep}
        class={`PaymetPages ${className}`}
        title={Title}
        closeBtn={CloseBtn}
      >
        <QuickGuideStep
          status={paymentPageStatus}
          step="PaymentPage"
          feature={RZPFeatures.PP}
          {...getQuickGuideData.paymentPage(paymentPageStatus)}
        />

        <QuickGuideStep
          status={paymentReceiveStatus}
          step="PaymentReceive"
          feature={RZPFeatures.PP}
          {...getQuickGuideData.receivePayments(paymentReceiveStatus)}
        />
      </QuickStepGuide>
    );
  }
}

export const getPaymentPageQuickGuideIsClosed = (props) => {
  const isClosed = getQuickGuideIsClosedFromLocalStorage(RZPFeatures.PP);

  // Check if transfers non created state count is more then or equal to 2
  if (isClosed || props.paymentPages.length <= 2) {
    return isClosed;
  }

  props.paymentPages.forEach((page) => {
    if (page.times_paid) {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.PP, true);
    }
  });

  return true;
};

function getStatus({ paymentPages, invoices }) {
  let paymentPageStatus = loading;
  let paymentReceiveStatus = loading;

  if (invoices.loading) {
    return {
      paymentPageStatus,
      paymentReceiveStatus,
    };
  }

  if (paymentPages.items.length) {
    paymentPageStatus = done;
    paymentReceiveStatus = active;

    paymentPages.items.forEach((page) => {
      if (page.times_paid) {
        paymentReceiveStatus = done;
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
}

const mapDispatchToProps = (state) => ({
  user: state.session.user,
  mode: state.session.mode,
  invoices: state.invoices,
});

const quickGuideSettings = {
  feature: RZPFeatures.PP,
  data_points: ['paymentPages'],
  dataTransformer: (key, state) => {
    return {
      ...state.invoices,
      items: state.invoices.paymentPages,
    };
  },
};

export default connect(mapDispatchToProps)(
  withQuickGuide(quickGuideSettings)(PaymentPagesQuickGuide),
);
