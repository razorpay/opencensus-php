import { Component } from 'react';
import { Switch, NavLink, Redirect } from 'react-router-dom';

import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';

import { fetchCommissionBalances } from 'merchant/modules/commission';

import Amount from 'rzp/ui/Amount';
import HeaderAction from 'rzp/ui/HeaderAction';
import { isPresent } from 'rzp/utils/rzp-utils';

import Transactional from './Transactional/List';
import Daily from './Daily/List';

export default class EarningsContainer extends Component {
  state = {
    commissionBalance: null,
  };

  componentDidMount() {
    this.getCommissionBalance();
  }

  getCommissionBalance = () => {
    fetchCommissionBalances().then(res => {
      const data = res.data;
      if (data.items.length > 0) {
        const commissionItem = data.items.find(
          item => item.type === 'commission'
        );
        isPresent(commissionItem) &&
          this.setState({
            commissionBalance: commissionItem.balance,
          });
      }
    });
  };

  render() {
    const { commissionBalance } = this.state;
    return (
      <tabbed-container>
        <header>
          <NavLink exact to="/partners/earnings/daily">
            Daily Earnings
          </NavLink>
          <ShowWhen additionalCondition={user => !user.isPartner('reseller')}>
            <NavLink exact to="/partners/earnings/transactional">
              Transactional Details
            </NavLink>
          </ShowWhen>
        </header>
        <content>
          <ShowWhen
            additionalCondition={user =>
              user.isShowCommissionBalanceEnabled &&
              user.isOrgAllowedFunctionality('current_balance') &&
              (commissionBalance == 0 || commissionBalance)
            }
          >
            <HeaderAction>
              <span class="settlement-balance-amount">
                Commission Balance:{' '}
                <Amount value={commissionBalance} currency="INR" />
              </span>
            </HeaderAction>
          </ShowWhen>
          <Switch>
            <Redirect
              to="/partners/earnings/daily"
              from="/partners/earnings"
              exact
            />
            <ShowWhenRoute
              path="/partners/earnings/transactional"
              component={Transactional}
              additionalCondition={user => !user.isPartner('reseller')}
              exact
            />
            <ShowWhenRoute
              path="/partners/earnings/daily"
              component={Daily}
              exact
            />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
