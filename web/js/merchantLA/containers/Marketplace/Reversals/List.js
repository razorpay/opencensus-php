import { connect } from 'react-redux';
import { Component } from 'react';
import { Route, Switch, NavLink } from 'react-router-dom';
import TestModeBanner from 'merchantLA/containers/TestModeBanner';
import HeaderAction from 'rzp/ui/HeaderAction';
import ReversalsTable from './ReversalsTable';
import Credit from './Credit';
import BatchUploadList from './BatchUpload/List';
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
      merchant = user.merchants[user.current] || {},
      isBalanceSource = merchant.refund_source === 'balance',
      balance = isBalanceSource
        ? balanceData.balance
        : balanceData.refund_credits,
      balanceTitle = isBalanceSource ? 'Current Balance:' : 'Refund Credits:';

    return (
      <div>
        <tabbed-container>
          <header id="marketplace-header">
            <NavLink to="/reversals">Reversals</NavLink>
            <NavLink to="/batchreversals">Batch</NavLink>
            {!isBalanceSource && <NavLink to="/credits">Credits</NavLink>}
            <HeaderAction>
              <span class="reversal-balance-amount">
                {balanceTitle} <Amount value={balance} currency={'INR'} />
              </span>
            </HeaderAction>
          </header>
          <TestModeBanner />
          <content>
            <Switch>
              <Route path="/reversals" component={ReversalsTable} />
              <Route path="/batchreversals" component={BatchUploadList} />
              {!isBalanceSource && <Route path="/credits" component={Credit} />}
            </Switch>
          </content>
        </tabbed-container>
      </div>
    );
  }
}
