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
  feature: RZPFeatures.VA,
  data_points: ['virtualaccounts'],
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
    const { virtualAccountsStatus, paymentReceiveStatus } = getStatus(
      this.props
    );

    const CloseBtn = this.getCloseBtn(paymentReceiveStatus === done);

    let activeStep = 0;

    if (paymentReceiveStatus === done) {
      activeStep = 1;
    }

    return (
      <QuickStepGuide
        activeStep={activeStep}
        class="SmartCollect"
        title={Title}
        closeBtn={CloseBtn}
      >
        <Step
          status={virtualAccountsStatus}
          {...getQuickGuideData.PaymentPage(virtualAccountsStatus)}
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

export const getVAQuickGuideIsClosed = props => {
  let isClosed = getQuickGuideIsClosedFromLocalStorage(RZPFeatures.VA);

  // Check if transfers non created state count is more then or equal to 2
  if (isClosed || props.items.length <= 2) {
    return isClosed;
  }

  return true;
};

const getStatus = ({ virtualaccounts }) => {
  let virtualAccountsStatus = loading,
    paymentReceiveStatus = loading;

  if (virtualaccounts.loading) {
    return {
      virtualAccountsStatus,
      paymentReceiveStatus,
    };
  }

  if (virtualaccounts.items.length) {
    virtualAccountsStatus = done;
    paymentReceiveStatus = active;

    virtualaccounts.items.forEach(account => {
      if (
        account.status === 'paid' ||
        virtualaccounts.status === 'partially_paid'
      ) {
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
};
