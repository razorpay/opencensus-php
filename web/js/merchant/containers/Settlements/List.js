import React, { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import ListContainer from 'merchant/containers/ListContainer';
import SettlementsList from 'merchant/components/Settlements/List';
import SettlementsListFilter from 'merchant/components/Settlements/ListFilter';
import SettlementBreakupModal from './BreakupModal';
import { fetchSettlements as fetchAll } from 'rzp/modules/collection';
import * as ModalActions from 'rzp/modules/modals';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import { stringifyQueryParamsWithPipe } from 'rzp/utils/rzp-utils';

@connect(state => state.settlements, {
  fetchAll,
  ...ModalActions,
})
export default class SettlementsListContainer extends ListContainer {
  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settlements',
      eventAction: 'Go To - Settlements',
    });
  }

  onSearchAnalytics = params => {
    const label = stringifyQueryParamsWithPipe(params);
    if (label && label.length > 0) {
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Settlements',
        eventAction: 'Search - Settlements',
        eventLabel: label,
      });
    }
  };

  onClearAnalytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settlements',
      eventAction: 'Clear Search Params - Settlements',
    });
  };

  settlementBreakupOnMount = id => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settlements',
      eventAction: 'Show - Settlement Breakup',
      eventLabel: `settlement_id=${id}`,
    });
  };

  settlementBreakupOnUnmount = id => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settlements',
      eventAction: 'Hide - Settlement Breakup',
      eventLabel: `settlement_id=${id}`,
    });
  };

  showBreakup = settlement => {
    this.props.openModal({
      component: (
        <SettlementBreakupModal
          settlementId={settlement.id}
          onMount={this.settlementBreakupOnMount}
          onUnmount={this.settlementBreakupOnUnmount}
        />
      ),
    });
  };

  render() {
    let { loading, items, error } = this.props;

    return (
      <tabbed-container>
        <header>
          <NavLink to="/settlements">Settlements</NavLink>
        </header>

        <TestModeBanner />

        <content>
          <div class="content-wrapper">
            <SettlementsListFilter
              form="settlementsListFilter"
              count={this.state.count}
              onSubmit={this.search}
              onSearchAnalytics={this.onSearchAnalytics}
              onClearAnalytics={this.onClearAnalytics}
            />

            {error && <Alert type="error" message={error} />}

            <SettlementsList
              settlements={items}
              isLoading={loading}
              showBreakup={this.showBreakup}
            />

            <Pager
              count={this.state.count}
              skip={this.state.skip}
              length={items.length}
              onClick={this.paginate}
            />

            <div class="row">
              <div class="col-md-6 col-md-offset-3 col-sm-12 text-center">
                <p>
                  A settlement is an aggregate of payments and refunds, and as
                  such the fees in a settlement is not reflective of the
                  pricing. We only charge fees on a captured payment.
                </p>
              </div>
            </div>
          </div>
        </content>
      </tabbed-container>
    );
  }
}
