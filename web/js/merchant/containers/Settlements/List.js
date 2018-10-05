import React, { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import ListContainer from 'merchant/containers/ListContainer';
import SettlementsList from 'merchant/components/Settlements/List';
import SettlementsListFilter from 'merchant/components/Settlements/ListFilter';
import SettlementBreakupModal from './BreakupModal';
import HeaderAction from 'rzp/ui/HeaderAction';
import { fetchSettlements as fetchAll } from 'merchant/modules/collection';
import * as ModalActions from 'rzp/modules/modals';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import { getKeysSeparatedByPipe } from 'rzp/utils/rzp-utils';
import { EarlySettlementAnnouncement } from 'merchant/components/Announcements';
import RequestEarlyAccessForm from 'merchant/components/Announcements/EarlySettlementsModal';
import {
  trackEarlySettlementRequests,
  trackHowSettlementsWorkClicks,
  trackOndemand,
} from './ga';
import { fetchCurrentBalance } from 'merchant/modules/home';
import OndemandModal from './OndemandModal';
import Amount from 'rzp/ui/Amount';
import Button from 'component/Button';

@connect(
  state => ({
    user: state.session.user,
    ...state.home,
    ...state.settlements,
  }),
  {
    fetchAll,
    ...ModalActions,
    fetchCurrentBalance,
  }
)
export default class SettlementsListContainer extends ListContainer {
  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settlements',
      eventAction: 'Go To - Settlements',
    });
    this.props.fetchCurrentBalance();
  }

  onSearchAnalytics = params => {
    const label = getKeysSeparatedByPipe(params);
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

  showRequestEarySettlementForm = e => {
    trackEarlySettlementRequests();
    this.props.openModal({
      component: <RequestEarlyAccessForm />,
      size: 'large',
    });
  };

  showOndemandSettlementForm = e => {
    trackOndemand.trackSettleNow('Settlements');
    let balance = this.props.current_balance.data.balance;
    this.props.openModal({
      component: (
        <OndemandModal currentBalance={balance} fromWhere="Settlements" />
      ),
      size: 'small',
    });
  };

  render() {
    let { loading, items, error, current_balance } = this.props;
    let balance = current_balance.data.balance || 0;

    return (
      <React.Fragment>
        <EarlySettlementAnnouncement from="Settlements" />

        <tabbed-container>
          <header>
            <NavLink to="/settlements">Settlements</NavLink>
          </header>

          <TestModeBanner />

          <content>
            <div class="content-wrapper">
              <HeaderAction>
                <React.Fragment>
                  {this.props.user.showEarlySettlementAnnouncement ? (
                    <a
                      class="btn btn-link req-es-btn"
                      onClick={this.showRequestEarySettlementForm}
                    >
                      Request Early Settlements{' '}
                      <i class="fa fa-circle interpunct" />
                    </a>
                  ) : (
                    ''
                  )}

                  <a
                    class="btn btn-link settlement-doc-btn"
                    href="http://razorpay.com/settlement"
                    target="_blank"
                    onClick={trackHowSettlementsWorkClicks}
                  >
                    How settlements work?&nbsp;<span class="icon i-external-link" />
                  </a>
                  <span class="settlement-balance-amount">
                    Current Balance: <Amount value={balance} />
                  </span>

                  {this.props.user.isOndemandSettlementEnabled && (
                    <Button.Secondary
                      class="settle-btn"
                      onClick={this.showOndemandSettlementForm}
                      disabled={current_balance.loading || balance < 100}
                    >
                      Settle Now
                    </Button.Secondary>
                  )}
                </React.Fragment>
              </HeaderAction>
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
                  <div>
                    The amount that gets settled to your bank account will show
                    up here.
                  </div>
                  <div>
                    <a
                      class="btn-link"
                      target="_blank"
                      href="http://razorpay.com/settlement"
                    >
                      See our Settlements Guide
                    </a>{' '}
                    to understand how it works.
                  </div>
                </div>
              </div>
            </div>
          </content>
        </tabbed-container>
      </React.Fragment>
    );
  }
}
