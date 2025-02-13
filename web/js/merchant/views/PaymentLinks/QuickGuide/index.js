/* eslint-disable babel/new-cap */
import React from 'react';
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
import { compose } from 'redux';

const { done, locked, active, loading } = PossibleStatuses;

const Title = <QuickGuideTitle />;

class PaymentPagesQuickGuide extends React.Component {
  getCloseBtn = (isCompleted) => {
    return <QuickGuideCloseBtn isCompleted={isCompleted} onClick={this.props.onClickClose} />;
  };

  render() {
    const { paymentLinkStatus, paymentReceiveStatus } = getStatus(this.props);
    const { className = '' } = this.props;

    const CloseBtn = this.getCloseBtn(paymentReceiveStatus === done);

    let activeStep = 0;

    if (paymentReceiveStatus === done) {
      activeStep = 1;
    }

    return (
      <QuickStepGuide
        activeStep={activeStep}
        className={`Route ${className}`}
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

export default compose(
  QuickGuide({
    feature: RZPFeatures.PL,
    data_points: ['paymentlinks'],
    dataTransformer: (key, state) => {
      return {
        ...state.paymentlinks,
        items: state.paymentlinks.paymentlinks,
      };
    },
  }),
)(PaymentPagesQuickGuide);

export function getPaymentLinksQuickGuideIsClosed(props) {
  const isClosed = getQuickGuideIsClosedFromLocalStorage(RZPFeatures.PL);

  // Check if transfers non created state count is more then or equal to 2
  if (isClosed || props.paymentlinks.paymentlinks.length <= 2) {
    return isClosed;
  }

  // eslint-disable-next-line consistent-return
  props.paymentlinks.paymentlinks.forEach((page) => {
    if (page.status === 'paid' || page.status === 'partially_paid') {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.PL, true);
      return false;
    }
  });

  return true;
}

function getStatus({ paymentlinks }) {
  let paymentLinkStatus = loading;
  let paymentReceiveStatus = loading;

  if (paymentlinks.loading) {
    return {
      paymentLinkStatus,
      paymentReceiveStatus,
    };
  }

  if (paymentlinks.items.length) {
    paymentLinkStatus = done;
    paymentReceiveStatus = active;

    // eslint-disable-next-line consistent-return
    paymentlinks.items.forEach((page) => {
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
}
