import React from 'react';
import { connect } from 'react-redux';
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
import { withRouter } from 'common/deprecated/withRouter';

import { getIsAllowedResetVAOnBoarding } from 'merchant/views/SmartCollect/OnBoarding';

import { getVAQuickGuideIsClosed } from 'merchant/views/SmartCollect/QuickGuide';

import EmptyList from 'merchant/components/EmptyList';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { checkIfVirtualAccountRoute } from 'merchant/views/SmartCollect/utils';
import { Button, PlusIcon, Box } from '@razorpay/blade/components';
import { NEW_CUSTOMER_IDENTIFER_URL } from 'merchant/constants/urls';
import { FEE_BEARER_TYPES } from 'merchant/constants/feeBearer';
import CustomerFeeBearerPopover from 'merchant/components/CustomerFeeBearerPopover';

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
class VirtualAccountsListContainer extends ListContainer {
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

  onCreateCustomerIdentifier = () => {
    const history = this.props.history;
    history.push(NEW_CUSTOMER_IDENTIFER_URL);

    selfServeTrackInitiate({
      selfServeAction: 'Customer Identifier Created',
      page: 'Virtualaccounts',
      screen: 'Smart Collect',
    });
    this.track('create');
  };

  render() {
    const { tabsData } = this.state;
    const feeBearer = this.props.user.merchant.fee_bearer;
    const isSmartCollectDisabled = feeBearer === FEE_BEARER_TYPES.CUSTOMER;

    return (
      <ProductWrapper
        tabsData={tabsData}
        extra={
          <Box display="flex" alignItems="center">
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
              <Box display="inline-block">
                <Button
                  onClick={this.onCreateCustomerIdentifier}
                  icon={PlusIcon}
                  iconPosition="left"
                  size="small"
                  type="button"
                  variant="primary"
                  isDisabled={isSmartCollectDisabled}
                >
                  Create Customer Identifier
                </Button>
                {isSmartCollectDisabled && <CustomerFeeBearerPopover feature="Smart Collect" />}
              </Box>
            </ShowWhen>
          </Box>
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
              progressLoader={true}
            />
          </div>
        </content>
      </ProductWrapper>
    );
  }
}

export default withRouter(VirtualAccountsListContainer);
