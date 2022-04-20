import React from 'react';
import { connect } from 'react-redux';
import { Field } from 'redux-form';
import ProductWrapper from 'common/ui/ProductWrapper';
import RTracking from 'react-tracking';
import track from './track';
import Pager from 'common/ui/Pager';
import Spinner from 'common/ui/Spinner';
import { withRouter } from 'react-router-dom';
import ListContainer from 'merchant/containers/ListContainer';
import ListFilter from 'merchant/components/ListFilter';
import ShowWhen from 'merchant/components/ShowWhen';
import EmptyList from 'merchant/components/EmptyList';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import { DocLink } from 'merchant/components/DocsLink';
import List from './List';
import { populateRPLReduxList } from 'merchant/reducers/invoices/list';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import {
  getIsPaymentPagesEnabled,
  getIsAllowedPaymentPagesResetOnBoarding,
} from '../../OnBoarding';
import { getPaymentPageQuickGuideIsClosed } from '../../QuickGuide';
import { fetchPaymentPagesList } from '../model';
import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';
import { showNotification } from 'merchant_common/reducers/notifications';
import { trackListActions } from '../ga';
import { RZPFeatures } from 'merchant/helpers/data';
import TestModeBanner from 'merchant/components/TestModeBanner';

const tabsData = [{ title: 'Payment Pages', url: '/paymentpages' }];

@withRouter
@connect(
  (state) => ({
    ...state.invoices,
    ...state.session,
    paymentPageProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.PP),
  }),
  {
    showNotification,
    populateRPLReduxList,
    handleProductQuickGuide,
  },
)
@RTracking(() => window.rzpQ.component('PaymentPagesContainer'))
export default class PaymentPagesContainer extends ListContainer {
  state = {
    loading: true,
    loadingAllList: true,
  };

  componentDidMount() {
    this.fetchAllEntityList();
    this.initPaymentPagesOnboarding();

    track.init(this.props.tracking.trackEvent);
    track.load();
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.paymentPages.length !== nextProps.paymentPages.length) {
      const newLength = nextProps.paymentPages.length;

      if (newLength) {
        this.setState({
          totalPaymentPagesLength: newLength,
        });
      }
    }

    if (nextProps.loading !== this.props.loading) {
      this.initPaymentPagesOnboarding(nextProps);
    }

    // eslint-disable-next-line babel/new-cap
    super.UNSAFE_componentWillReceiveProps(nextProps);
  }

  /* Fetch all payment pages list to find whether first-time user */
  fetchAllEntityList() {
    fetchPaymentPagesList({
      count: 1,
    })
      .then((resp) => {
        this.setState({
          loadingAllList: false,
        });

        if (resp.data) {
          this.setState({
            totalPaymentPagesLength: resp.data.items.length,
          });
        }

        this.initPaymentPagesOnboarding();

        return resp;
      })
      .catch(() => {});
  }

  fetchEntityList(params) {
    return fetchPaymentPagesList(params)
      .then((resp) => {
        if (resp.data) {
          this.props.populateRPLReduxList(resp);
        }

        this.setState({ loading: false });

        this.initPaymentPagesOnboarding();

        return resp;
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });

        this.setState({ loading: false });
      });
  }

  onSearchAnalytics = (params) => {
    const label = getKeysSeparatedByPipe(params);
    if (label && label.length > 0) {
      trackListActions('Search', label);
    }

    track.search(params);
  };

  onClearAnalytics = () => {
    trackListActions('Clear');

    track.searchClear();
  };

  componentWillUnmount() {
    const { paymentPageProductOnBoarding } = this.props;

    if (paymentPageProductOnBoarding.isTour) {
      this.props.handleProductQuickGuide({
        ...paymentPageProductOnBoarding,
        showOnboarding: false,
        isQuickGuideOpen: this.state.isPaymentPageWysiwyg,
        isTour: this.state.isPaymentPageWysiwyg,
      });
    }
  }

  handleProductQuickGuide = () => {
    this.setState(
      {
        isPaymentPageWysiwyg: true,
      },
      () => {
        this.props.history.push('/paymentpages/new');
      },
    );
  };

  initPaymentPagesOnboarding = (props = this.props) => {
    if (props.paymentPageProductOnBoarding.isTour) {
      return;
    }

    const data = {
      user: props.user,
      paymentPages: props.paymentPages,
      loading: this.state.loading || this.state.loadingAllList,
    };

    const isPaymentPagesEnabled = getIsPaymentPagesEnabled(data);

    let showOnboarding = !isPaymentPagesEnabled;

    if (isPaymentPagesEnabled) {
      showOnboarding = getIsAllowedPaymentPagesResetOnBoarding(data);
    }

    const paymentPageProductOnBoarding = {
      ...props.paymentPageProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen: !getPaymentPageQuickGuideIsClosed(props),
    };

    this.props.handleProductQuickGuide(paymentPageProductOnBoarding);
  };

  trackCreatePaymentPage = () => {
    this.props.tracking.trackEvent(
      window.rzpQ.onbr().success('dash.pp_action', {
        action: 'Initiate_PP_Creation',
      }),
    );

    track.createPaymentPage();
  };

  onClickPaginate = (params, type) => {
    track.paginate(type, {
      count: params.count,
    });

    this.paginate(params);
  };

  render() {
    const { loading, loadingAllList, totalPaymentPagesLength } = this.state;
    const { paymentPages, user } = this.props;

    const isRoleAllowedEdit = user.isAllowedEdit('payment_pages');

    let content;

    if (loadingAllList) {
      content = (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    } else if (
      isRoleAllowedEdit &&
      !loadingAllList &&
      !totalPaymentPagesLength &&
      !paymentPages.length
    ) {
      // !paymentPages check is required so that while creation first time, the list would be updated while totalPaymentPagesLength still = 0
      content = <EmptyComponent />;
    } else {
      content = (
        <React.Fragment>
          <ListFilter
            form="PaymentPagesPaymentListFilter"
            count={this.state.count}
            onSearchAnalytics={this.onSearchAnalytics}
            onClearAnalytics={this.onClearAnalytics}
          >
            <div class="form-group list-filter-item">
              <label>Title</label>
              <Field
                name="title"
                component="input"
                class="form-control input-sm"
                onBlur={track.searchTitle}
              />
            </div>

            <div class="form-group list-filter-item">
              <label>Status</label>
              <Field
                name="status"
                component="select"
                class="form-control input-sm"
                onChange={track.searchStatus}
              >
                <option value="">All</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </Field>
            </div>

            <div class="form-group list-filter-item count">
              <label>Count</label>
              <Field
                name="count"
                component="input"
                min={1}
                max={100}
                type="number"
                class="form-control input-sm"
                onBlur={track.searchCount}
              />
            </div>
          </ListFilter>
          <List loading={loading} paymentPages={paymentPages} />
          {!loading && !!paymentPages.length && (
            <Pager
              count={this.state.count}
              skip={this.state.skip}
              length={paymentPages.length}
              onClick={this.onClickPaginate}
            />
          )}
        </React.Fragment>
      );
    }

    return (
      <ProductWrapper
        tabsData={tabsData}
        extra={
          <>
            <ShowWhen additionalCondition={() => !user.isOrgAxis}>
              <TakeATourButton feature={RZPFeatures.PP} onClick={track.takeTour} />
            </ShowWhen>

            <ShowWhen additionalCondition={() => user.isOrgAllowedFunctionality('external_links')}>
              <DocLink
                class="btn btn-link"
                href="https://razorpay.com/docs/payment-pages/"
                target="_blank"
                onClick={track.viewDoc}
              >
                Documentation&nbsp;
                <i class="i i-external-link" />
              </DocLink>
            </ShowWhen>

            {isRoleAllowedEdit && (
              <span class="cta-container">
                <span class="btn btn-primary" onClick={this.handleProductQuickGuide}>
                  <i class="i i-plus" />
                  <span onClick={this.trackCreatePaymentPage}>Create Payment Page</span>
                </span>
              </span>
            )}
          </>
        }
      >
        <content>
          <div class="content-wrapper">
            <TestModeBanner />
            {content}
          </div>
        </content>
      </ProductWrapper>
    );
  }
}

const EmptyComponent = () => (
  <EmptyList
    description={
      <React.Fragment>
        <div>There are no payment pages yet!!</div>
        <div>Start creating new links now.</div>
      </React.Fragment>
    }
  />
);
