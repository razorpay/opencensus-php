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

const { done, locked, active, loading } = PossibleStatuses;
const Title = <QuickGuideTitle />;

@QuickGuide({
  feature: RZPFeatures.VA,
  data_points: ['virtualaccounts'],
})
export default class InvoicesQuickGuide extends React.Component {
  getCloseBtn = (isCompleted) => {
    return <QuickGuideCloseBtn isCompleted={isCompleted} onClick={this.props.onClickClose} />;
  };

  render() {
    const { virtualAccountsStatus, paymentReceiveStatus } = getStatus(this.props);

    const { className = '' } = this.props;

    const CloseBtn = this.getCloseBtn(paymentReceiveStatus === done);

    let activeStep = 0;

    if (paymentReceiveStatus === done) {
      activeStep = 1;
    }

    return (
      <QuickStepGuide
        activeStep={activeStep}
        className={`SmartCollect ${className}`}
        title={Title}
        closeBtn={CloseBtn}
      >
        <QuickGuideStep
          status={virtualAccountsStatus}
          step="VirtualAccount"
          feature={RZPFeatures.VA}
          {...getQuickGuideData.PaymentPage(virtualAccountsStatus)}
        />

        <QuickGuideStep
          status={paymentReceiveStatus}
          step="PaymentReceive"
          feature={RZPFeatures.VA}
          {...getQuickGuideData.ReceivePayments(paymentReceiveStatus)}
        />
      </QuickStepGuide>
    );
  }
}

export const getVAQuickGuideIsClosed = (props) => {
  const isClosed = getQuickGuideIsClosedFromLocalStorage(RZPFeatures.VA);

  // Check if transfers non created state count is more then or equal to 2
  if (isClosed || props.items.length <= 2) {
    return isClosed;
  }

  if (!props.items.loading) {
    setQuickGuideIsClosedInLocalStorage(RZPFeatures.VA, true);
  }

  return true;
};

function getStatus({ virtualaccounts }) {
  let virtualAccountsStatus = loading;
  let paymentReceiveStatus = loading;

  if (virtualaccounts.loading) {
    return {
      virtualAccountsStatus,
      paymentReceiveStatus,
    };
  }

  if (virtualaccounts.items.length) {
    virtualAccountsStatus = done;
    paymentReceiveStatus = active;

    // eslint-disable-next-line consistent-return
    virtualaccounts.items.forEach((account) => {
      if (account.amount_paid > 0) {
        paymentReceiveStatus = done;

        return false;
      }
    });
  } else {
    virtualAccountsStatus = active;
    paymentReceiveStatus = locked;
  }

  return {
    virtualAccountsStatus,
    paymentReceiveStatus,
  };
}
