import React from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import {
  virtualAccountId,
  accountDescription,
  amountPaid,
  status,
  createdAt,
} from 'common/ui/item/pair';
import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';
import { RZPFeatures } from 'merchant/helpers/data';

import HeaderAction from 'common/ui/HeaderAction';
import DataTable from 'common/ui/Table/DataTable';

import { openModal, closeModal } from 'merchant_common/reducers/modals';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import { fetchVirtualAccounts as fetchAll } from 'merchant/reducers/virtualaccounts';

import ShowWhen from 'merchant/components/ShowWhen';
import DocsLink from 'merchant/components/DocsLink';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import VirtualAccountsListFilter from 'merchant/views/SmartCollect/VirtualAccounts/components/ListFilter';

import ListContainer from 'merchant/containers/ListContainer';
import TestModeBanner from 'merchant/components/TestModeBanner';

import OnBoarding, { getIsAllowedResetVAOnBoarding } from './OnBoarding';

import QuickGuide, { getVAQuickGuideIsClosed } from './QuickGuide';

import EmptyList from 'merchant/components/EmptyList';

@connect(
  state => {
    return {
      ...state.virtualaccounts,
      user: state.session.user,
      mode: state.session.mode,
      VAProductOnBoarding: getCurrentProductOnBoardingDetails(
        state,
        RZPFeatures.VA
      ),
    };
  },
  {
    fetchAll,
    openModal,
    closeModal,
    handleProductQuickGuide,
  }
)
export default class VirtualAccountsListContainer extends ListContainer {
  componentWillMount() {
    // TODO: Don't call below when feature is disbaled
    super.componentWillMount();

    this.initVAOnboarding();
  }

  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Smart Collect',
      eventAction: 'Go To - Smart Collect',
    });
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.loading != this.props.loading) {
      this.initVAOnboarding(nextProps);
    }
  }

  componentWillUnmount() {
    const { VAProductOnBoarding } = this.props;

    if (VAProductOnBoarding.isTour) {
      this.props.handleProductQuickGuide({
        ...VAProductOnBoarding,
        showOnboarding: false,
        isQuickGuideOpen: false,
        isTour: false,
      });
    }
  }

  initVAOnboarding = (props = this.props) => {
    if (props.VAProductOnBoarding.isTour) {
      return;
    }

    const data = {
      user: props.user,
      items: props.items,
      loading: props.loading,
    };

    const { isVirtualAccountsEnabled } = props.user;

    let showOnboarding = !isVirtualAccountsEnabled;

    if (isVirtualAccountsEnabled) {
      showOnboarding = getIsAllowedResetVAOnBoarding(data);
    }

    let VAProductOnBoarding = {
      ...props.VAProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen: !getVAQuickGuideIsClosed(props),
    };

    this.props.handleProductQuickGuide(VAProductOnBoarding);
  };

  onSearchAnalytics = params => {
    const label = getKeysSeparatedByPipe(params);
    if (label && label.length > 0) {
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Smart Collect',
        eventAction: 'Search - Virtual Accounts',
        eventLabel: label,
      });
    }
  };

  onClearAnalytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Smart Collect',
      eventAction: 'Clear Search Params - Virtual Accounts',
    });
  };

  /*
  showCreateVAModal = () => {
    this.props.openModal({
      size: 'medium',
      className: 'create-virtual-account',
      component: (
        <CreateVirtualAccount
          showCreateVAModal={this.showCreateVAModal}
          onCopy={this.onCopy}
        />
      ),
    });
  };
*/

  render() {
    const { isQuickGuideOpen, showOnboarding } = this.props.VAProductOnBoarding;
    const { items } = this.props;

    if (showOnboarding) {
      return <OnBoarding />;
    }

    return (
      <React.Fragment>
        <tabbed-container>
          {isQuickGuideOpen && <QuickGuide />}

          <header id="#va-header">
            <NavLink to="/virtualaccounts">Virtual Accounts</NavLink>

            <HeaderAction>
              <div class="btn-toolbar">
                <TakeATourButton feature={RZPFeatures.VA} />

                <DocsLink url="https://razorpay.com/docs/smart-collect/" />

                <ShowWhen
                  additionalCondition={user =>
                    user.isAllowedEdit('virtual_accounts')
                  }
                >
                  <NavLink class="btn btn-primary" to="/virtualaccounts/new">
                    <i class="i i-plus" />
                    <span>Create Virtual Account</span>
                  </NavLink>
                </ShowWhen>
              </div>
            </HeaderAction>
          </header>

          <TestModeBanner />

          <content>
            <div class="content-wrapper">
              <VirtualAccountsListFilter
                form="virtualAccountsListFilter"
                count={this.state.count}
                onSubmit={this.search}
                onSearchAnalytics={this.onSearchAnalytics}
                onClearAnalytics={this.onClearAnalytics}
              />

              <DataTable
                title="Virtual Accounts"
                columns={[
                  virtualAccountId,
                  accountDescription,
                  amountPaid,
                  status,
                  createdAt,
                ]}
                count={this.state.count}
                skip={this.state.skip}
                paginate={this.paginate}
                EmptyComponent={EmptyComponent}
                {...this.props}
              />
            </div>
          </content>
        </tabbed-container>
      </React.Fragment>
    );
  }
}

const EmptyComponent = () => (
  <EmptyList
    description={
      <React.Fragment>
        <div>There are no virtual accounts yet!!</div>
        <div>Start creating new account now.</div>
      </React.Fragment>
    }
  />
);
