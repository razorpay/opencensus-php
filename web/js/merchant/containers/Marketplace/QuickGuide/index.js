import { connect } from 'react-redux';

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

@connect(state => ({
  user: state.session.user,
  mode: state.session.mode,
}))
@QuickGuide({
  feature: RZPFeatures.ROUTE,
  data_points: ['transfers', 'accounts'],
  dataTransformer: (key, state) => {
    if (key === 'accounts') {
      return {
        ...state.accounts,
        items: state.accounts.accounts,
      };
    }

    return state[key];
  },
})
export default class MarketPlaceQuickGuide extends React.Component {
  getCloseBtn = isCompleted => {
    return (
      <QuickGuideCloseBtn
        isCompleted={isCompleted}
        onClick={this.props.onClickClose}
      />
    );
  };

  render() {
    const { accountsStatus, transfersStatus } = getStatus(this.props);

    const CloseBtn = this.getCloseBtn(transfersStatus === done);

    let activeStep = 0;

    if (transfersStatus === done) {
      activeStep = 1;
    }

    return (
      <QuickStepGuide
        activeStep={activeStep}
        class="Route"
        title={Title}
        closeBtn={CloseBtn}
      >
        <Step
          status={accountsStatus}
          {...getQuickGuideData.LinkedAccount(accountsStatus)}
        />

        <Step
          status={transfersStatus}
          {...getQuickGuideData.Transfers(transfersStatus)}
        />
      </QuickStepGuide>
    );
  }
}

const Title = <QuickGuideTitle />;

export const getRouteQuickGuideIsClosed = props => {
  let isClosed = getQuickGuideIsClosedFromLocalStorage(RZPFeatures.ROUTE);

  // Check if transfers non created state count is more then or equal to 2
  if (isClosed || props.transfers.items.length <= 2) {
    return isClosed;
  }

  return true;
};

const getStatus = ({ accounts, transfers }) => {
  if (!transfers.items.length && !accounts.items.length && accounts.loading) {
    return {
      accountsStatus: loading,
      transfersStatus: loading,
    };
  }

  const accountsStatus = transfers.items.length
    ? done
    : accounts.items.length ? done : active;

  if (!transfers.items.length && transfers.loading) {
    return {
      accountsStatus: accountsStatus,
      transfersStatus: loading,
    };
  }

  let transfersStatus = locked;

  if (accountsStatus === done) {
    if (transfers.items.length) {
      transfersStatus = done;
    } else {
      transfersStatus = active;
    }
  }

  return {
    accountsStatus,
    transfersStatus,
  };
};
