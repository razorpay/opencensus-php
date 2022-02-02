import { Component } from 'react';
import { connect } from 'react-redux';
import { Switch, NavLink, Redirect } from 'react-router-dom';

import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';

import { fetchCommissionBalances } from 'merchant/reducers/commission';

import Amount from 'common/ui/Amount';
import HeaderAction from 'common/ui/HeaderAction';
import { isPresent } from 'common/utils/rzp-utils';

import Transactional from './Transactional/List';
import Daily from './Daily/List';
import CommissionInvoicesList from './Invoices/List';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

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
    const { sessionUser } = this.props;
    const not_pure_platform = sessionUser.isPartner() && !sessionUser.isPartner('pure_platform');
    return (
      <>
        {not_pure_platform && sessionUser.isPartnershipForXEnabled ? (
          <AnnouncementBanner
            title=""
            theme="primary"
            card_id="current-account-earning-partnership-banner"
          >
            <b>Note: </b> Earnings for RazorpayX referrals won&apos;t be visible here and will be
            processed manually by our team in the first week of each month
          </AnnouncementBanner>
        ) : null}
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
      </>
    );
  }
}

export default connect(
  (state) => ({
    sessionUser: state.session.user,
  }),
  {},
)(EarningsContainer);
