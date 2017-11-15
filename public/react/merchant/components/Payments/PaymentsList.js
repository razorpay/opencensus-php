import React, { Component } from 'react';
import PaymentsTable from 'merchant/components/Payments/PaymentsTable';
import ListContainer from 'merchant/containers/ListContainer';
import PaymentsListFilter from 'merchant/components/Payments/PaymentsListFilter';
import { stringifyQueryParamsWithPipe } from 'rzp/utils/rzp-utils';

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

  onSearchAnalytics = params => {
    const { pathname } = this.props.location;
    if (pathname && pathname.indexOf('route') < 0) {
      // Currently not tracking events from Route.
      const label = stringifyQueryParamsWithPipe(params);
      if (label && label.length > 0) {
        window.rzpAnalytics({
          eventCategory: 'Dashboard - Payments',
          eventAction: 'Search - Payments',
          eventLabel: label,
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
    return (
      <div class="content-wrapper">
        <PaymentsListFilter
          form="paymentListFilter"
          count={this.state.count}
          onSubmit={this.search}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
        />

        <PaymentsTable
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
