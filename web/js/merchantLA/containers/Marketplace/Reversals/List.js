import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import TestModeBanner from 'merchantLA/containers/TestModeBanner';
import HeaderAction from 'common/ui/HeaderAction';
import ReversalsTable from './ReversalsTable';
import Credit from './Credit';
import BatchUploadList from './BatchUpload/List';
import { fetchBalanceAction } from 'merchantLA/reducers/credits';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';
import Amount from 'common/ui/Amount';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';

@connect(
  (state) => {
    return {
      balanceData: state.credits.balanceData,
      user: state.session.user,
    };
  },
  { fetchBalanceAction },
)
export default class ReversalsListContainer extends Component {
  componentDidMount() {
    if (this.props.user.current && !this.props.balanceData.data.balance) {
      this.props.fetchBalanceAction();
    }
  }

  render() {
    const {
      balanceData: { loading, data: balanceData },
      user,
    } = this.props;
    const merchant = user.merchants[user.current] || {};
    const isBalanceSource = merchant.refund_source === 'balance';
    const balance = isBalanceSource ? balanceData.balance : balanceData.refund_credits;
    const balanceTitle = isBalanceSource ? 'Current Balance:' : 'Refund Credits:';
    const showRefundToCustomer = user.isAllowedLARefunds;

    return (
      <div>
        <tabbed-container>
          <header id="marketplace-header">
            <NavLink exact to="/reversals">
              Reversals
            </NavLink>
            <ShowWhen additionalCondition={(_) => showRefundToCustomer}>
              <NavLink exact to="/reversals/batchreversals">
                Batch
              </NavLink>
            </ShowWhen>
            <ShowWhen additionalCondition={(_) => !isBalanceSource}>
              <NavLink to="/credits">Credits</NavLink>
            </ShowWhen>
            <HeaderAction>
              <span class="reversal-balance-amount">
                {loading ? (
                  <PlaceholderLoader />
                ) : (
                  <React.Fragment>
                    {balanceTitle} <Amount value={balance} currency={balanceData.currency} />
                  </React.Fragment>
                )}
              </span>
            </HeaderAction>
          </header>
          <TestModeBanner />
          <content>
            <ErrorBoundary resetOnProps>
              <Switch>
                <Route exact path="/reversals" component={ReversalsTable} />
                <ShowWhenRoute
                  exact
                  path="/reversals/batchreversals"
                  component={BatchUploadList}
                  additionalCondition={(_) => showRefundToCustomer}
                />
                <ShowWhenRoute
                  path="/credits"
                  component={Credit}
                  additionalCondition={(_) => !isBalanceSource}
                />
              </Switch>
            </ErrorBoundary>
          </content>
        </tabbed-container>
      </div>
    );
  }
}
