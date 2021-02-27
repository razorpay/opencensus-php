import React from 'react';
import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';
import HeaderAction from 'common/ui/HeaderAction';
import DocsLink from 'merchant/components/DocsLink';
import EmptyList from 'merchant/components/EmptyList';
import PaymentsTable from 'merchant/views/Transactions/Payments/components/PaymentsTable';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import PaymentsListFilter from 'merchant/views/Transactions/Payments/components/PaymentsListFilter';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import ListContainer from 'merchant/containers/ListContainer';

const EmptyComponent = () => (
  <EmptyList
    description={
      <React.Fragment>
        <div>There are no payments yet!!</div>
        <div>Create a linked account first to route payments.</div>
      </React.Fragment>
    }
  />
);

export default class PaymentsListContainer extends ListContainer {
  componentDidMount() {
    const { pathname } = this.props.location;
    if (pathname && pathname.indexOf('route') < 0) {
      // Currently not tracking events from Route.
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Payments',
        eventAction: 'Go To - Payments',
      });
    }
  }

  onSearchAnalytics = (params) => {
    const { pathname } = this.props.location;
    if (pathname && pathname.indexOf('route') < 0) {
      // Currently not tracking events from Route.
      const label = getKeysSeparatedByPipe(params);
      if (label && label.length > 0) {
        window.rzpAnalytics({
          eventCategory: 'Dashboard - Payments',
          eventAction: 'Search - Payments',
          eventLabel: label,
        });
        analyticsTrack({
          objectName: 'payments search',
          actionName: 'clicked',
          screen: 'transactions',
          properties: {
            paymentId: params.id,
            paymentStatus: params.status,
            emailFilled: Boolean(params.email),
            notesFilled: Boolean(params.notes),
            location: 'payments',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      }
    }
  };

  onClearAnalytics = () => {
    const { pathname } = this.props.location;
    if (pathname && pathname.indexOf('route') < 0) {
      // Currently not tracking events from Route.
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Payments',
        eventAction: 'Clear Search Params - Payments',
      });
    }
  };

  render() {
    const { docUrl, quickTourFeature, isRoute } = this.props;

    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            {quickTourFeature && <TakeATourButton feature={quickTourFeature} />}

            {docUrl && <DocsLink url={docUrl} />}
          </div>
        </HeaderAction>

        <PaymentsListFilter
          form="paymentListFilter"
          count={this.state.count}
          onSubmit={(args) => {
            this.search(args)
              .then(() => {
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
              .catch((er) => {
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
        />

        <PaymentsTable
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          EmptyComponent={isRoute && EmptyComponent}
          {...this.props}
        />
      </div>
    );
  }
}
