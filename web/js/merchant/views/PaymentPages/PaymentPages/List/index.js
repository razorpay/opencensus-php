import React, { createRef } from 'react';
import axios from 'axios';
import { connect } from 'react-redux';
import { Field } from 'redux-form';
import ProductWrapper from 'common/ui/ProductWrapper';
import RTracking from 'react-tracking';
import track from './track';
import Pager from 'common/ui/Pager';
import Spinner from 'common/ui/Spinner';
import { withRouter } from 'common/deprecated/withRouter';
import ListContainer from 'merchant/containers/ListContainer';
import ListFilter from 'merchant/components/ListFilter';
import ShowWhen from 'merchant/components/ShowWhen';
import EmptyList from 'merchant/components/EmptyList';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import { DocLink } from 'merchant/components/DocsLink';
import List from './List';
import { populateRPLReduxList, populateStorefrontReduxList } from 'merchant/reducers/invoices/list';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import {
  getIsPaymentPagesEnabled,
  getIsAllowedPaymentPagesResetOnBoarding,
} from 'merchant/views/PaymentPages/OnBoarding';
import { getPaymentPageQuickGuideIsClosed } from 'merchant/views/PaymentPages/QuickGuide';
import {
  fetchPaymentPagesList,
  fetchStorefrontList,
} from 'merchant/views/PaymentPages/PaymentPages/model';
import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';
import { showNotification } from 'merchant_common/reducers/notifications';
import { trackListActions } from 'merchant/views/PaymentPages/PaymentPages/ga';
import { RZPFeatures } from 'merchant/helpers/data';
import TestModeBanner from 'merchant/components/TestModeBanner';
import PaymentsAndStorefrontTab from './PaymentsAndStorefrontTab';
import { setIsStorefrontPage } from 'merchant/reducers/paymentPages/storefront';
import {
  CREATE_PP_DOC_URL,
  CREATE_BATCH_PP_DOC_URL,
  BATCH_PAYMENT_PAGES_BASE_URL,
} from 'merchant/views/PaymentPages/PaymentPages/constants';

@connect(
  (state) => ({
    ...state.invoices,
    ...state.session,
    isStorefrontPage: state.paymentPageStorefront.isStorefrontPage,
    paymentPageProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.PP),
  }),
  {
    showNotification,
    populateRPLReduxList,
    populateStorefrontReduxList,
    handleProductQuickGuide,
    setIsStorefrontPage,
  },
)
@RTracking(() => window.rzpQ.component('PaymentPagesContainer'))
class PaymentPagesContainer extends ListContainer {
  constructor(props) {
    super(props);
    const { user } = props;
    this.state = {
      loading: false,
      isBatchPagesLoading: false,
      loadingAllList: true,
      tabsData: [
        {
          title: 'Payment Pages',
          url: '/paymentpages',
        },
        {
          title: 'Products',
          url: '/paymentpages/products',
          // razorx doesn't change during the component lifecycle
          hidden: !user.isPaymentPageStorefrontEnabled,
        },
        {
          title: 'Batch Payment Pages',
          url: BATCH_PAYMENT_PAGES_BASE_URL,
          hidden: !user?.isPaymentPageFileUploadEnabled,
        },
      ],
    };
    this.cancelToken = createRef();
  }

  componentDidMount() {
    this.fetchAllEntityList();
    this.initPaymentPagesOnboarding();

    track.init(this.props.tracking.trackEvent);
    track.load();
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    const { isStorefrontPage, loading, isBatchPaymentPages } = this.props;

    if (
      nextProps.isStorefrontPage !== isStorefrontPage ||
      nextProps.isBatchPaymentPages !== isBatchPaymentPages
    ) {
      this.setState(
        {
          skip: '0',
          count: '25',
        },
        () => {
          this.fetchEntityList({
            count: this.state.count,
            skip: this.state.skip,
          });
        },
      );
    }

    if (nextProps.loading !== loading) {
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

        this.initPaymentPagesOnboarding();

        return resp;
      })
      .catch(() => {});
  }

  fetchEntityList(params) {
    const {
      user,
      populateStorefrontReduxList,
      showNotification,
      populateRPLReduxList,
      isBatchPaymentPages,
    } = this.props;
    const { isPaymentPageStorefrontEnabled } = user;

    params.view_type = isBatchPaymentPages ? 'file_upload_page' : undefined;

    if (isPaymentPageStorefrontEnabled) {
      fetchStorefrontList(params)
        .then((resp) => {
          if (resp.data) {
            populateStorefrontReduxList(resp);
          }
          return resp;
        })
        .catch((err) => {
          showNotification({
            type: 'error',
            message: err.errors,
          });
        });
    }

    this.setState({ [isBatchPaymentPages ? 'isBatchPagesLoading' : 'loading']: true });

    this.cancelToken.current?.cancel();
    this.cancelToken.current = axios.CancelToken.source();

    return fetchPaymentPagesList(params, { cancelToken: this.cancelToken.current.token })
      .then((resp) => {
        if (resp.data) {
          populateRPLReduxList(resp);
        }

        this.initPaymentPagesOnboarding();

        return resp;
      })
      .catch((err) => {
        if (err.errors.join('')) {
          showNotification({
            type: 'error',
            message: err.errors.join(', '),
          });
        }
      })
      .finally(() => {
        this.setState({ [isBatchPaymentPages ? 'isBatchPagesLoading' : 'loading']: false });
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

    // Cancel out the request when component unmounts
    this.cancelToken.current?.cancel();
  }

  handleProductQuickGuide = () => {
    const { isBatchPaymentPages, history } = this.props;
    const url = isBatchPaymentPages ? `${BATCH_PAYMENT_PAGES_BASE_URL}/new` : `/paymentpages/new`;

    this.setState({ isPaymentPageWysiwyg: true }, () => {
      history.push(url);
    });
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
    const { loading, loadingAllList, isBatchPagesLoading } = this.state;
    const {
      paymentPages,
      user,
      totalPaymentPagesLength,
      totalStorefrontLength,
      storefrontPages,
      isStorefrontPage,
      isBatchPaymentPages,
    } = this.props;
    const entityList = isStorefrontPage ? storefrontPages : paymentPages;
    const isRoleAllowedEdit = user.isAllowedEdit('payment_pages');

    let content;

    if (loadingAllList) {
      content = (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    } else if (isRoleAllowedEdit && !entityList.length) {
      content = <EmptyComponent isStorefrontPage={isStorefrontPage} />;
    } else {
      const isListLoading = loading || isBatchPagesLoading;

      content = (
        <React.Fragment>
          <List
            loading={isListLoading}
            paymentPages={entityList}
            isStorefrontPage={isStorefrontPage}
            isBatchPaymentPages={isBatchPaymentPages}
          />
          {!isListLoading && !!entityList.length && (
            <Pager
              count={this.state.count}
              skip={this.state.skip}
              length={entityList.length}
              onClick={this.onClickPaginate}
            />
          )}
        </React.Fragment>
      );
    }
    const docLink = isBatchPaymentPages ? CREATE_BATCH_PP_DOC_URL : CREATE_PP_DOC_URL;
    return (
      <ProductWrapper
        tabsData={this.state.tabsData}
        extra={
          <>
            <ShowWhen additionalCondition={() => !user.isOrgAxis}>
              <TakeATourButton feature={RZPFeatures.PP} onClick={track.takeTour} />
            </ShowWhen>

            <ShowWhen additionalCondition={() => user.isOrgAllowedFunctionality('external_links')}>
              <DocLink
                className="btn btn-link"
                href={docLink}
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
          {user.isPaymentPageStorefrontEnabled && !isBatchPaymentPages && (
            <PaymentsAndStorefrontTab
              totalPaymentPagesLength={totalPaymentPagesLength}
              totalStorefrontLength={totalStorefrontLength}
              isStorefrontPage={isStorefrontPage}
              setIsStorefrontPage={this.props.setIsStorefrontPage}
            />
          )}
          <div class="content-wrapper">
            <TestModeBanner />
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
            {content}
          </div>
        </content>
      </ProductWrapper>
    );
  }
}

const EmptyComponent = ({ isStorefrontPage }) => (
  <EmptyList
    description={
      <React.Fragment>
        <div>There are no {isStorefrontPage ? 'storefront' : 'payment'} pages yet!!</div>
        <div>Start creating new pages now.</div>
      </React.Fragment>
    }
  />
);

export default withRouter(PaymentPagesContainer);
