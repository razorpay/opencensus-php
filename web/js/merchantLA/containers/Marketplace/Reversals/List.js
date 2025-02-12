/* eslint-disable */
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink, Outlet } from 'react-router-dom';
import TestModeBanner from 'merchantLA/containers/TestModeBanner';
import HeaderAction from 'common/ui/HeaderAction';
import { fetchBalanceAction } from 'merchantLA/reducers/credits';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import ShowWhen from 'merchant/components/ShowWhen';
import Amount from 'common/ui/Amount';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';

class ReversalsListContainer extends Component {
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
            <NavLink end to="/reversals">
              Reversals
            </NavLink>
            <ShowWhen additionalCondition={(_) => showRefundToCustomer}>
              <NavLink end to="/reversals/batchreversals">
                Batch
              </NavLink>
            </ShowWhen>
            <ShowWhen additionalCondition={(_) => !isBalanceSource}>
              <NavLink to="/credits">Credits</NavLink>
            </ShowWhen>
            <HeaderAction>
              <span className="reversal-balance-amount">
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
              <Outlet />
            </ErrorBoundary>
          </content>
        </tabbed-container>
      </div>
    );
  }
}

export default connect(
  (state) => {
    return {
      balanceData: state.credits.balanceData,
      user: state.session.user,
    };
  },
  { fetchBalanceAction },
)(ReversalsListContainer);
