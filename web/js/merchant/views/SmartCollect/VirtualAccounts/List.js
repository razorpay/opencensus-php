import React from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import RTracking from 'react-tracking';

import {
  virtualAccountId,
  accountDescription,
  amountPaid,
  status,
  createdAt,
} from 'common/ui/item/pair';
import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';
import { RZPFeatures } from 'merchant/helpers/data';

import ProductWrapper from 'common/ui/ProductWrapper';
import TestModeBanner from 'merchant/components/TestModeBanner';
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

import { getIsAllowedResetVAOnBoarding } from 'merchant/views/SmartCollect/OnBoarding';

import { getVAQuickGuideIsClosed } from 'merchant/views/SmartCollect/QuickGuide';

import EmptyList from 'merchant/components/EmptyList';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { checkIfVirtualAccountRoute } from 'merchant/views/SmartCollect/utils';

const EmptyComponent = () => (
  <EmptyList
    description={
      <React.Fragment>
        <div>There are no customer identifiers yet!!</div>
        <div>Start creating a new customer identifier now.</div>
      </React.Fragment>
    }
  />
);

@connect(
  (state) => {
    return {
      ...state.virtualaccounts,
      user: state.session.user,
      mode: state.session.mode,
      VAProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.VA),
      isVaEditBulkMid: state.virtualaccount.isVaEditBulkMid,
    };
  },
  {
    fetchAll,
    openModal,
    closeModal,
    handleProductQuickGuide,
  },
)
@RTracking(() => window.rzpQ.component('VirtualAccountsListContainer'))
export default class VirtualAccountsListContainer extends ListContainer {
  constructor(props) {
    super(props);
    this.state = {
      tabsData: [
        {
          title: 'Customer Identifiers',
          url: '/smartcollect/virtualaccounts',
          isActive: checkIfVirtualAccountRoute,
        },
        { title: 'Payments', url: '/smartcollect/payments' },
        {
          title: 'Batch Expiry Update',
          url: '/smartcollect/batchuploads',
          hidden: !props.isVaEditBulkMid,
        },
      ],
    };
  }
  UNSAFE_componentWillMount() {
    // TODO: Don't call below when feature is disbaled
    //eslint-disable-next-line
    super.UNSAFE_componentWillMount();

    this.initVAOnboarding();
  }

  componentDidMount() {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Smart Collect',
      eventAction: 'Go To - Virtual Accounts',
    });

    this.track('loaded');

    const { isVirtualAccountsEnabled } = this.props.user;

    if (!isVirtualAccountsEnabled) {
      this.track('onboarding.first_time');
    }
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (nextProps.loading != this.props.loading) {
      this.initVAOnboarding(nextProps);
    }
    if (nextProps.isVaEditBulkMid != this.props.isVaEditBulkMid) {
      this.setState({
        tabsData: [
          {
            title: 'Customer Identifiers',
            url: '/smartcollect/virtualaccounts',
            isActive: checkIfVirtualAccountRoute,
          },
          { title: 'Payments', url: '/smartcollect/payments' },
          {
            title: 'Batch Expiry Update',
            url: '/smartcollect/batchuploads',
            hidden: !nextProps.isVaEditBulkMid,
          },
        ],
      });
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

  track = (event, options) => {
    this.props.tracking.trackEvent(
      window.rzpQ.smartCollect().interaction(`smartcollect.va.${event}`, options),
    );
  };

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

    const VAProductOnBoarding = {
      ...props.VAProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen: !getVAQuickGuideIsClosed(props),
    };

    this.props.handleProductQuickGuide(VAProductOnBoarding);
  };

  onSearchAnalytics = (params) => {
    const label = getKeysSeparatedByPipe(params);
    if (label && label.length > 0) {
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Smart Collect',
        eventAction: 'Search - Virtual Accounts',
        eventLabel: label,
      });
    }

    this.track('search.submit');
  };

  onClearAnalytics = () => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Smart Collect',
      eventAction: 'Clear Search Params - Virtual Accounts',
    });

    this.track('search.clear');
  };

  onSearchEleBlur = (name) => (e) => {
    this.track(`search.${name}`, { value: e.target.value });
  };

  onErrorCloseClick = () => {
    this.track('search.error_close', {
      response: this.state.status.message[0],
    });
  };

  onSearchSubmit = (...args) => {
    this.search(...args)
      .then(() => {
        this.track('search.success');
      })
      .catch((error) => {
        this.track('search.error', {
          response: error.errors[0],
        });
      });
  };

  render() {
    const { tabsData } = this.state;
    return (
      <ProductWrapper
        tabsData={tabsData}
        extra={
          <>
            <TakeATourButton
              feature={RZPFeatures.VA}
              onClick={() => this.track('tour')}
              onSuccess={() => this.track('tour.yes')}
              onAbort={() => this.track('tour.no')}
            />

            <DocsLink
              url="https://razorpay.com/docs/smart-collect/"
              onClick={() => {
                this.track('docs');
              }}
            />

            <ShowWhen additionalCondition={(user) => user.isAllowedEdit('virtual_accounts')}>
              <span className="tabbed-header-actions">
                <span className="cta-container">
                  <NavLink
                    class="btn btn-primary"
                    to="/smartcollect/virtualaccounts/new"
                    onClick={() => {
                      selfServeTrackInitiate({
                        selfServeAction: 'Customer Identifier Created',
                        page: 'Virtualaccounts',
                        screen: 'Smart Collect',
                      });
                      this.track('create');
                    }}
                  >
                    <i class="i i-plus" />
                    <span>Create Customer Identifier</span>
                  </NavLink>
                </span>
              </span>
            </ShowWhen>
          </>
        }
      >
        <content>
          <div class="content-wrapper">
            <TestModeBanner />

            <VirtualAccountsListFilter
              form="virtualAccountsListFilter"
              count={this.state.count}
              onSubmit={this.onSearchSubmit}
              onEleBlur={this.onSearchEleBlur}
              onSearchAnalytics={this.onSearchAnalytics}
              onClearAnalytics={this.onClearAnalytics}
            />

            <DataTable
              title="Customer Identifiers"
              columns={[virtualAccountId, accountDescription, amountPaid, status, createdAt]}
              count={this.state.count}
              skip={this.state.skip}
              EmptyComponent={EmptyComponent}
              {...this.props}
              paginate={(params, type) => {
                this.track(`list.${type}`, {
                  page: params.skip % params.count,
                });

                this.paginate(params);
              }}
              onErrorCloseClick={this.onErrorCloseClick}
            />
          </div>
        </content>
      </ProductWrapper>
    );
  }
}
