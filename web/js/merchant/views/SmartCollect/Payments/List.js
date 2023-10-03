import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { RZPFeatures } from 'merchant/helpers/data';
import { getKeysSeparatedByPipe, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import { paymentId, amount, email, contact, createdAt, status } from 'common/ui/item/pair';
import ProductWrapper from 'common/ui/ProductWrapper';
import TestModeBanner from 'merchant/components/TestModeBanner';
import DocsLink from 'merchant/components/DocsLink';
import ListContainer from 'merchant/containers/ListContainer';
import { withRouter } from 'common/deprecated/withRouter';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import PaymentsTable from 'merchant/views/Transactions/v1/Payments/components/PaymentsTable';
import PaymentsListFilter from './Filter';
import { fetchSmartCollectPayments as fetchAll } from 'merchant/reducers/collection';
import { makeIdLink } from 'merchant/views/Transactions/v1/Payments/Utils';
import { SelfServeActionPages } from 'common/constant/enums';
import { checkIfVirtualAccountRoute } from 'merchant/views/SmartCollect/utils';

@connect(
  (state) => ({
    ...state.scPayments,
    user: state.session.user,
    isVaEditBulkMid: state.virtualaccount.isVaEditBulkMid,
  }),
  { fetchAll },
)
@RTracking(() => window.rzpQ.component('VAPaymentsListContainer'))
class VAPaymentsListContainer extends ListContainer {
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
  componentDidMount() {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Smart Collect',
      eventAction: 'Go To - VA Payments',
    });

    this.track('loaded');
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
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

  track = (event, options) => {
    this.props.tracking.trackEvent(
      window.rzpQ.smartCollect().interaction(`smartcollect.payments.${event}`, options),
    );
  };

  onSearchAnalytics = (params) => {
    const label = getKeysSeparatedByPipe(params);
    if (label && label.length > 0) {
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - VA Payments',
        eventAction: 'Search - Payments',
        eventLabel: label,
      });
    }

    this.track('search.submit');
  };

  onClearAnalytics = () => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - VA Payments',
      eventAction: 'Clear Search Params - Payments',
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
        this.track('search.fail', {
          response: error.errors[0],
        });
      });
  };

  trackPaymentIdCol = ({ id, paymentStatus }) => {
    return () => {
      const options = { id, paymentStatus };

      this.track('list.payment_id', options);

      // TODO: Move all tracking to one file
      analyticsTrack({
        objectName: 'payment id',
        actionName: 'clicked',
        screen: 'Smart Collect Payments',
        properties: {
          ...getCommonAnalyticsProperties(window.rzp_user),
          ...options,
        },
      });
    };
  };

  get paymentIdCol() {
    return {
      title: paymentId.title,
      value: (item) => {
        const intermediateElement = makeIdLink('payment')(
          item,
          SelfServeActionPages.SmartcollectPayments,
        );
        return <div>{intermediateElement}</div>;
      },
    };
  }

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
          </>
        }
      >
        <content>
          <div class="content-wrapper">
            <TestModeBanner />
            <PaymentsListFilter
              form="paymentListFilter"
              count={this.state.count}
              onSearchAnalytics={this.onSearchAnalytics}
              onClearAnalytics={this.onClearAnalytics}
              onSubmit={this.onSearchSubmit}
              onEleBlur={this.onSearchEleBlur}
            />

            <PaymentsTable
              count={this.state.count}
              skip={this.state.skip}
              {...this.props}
              paginate={(params, type) => {
                this.track(`list.${type}`, {
                  page: params.skip % params.count,
                });

                this.paginate(params);
              }}
              onErrorCloseClick={this.onErrorCloseClick}
              paymentColumns={[this.paymentIdCol, amount, email, contact, createdAt, status]}
              selfServeActionsPage={SelfServeActionPages.SmartcollectPayments}
            />
          </div>
        </content>
      </ProductWrapper>
    );
  }
}

export default withRouter(VAPaymentsListContainer);
