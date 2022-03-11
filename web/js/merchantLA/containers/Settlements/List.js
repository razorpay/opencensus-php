import React from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import Pager from 'common/ui/Pager';
import Alert from 'common/ui/Forms/Alert';
import ListContainer from 'merchant/containers/ListContainer';
import SettlementsList from 'merchantLA/components/Settlements/List';
import SettlementsListFilter from 'merchantLA/components/Settlements/ListFilter';
import SettlementBreakupModal from './BreakupModal';
import HeaderAction from 'common/ui/HeaderAction';
import { fetchSettlements as fetchAll } from 'merchantLA/reducers/collection';
import * as ModalActions from 'merchant_common/reducers/modals';
import TestModeBanner from 'merchantLA/containers/TestModeBanner';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';
import { fetchBalanceAction } from 'merchantLA/reducers/credits';
import Amount from 'common/ui/Amount';
import SettlementGuideText from 'merchant_common/components/SettlementGuideText';

@connect(
  (state) => ({
    ...state.settlements,
    balanceData: state.credits.balanceData,
    user: state.session.user,
    merchant: state.merchant,
  }),
  {
    fetchAll,
    fetchBalanceAction,
    ...ModalActions,
  },
)
export default class SettlementsListContainer extends ListContainer {
  componentDidMount() {
    if (this.props.user.current && !this.props.balanceData.data.balance) {
      this.props.fetchBalanceAction();
    }

    window.rzpAnalytics({
      eventCategory: 'LA Dashboard - Settlements',
      eventAction: 'Go To - Settlements',
    });
  }

  onSearchAnalytics = (params) => {
    const label = getKeysSeparatedByPipe(params);
    if (label && label.length > 0) {
      window.rzpAnalytics({
        eventCategory: 'LA Dashboard - Settlements',
        eventAction: 'Search - Settlements',
        eventLabel: label,
      });
    }
  };

  onClearAnalytics = () => {
    window.rzpAnalytics({
      eventCategory: 'LA Dashboard - Settlements',
      eventAction: 'Clear Search Params - Settlements',
    });
  };

  settlementBreakupOnMount = (id) => {
    window.rzpAnalytics({
      eventCategory: 'LA Dashboard - Settlements',
      eventAction: 'Show - Settlement Breakup',
      eventLabel: `settlement_id=${id}`,
    });
  };

  settlementBreakupOnUnmount = (id) => {
    window.rzpAnalytics({
      eventCategory: 'LA Dashboard - Settlements',
      eventAction: 'Hide - Settlement Breakup',
      eventLabel: `settlement_id=${id}`,
    });
  };

  showBreakup = (settlement) => {
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
    const { loading, items, error, balanceData } = this.props;

    return (
      <tabbed-container>
        <header>
          <NavLink to="/settlements">Settlements</NavLink>
          <HeaderAction>
            <div>
              <a
                class="btn btn-link"
                href="http://razorpay.com/settlement"
                target="_blank"
                rel="noreferrer noopener"
              >
                How settlements work?&nbsp;
                <span class="icon i-external-link" />
              </a>
              {this.props.balanceData.loading ? (
                <PlaceholderLoader style={{ width: 150 }} />
              ) : (
                <span class="settlement-balance-amount">
                  Current Balance: <Amount value={balanceData.data.balance} currency={'INR'} />
                </span>
              )}
            </div>
          </HeaderAction>
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

            <SettlementGuideText />
          </div>
        </content>
      </tabbed-container>
    );
  }
}
