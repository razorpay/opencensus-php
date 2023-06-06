import React from 'react';
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

import { getQuickGuideData } from './data';
import { getIsPaymentButtonCodeUsed } from 'merchant/views/PaymentButton/utils';

const { done, locked, active, loading } = PossibleStatuses;

const Title = <QuickGuideTitle />;

class PaymentButtonsQuickGuide extends React.Component {
  getCloseBtn = (isCompleted) => {
    return <QuickGuideCloseBtn isCompleted={isCompleted} onClick={this.props.onClickClose} />;
  };

  render() {
    const { className = '' } = this.props;
    const { paymentButtonStatus, copyAndPasteTheCodeStatus, paymentReceiveStatus } = getStatus(
      this.props,
    );

    const CloseBtn = this.getCloseBtn(paymentReceiveStatus === done);

    let activeStep = 0;

    if (paymentReceiveStatus === done) {
      activeStep = 2;
    }

    if (copyAndPasteTheCodeStatus === done) {
      activeStep = 1;
    }

    return (
      <QuickStepGuide
        activeStep={activeStep}
        class={`PaymentButton ${className}`}
        title={Title}
        closeBtn={CloseBtn}
      >
        <QuickGuideStep
          status={paymentButtonStatus}
          step="PaymentButton"
          feature={RZPFeatures.PB}
          {...getQuickGuideData.createButton(paymentButtonStatus)}
        />

        <QuickGuideStep
          status={copyAndPasteTheCodeStatus}
          step="CopyAndPasteTheCode"
          feature={RZPFeatures.PB}
          {...getQuickGuideData.copyAndPasteTheCode(paymentReceiveStatus)}
        />

        <QuickGuideStep
          status={paymentReceiveStatus}
          step="PaymentReceive"
          feature={RZPFeatures.PB}
          {...getQuickGuideData.receivePayments(paymentReceiveStatus)}
        />
      </QuickStepGuide>
    );
  }
}

export const getPaymentButtonsQuickGuideIsClosed = (props) => {
  let isClosed = getQuickGuideIsClosedFromLocalStorage(RZPFeatures.PB);

  // Check if transfers non created state count is more then or equal to 2
  if (isClosed || props.paymentbuttons.items.length <= 2) {
    return isClosed;
  }

  props.paymentbuttons.items.forEach((page) => {
    if (isClosed) {
      return;
    }

    if (page.status === 'paid' || page.status === 'partially_paid') {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.PB, true);

      if (!isClosed) {
        isClosed = true;
      }
    }
  });

  return isClosed;
};

function getStatus({ paymentbuttons, mid, mode }) {
  let paymentButtonStatus = loading;
  let copyAndPasteTheCodeStatus = loading;
  let paymentReceiveStatus = loading;

  if (paymentbuttons.loading) {
    return {
      paymentButtonStatus,
      paymentReceiveStatus,
      copyAndPasteTheCodeStatus,
    };
  } else {
    paymentButtonStatus = locked;
    copyAndPasteTheCodeStatus = locked;
    paymentReceiveStatus = locked;
  }

  const isPaymentButtonCodeUsed = getIsPaymentButtonCodeUsed({ mid, mode });

  if (paymentbuttons.items.length) {
    paymentButtonStatus = done;
    copyAndPasteTheCodeStatus = done;
    paymentReceiveStatus = active;

    paymentbuttons.items.forEach((page) => {
      if (page.total_amount_paid) {
        paymentReceiveStatus = done;
        copyAndPasteTheCodeStatus = done;
      }
    });
  } else {
    paymentButtonStatus = active;
    copyAndPasteTheCodeStatus =
      paymentButtonStatus !== done ? locked : isPaymentButtonCodeUsed ? done : active;
    paymentReceiveStatus = copyAndPasteTheCodeStatus === done ? active : locked;
  }

  return {
    paymentButtonStatus,
    copyAndPasteTheCodeStatus,
    paymentReceiveStatus,
  };
}

const quickGuideSettings = {
  feature: RZPFeatures.PB,
  data_points: ['paymentbuttons'],
};

export default withQuickGuide(quickGuideSettings)(PaymentButtonsQuickGuide);
