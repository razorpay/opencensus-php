import { Component } from 'react';
import { connect } from 'react-redux';
import { Switch, NavLink, Redirect } from 'react-router-dom';

import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';

import { fetchCommissionBalances } from 'merchant/reducers/commission';

import Amount from 'common/ui/Amount';
import HeaderAction from 'common/ui/HeaderAction';
import { isPresent } from 'common/utils/rzp-utils';
import { showNotification } from 'merchant_common/reducers/notifications';

import Transactional from 'merchant/views/PartnerDashboard/Earnings/Transactional/List';
import Daily from 'merchant/views/PartnerDashboard/Earnings/Daily/List';
import CommissionInvoicesList from 'merchant/views/PartnerDashboard/Earnings/Invoices/List';

class EarningsContainer extends Component {
  state = {
    commissionBalance: null,
  };

  componentDidMount() {
    this.getCommissionBalance();
  }

  getCommissionBalance = () => {
    fetchCommissionBalances().then((res) => {
      const data = res.data;
      if (data.items.length > 0) {
        const commissionItem = data.items.find((item) => item.type === 'commission');
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
      <div className="earnings-page">
        <tabbed-container>
          <header>
            <NavLink exact to="/partners/earnings/daily">
              Daily Earnings
            </NavLink>
            <ShowWhen additionalCondition={(user) => !user.isPartner('reseller')}>
              <NavLink exact to="/partners/earnings/transactional">
                Transactional Details
              </NavLink>
            </ShowWhen>
            <ShowWhen additionalCondition={(user) => user.isCommissionInvoicesEnabled}>
              <NavLink exact to="/partners/earnings/invoices">
                Invoices
              </NavLink>
            </ShowWhen>
          </header>
          <content>
            <ShowWhen
              additionalCondition={(user) =>
                user.isShowCommissionBalanceEnabled &&
                user.isOrgAllowedFunctionality('current_balance') &&
                (commissionBalance == 0 || commissionBalance)
              }
            >
              <HeaderAction>
                <span class="settlement-balance-amount">
                  Commission Balance: <Amount value={commissionBalance} currency="INR" />
                </span>
              </HeaderAction>
            </ShowWhen>
            <Switch>
              <Redirect to="/partners/earnings/daily" from="/partners/earnings" exact />
              <ShowWhenRoute
                path="/partners/earnings/transactional"
                component={Transactional}
                additionalCondition={(user) => !user.isPartner('reseller')}
                exact
              />
              <ShowWhenRoute path="/partners/earnings/daily" component={Daily} exact />
              <ShowWhenRoute
                path="/partners/earnings/invoices"
                component={CommissionInvoicesList}
                additionalCondition={(user) => user.isCommissionInvoicesEnabled}
                exact
              />
            </Switch>
          </content>
        </tabbed-container>
      </div>
    );
  }
}

export default connect(
  (state) => ({
    sessionUser: state.session.user,
  }),
  { showNotification },
)(EarningsContainer);
