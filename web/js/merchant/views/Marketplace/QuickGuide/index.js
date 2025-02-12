import React from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

import QuickGuide, {
  setQuickGuideIsClosedInLocalStorage,
  getQuickGuideIsClosedFromLocalStorage,
} from 'merchant/components/QuickGuide';
import QuickStepGuide, {
  QuickGuideStep,
  QuickGuideTitle,
  QuickGuideCloseBtn,
} from 'merchant/components/QuickGuide/QuickStepGuide';
import { RZPFeatures, PossibleStatuses } from 'merchant/helpers/data';

import { getQuickGuideData } from './data';

const { done, locked, active, loading } = PossibleStatuses;

const Title = <QuickGuideTitle />;

export const getRouteQuickGuideIsClosed = (props) => {
  const isClosed = getQuickGuideIsClosedFromLocalStorage(RZPFeatures.ROUTE);

  if (isClosed || props.transfers.items.length <= 2 || props.accounts.length <= 2) {
    return isClosed;
  }

  if (props.transfers.items.length) {
    setQuickGuideIsClosedInLocalStorage(RZPFeatures.ROUTE, true);
  }

  return true;
};

const getStatus = ({ accounts, transfers }) => {
  if (transfers.loading && accounts.loading) {
    return {
      accountsStatus: loading,
      transfersStatus: loading,
    };
  }

  let accountsStatus = active;

  if (transfers.items.length) {
    accountsStatus = done;
  } else {
    accountsStatus = accounts.items.length ? done : active;
  }

  if (transfers.loading) {
    return {
      accountsStatus,
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

class MarketPlaceQuickGuide extends React.Component {
  getCloseBtn = (isCompleted) => {
    return <QuickGuideCloseBtn isCompleted={isCompleted} onClick={this.props.onClickClose} />;
  };

  render() {
    const { accountsStatus, transfersStatus } = getStatus(this.props);

    const closeBtn = this.getCloseBtn(transfersStatus === done);

    let activeStep = 0;

    if (transfersStatus === done) {
      activeStep = 1;
    }

    return (
      <QuickStepGuide activeStep={activeStep} className="Route" title={Title} closeBtn={closeBtn}>
        <QuickGuideStep
          status={accountsStatus}
          step="Account"
          feature={RZPFeatures.ROUTE}
          {...getQuickGuideData.LinkedAccount(accountsStatus)}
        />

        <QuickGuideStep
          status={transfersStatus}
          step="Transfers"
          feature={RZPFeatures.ROUTE}
          {...getQuickGuideData.Transfers(transfersStatus)}
        />
      </QuickStepGuide>
    );
  }
}

export default compose(
  connect((state) => ({
    user: state.session.user,
    mode: state.session.mode,
  })),
  /* eslint-disable */
  QuickGuide({
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
  }),
)(MarketPlaceQuickGuide);
