import { connect } from 'react-redux';
import { Component } from 'react';
import { Route, NavLink } from 'react-router-dom';
import TestModeBanner from 'merchantLA/containers/TestModeBanner';
import HeaderAction from 'rzp/ui/HeaderAction';
import ReversalsTable from './ReversalsTable';
import Credit from './Credit';
import { fetchCreditBalance } from 'merchantLA/modules/credits';
import Amount from 'rzp/ui/Amount';
@connect(
  state => {
    return {
      credits: state.credits,
      user: state.session.user,
      merchant: state.merchant,
    };
  },
  { fetchCreditBalance }
)
export default class ReversalsListContainer extends Component {
  componentDidMount() {
    if (this.props.user.current && !this.props.credits.balanceData.balance) {
      this.props.fetchCreditBalance();
    }
  }

  render() {
    const { credits: { loading, balanceData }, user } = this.props,
      merchant = user.merchants[user.current],
      balance = !loading
        ? merchant.refund_source
          ? balanceData.refund_credits
          : balanceData.balance
        : 0,
      balanceTitle = merchant.refund_source
        ? 'Refund Credits:'
        : 'Current Balance:';

    return (
      <div>
        <tabbed-container>
          <header id="marketplace-header">
            <NavLink to="/reversals">Reversals</NavLink>
            <NavLink to="/credits">Credits</NavLink>
            <HeaderAction>
              {!loading && (
                <span class="reversal-balance-amount">
                  {balanceTitle} <Amount value={balance} currency={'INR'} />
                </span>
              )}
            </HeaderAction>
          </header>
          <TestModeBanner />
          <content>
            <Route path="/reversals" component={ReversalsTable} />
            <Route path="/credits" component={Credit} />
          </content>
        </tabbed-container>
      </div>
    );
  }
}
