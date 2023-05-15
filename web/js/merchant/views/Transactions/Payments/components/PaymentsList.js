import React from 'react';
import {
  getKeysSeparatedByPipe,
  getCommonAnalyticsProperties,
  getURLQueryParams,
} from 'common/utils/rzp-utils';
// eslint-disable-next-line no-restricted-imports
import HeaderAction from 'common/ui/HeaderAction';
import DocsLink from 'merchant/components/DocsLink';
import EmptyList from 'merchant/components/EmptyList';
import PaymentsTable from 'merchant/views/Transactions/Payments/components/PaymentsTable';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import PaymentsListFilter from 'merchant/views/Transactions/Payments/components/PaymentsListFilter';
import { analyticsTrack } from 'common/utils/analytics';
import {
  paymentId,
  amount,
  email,
  contact,
  createdAt,
  status,
  paymentReceiverType,
} from 'common/ui/item/pair';
import { isOrgFeatureExist } from 'merchant/models/User';
import ListContainer from 'merchant/containers/ListContainer';
import PaymentFailureAnalysis from './PaymentFailureAnalysis';
import { selfServerTrack, selfServeTrackResult } from 'merchant/views/Transactions/AnalyticsTrack';
import { makeIdLink } from 'merchant/views/Transactions/Payments/Utils';

const EmptyRoutesComponent = () => (
  <EmptyList
    description={
      <React.Fragment>
        <div>No route payments found for the selected duration and criteria!</div>
        <div>Create a linked account first to route payments.</div>
      </React.Fragment>
    }
  />
);

const EmptyComponent = () => {
  return (
    <EmptyList description={<div>No payments found for the selected duration and criteria!</div>} />
  );
};

const _paymentId = (initiatePage = 'Transactions.Payments') => {
  return {
    title: paymentId.title,
    value: (item) => {
      const intermediateElement = makeIdLink('payment')(item, initiatePage);
      return <div>{intermediateElement}</div>;
    },
  };
};

export default class PaymentsListContainer extends ListContainer {
  componentDidMount() {
    const { user, isRoute } = this.props;
    const { pathname } = this.props.location;
    /* istanbul ignore else */
    if (pathname && pathname.indexOf('route') < 0) {
      // Currently not tracking events from Route.
      /* istanbul ignore else */
      if (window.rzpAnalytics) {
        window.rzpAnalytics?.({
          eventCategory: 'Dashboard - Payments',
          eventAction: 'Go To - Payments',
        });
      }
    }
    /*
     As we are only Showing the Failure Analysis on the Trasaction Tab
     and this Component is comonly used Between Routes and Trasaction
     so added this check with the isRoute Prop as this is only true for the route Tab
    */
    if (!isRoute && user?.isFAEnabled) {
      this.fetchFailureAnalysisData();
    }
  }

  onSearchAnalytics = (params) => {
    const { pathname } = this.props.location;
    /* istanbul ignore else */
    if (pathname && pathname.indexOf('route') < 0) {
      // Currently not tracking events from Route.
      const label = getKeysSeparatedByPipe(params);
      /* istanbul ignore else */
      if (label && label.length > 0) {
        /* istanbul ignore else */
        if (window.rzpAnalytics) {
          window.rzpAnalytics?.({
            eventCategory: 'Dashboard - Payments',
            eventAction: 'Search - Payments',
            eventLabel: label,
          });
        }
        analyticsTrack({
          objectName: 'payments search',
          actionName: 'clicked',
          screen: 'transactions',
          properties: {
            paymentId: params.id,
            paymentStatus: params.status,
            location: 'payments',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      }
    }
  };

  onClearAnalytics = () => {
    const { pathname } = this.props.location;
    /* istanbul ignore else */
    if (pathname && pathname.indexOf('route') < 0) {
      // Currently not tracking events from Route.
      /* istanbul ignore else */
      if (window.rzpAnalytics) {
        window.rzpAnalytics?.({
          eventCategory: 'Dashboard - Payments',
          eventAction: 'Clear Search Params - Payments',
        });
      }
    }
  };

  getDefaultQueryParams = () => {
    const queryString = this.props?.location?.search;
    let params = null;
    /* istanbul ignore else */
    if (queryString) {
      params = getURLQueryParams(queryString);
      params = this.removeBlacklistedParams(params);
    }
    return params;
  };

  fetchFailureAnalysisData = (args) => {
    if (!args) {
      args = this.getDefaultQueryParams();
    }
    const faTextExp = this.props.user?.faTextVariant;
    // ignoring analizeFailure unit testing of analizeFailure as somehow it returns null instead of promise
    /* istanbul ignore next */
    this.analizeFailure(args)?.then((response) => {
      if (response?.status_code === 200) {
        const { data } = response;
        analyticsTrack({
          objectName: 'Failure Analysis displayed',
          actionName: 'displayed',
          screen: 'transactions',
          properties: {
            totalPayments: data.summary.number_of_total_payments,
            successfulPayments: data.summary.number_of_successful_payments,
            customerDropOffs: data.failure_details.customer_dropp_off,
            bankFailures: data.failure_details.bank_failure,
            otherFailures: data.failure_details.other_failure,
            businessFailures: data.failure_details.business_failure,
            experimentName: faTextExp,
            startDate: args?.from,
            endDate: args?.to,
            paymentStatus: args?.status,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      }
    });
  };

  doShowFA = () => {
    const { user, user_segment_data } = this.props;
    return (user && user_segment_data?.average_monthly_transactions <= user.getMaxFAMtv) || null;
  };

  getColumns = () => {
    const { selfServeActionsPage } = this.props;
    const initiatePage = selfServeActionsPage ? selfServeActionsPage : 'Transactions.Payments';
    const cols = [_paymentId(initiatePage), amount, email, contact, createdAt, status];
    const showReceiverType = isOrgFeatureExist('show_pmt_receiver_type');
    /* istanbul ignore else */
    if (showReceiverType) cols.splice(4, 0, paymentReceiverType);
    return cols;
  };

  render() {
    const { docUrl, quickTourFeature, isRoute, user, failureAnalysisData, terminalProviders } =
      this.props;

    return (
      <div className="content-wrapper">
        <HeaderAction>
          <div className="btn-toolbar pull-right">
            {quickTourFeature && <TakeATourButton feature={quickTourFeature} />}

            {docUrl && <DocsLink url={docUrl} />}
          </div>
        </HeaderAction>

        <PaymentsListFilter
          form="paymentListFilter"
          count={this.state.count}
          onSubmit={(args) => {
            // Only Needed to show FA on Trasaction Tab not in Routes Tab
            /* istanbul ignore else */
            if (!isRoute && user?.isFAEnabled) {
              this.fetchFailureAnalysisData(args);
            }
            selfServerTrack({ type: 'payment', actionType: 'search' });

            this.search(args)
              .then(() => {
                if (args.status) {
                  selfServeTrackResult({ type: 'payment', actionType: 'filter' });
                }
                selfServeTrackResult({ type: 'payment', actionType: 'search' });
                analyticsTrack({
                  objectName: 'payments search',
                  actionName: 'result',
                  screen: 'transactions',
                  properties: {
                    paymentId: args.id,
                    paymentStatus: args.status,
                    emailFilled: Boolean(args.email),
                    notesFilled: Boolean(args.notes),
                    count: args.count,
                    resultsReturned: true,
                    status: 'success',
                    location: 'payments',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
              })
              .catch(() => {
                analyticsTrack({
                  objectName: 'payments search',
                  actionName: 'result',
                  screen: 'transactions',
                  properties: {
                    paymentId: args.id,
                    paymentStatus: args.status,
                    emailFilled: Boolean(args.email),
                    notesFilled: Boolean(args.notes),
                    resultsReturned: false,
                    status: 'failure',
                    location: 'payments',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
              });
          }}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
          terminalProviders={terminalProviders}
          user={user}
        />
        {/* Only Needed to show FA on Trasaction Tab not in Routes Tab */}
        {!isRoute && user?.isFAEnabled && this.doShowFA() && failureAnalysisData?.data && (
          <PaymentFailureAnalysis data={failureAnalysisData?.data} user={user} />
        )}

        <PaymentsTable
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          EmptyComponent={isRoute ? EmptyRoutesComponent : EmptyComponent}
          paymentColumns={this.getColumns()}
          {...this.props}
        />
      </div>
    );
  }
}
