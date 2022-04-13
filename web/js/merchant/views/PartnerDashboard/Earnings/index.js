import { Component } from 'react';
import { connect } from 'react-redux';
import { Switch, NavLink, Redirect } from 'react-router-dom';

import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';

import { fetchCommissionBalances } from 'merchant/reducers/commission';

import Amount from 'common/ui/Amount';
import HeaderAction from 'common/ui/HeaderAction';
import { isPresent } from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';
import { showNotification } from 'merchant_common/reducers/notifications';

import Transactional from 'merchant/views/PartnerDashboard/Earnings/Transactional/List';
import Daily from 'merchant/views/PartnerDashboard/Earnings/Daily/List';
import CommissionInvoicesList from 'merchant/views/PartnerDashboard/Earnings/Invoices/List';
import CommissionCard from 'merchant/views/PartnerDashboard/Commissions/components/FUX-Cards/CommissionCard';
import PayoutsCard from 'merchant/views/PartnerDashboard/Commissions/components/FUX-Cards/PayoutsCard';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

class EarningsContainer extends Component {
  state = {
    commissionBalance: null,
    isFirstEarningGen: false,
    isFirstPayoutDone: false,
  };

  componentDidMount() {
    this.getCommissionBalance();
    this.getFirstEarningStatus();
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

  getFirstEarningStatus = async () => {
    try {
      const { data } = await merchantFetch({
        url: 'partner/first_user_experience',
        method: 'get',
      });
      const isFirstEarningGen = data?.first_earning_generated || false;
      const isFirstPayoutDone = data?.first_commission_payout || false;
      this.setState({
        isFirstEarningGen,
        isFirstPayoutDone,
      });
    } catch (_) {
      this.props.showNotification({
        type: 'error',
        message: 'An error occurred in connecting to the server',
        hidePrevious: true,
      });
    }
  };

  render() {
    const { commissionBalance, isFirstEarningGen, isFirstPayoutDone } = this.state;
    const { sessionUser } = this.props;
    const not_pure_platform = sessionUser.isPartner() && !sessionUser.isPartner('pure_platform');
    const merchant = sessionUser?.merchants[sessionUser?.current];
    const partnerName = merchant?.name || '';

    return (
      <div className="earnings-page">
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
        <ShowWhen additionalCondition={(user) => user.isPartnershipFUX}>
          <tabbed-container>
            <h2 className="page-heading">{` Welcome to Partner dashboard, ${partnerName}!`}</h2>
            <ShowWhen additionalCondition={() => !isFirstPayoutDone}>
              {isFirstEarningGen ? <PayoutsCard /> : <CommissionCard />}
            </ShowWhen>
          </tabbed-container>
        </ShowWhen>
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
