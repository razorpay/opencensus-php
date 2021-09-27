import { Fragment, Component } from 'react';
import { connect } from 'react-redux';
import { NavLink, Route, Switch } from 'react-router-dom';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { merchantFetch } from 'merchant/utils/ajax';
import RTracking from 'react-tracking';

import HeaderAction from 'common/ui/HeaderAction';
import ShowWhen from 'merchant/components/ShowWhen';

import PartnerOnbr from 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr';
import AddMerchant from './AddMerchant';
import ReferralBox from './ReferralBox';
import Announcement from 'merchant/components/Announcements/Instant';
import { XSubMerchantList, PrimarySubMerchantList } from './AccountsList';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

@connect(
  (state) => ({
    user: state.session.user,
    mode: state.session.mode,
    isMobileResolution: state.app.isMobileResolution,
    ...state.submerchants,
  }),
  {
    openModal,
    closeModal,
    showNotification,
  },
)
@RTracking(() => window.rzpQ.component('SubMerchantsList'))
export default class SubMerchantsList extends Component {
  state = {};

  componentDidMount() {
    this.trackUserEvent('partnerships.dashboard.open');

    // triggered because Affiliate Razorpay Accounts is default view
    this.trackUserEvent('partnerships.dashboard.affiliate_account.payments');
  }
  handleAddMerchant = () => {
    this.trackUserEvent('partnerships.submerchant.add', {
      source: 'navbar',
    });
    this.props.openModal({
      size: 'med-large',
      component: <AddMerchant closeModal={this.props.closeModal} />,
    });
  };

  handleShareReferralLink = () => {
    this.trackUserEvent('partnerships.submerchant.referral');

    this.props.openModal({
      size: 'med-large',
      component: (
        <ReferralBox
          user={this.props.user}
          closeModal={this.props.closeModal}
          referralData={this.state.referralData}
          tracking={this.props.tracking}
          partnerID={this.props.user.id}
          partnershipForXEnabled={this.props.user.isPartnershipForXEnabled}
        />
      ),
    });
  };

  constructor(props) {
    merchantFetch({
      url: 'merchant/referral',
      mode: 'live',
      method: 'post',
      data: {},
    })
      .then(({ data }) => {
        this.setState({
          referralData: data.referrals,
        });
      })
      .catch(() => {});
    super(props);
  }

  trackUserEvent = (eventName, properties = {}) => {
    const { user, tracking } = this.props;
    tracking.trackEvent(
      window.rzpQ.onbr().interaction(eventName, {
        partnerID: user.id,
        ...properties,
      }),
    );
  };

  sendAnalytics = (e, type) => {
    if (type === 'navlink-Payments') {
      this.trackUserEvent('partnerships.dashboard.affiliate_account.payments');
    }
    if (type === 'navlink-X') {
      this.trackUserEvent('partnerships.dashboard.affiliate_account.x');
    }
  };

  render() {
    const { user } = this.props;

    if (user.isPartnerIntent()) {
      this.props.openModal({
        size: 'xlarge',
        disableClose: true,
        component: <PartnerOnbr disableClose={true} />,
        className: this.props.isMobileResolution
          ? 'partner-onboarding-popup mobile-app-popup'
          : 'partner-onboarding-popup',
      });
    }
    return (
      <Fragment>
        <Announcement user={this.props.user} mode={this.props.mode} />
        <tabbed-container>
          <header className="partner-dashboard-header">
            <NavLink
              exact
              to="/partners/submerchants"
              onClick={(e) => this.sendAnalytics(e, 'navlink-Payments')}
            >
              Affiliate Razorpay Accounts
            </NavLink>
            <ShowWhen additionalCondition={(currentUser) => currentUser.isPartnershipForXEnabled}>
              <NavLink
                exact
                to="/partners/submerchants/x"
                onClick={(e) => this.sendAnalytics(e, 'navlink-X')}
              >
                Affiliate RazorpayX Accounts
              </NavLink>
            </ShowWhen>
          </header>
          <content>
            <div class="sub-merchants-list">
              <div>
                <HeaderAction>
                  <>
                    <ShowWhen
                      additionalCondition={(currentUser) =>
                        currentUser.isPartner() && currentUser.isPartner('reseller')
                      }
                    >
                      <button class="btn btn-link" onClick={this.handleShareReferralLink}>
                        <span> Share Referral Link</span>
                      </button>
                    </ShowWhen>
                    <ShowWhen
                      myRole="owner manager admin"
                      additionalCondition={(currentUser) =>
                        currentUser.isPartner() && !currentUser.isPartner('pure_platform')
                      }
                    >
                      <button
                        class="btn btn-primary pull-right m-l"
                        onClick={this.handleAddMerchant}
                      >
                        <i class="i i-plus" />
                        Add New Accounts
                      </button>
                    </ShowWhen>
                  </>
                </HeaderAction>
              </div>
              <Switch>
                {user.isPartnershipForXEnabled ? (
                  <Route
                    path={`${this.props.match.path}/x`}
                    render={(props) => (
                      <XSubMerchantList
                        {...props}
                        product={PRODUCT_TYPE.X}
                        referralData={this.state.referralData}
                      />
                    )}
                    exact
                  />
                ) : (
                  ''
                )}
                <Route
                  path={`${this.props.match.path}/`}
                  render={(props) => (
                    <PrimarySubMerchantList
                      {...props}
                      product={PRODUCT_TYPE.PG}
                      referralData={this.state.referralData}
                    />
                  )}
                />
              </Switch>
            </div>
          </content>
        </tabbed-container>
      </Fragment>
    );
  }
}
