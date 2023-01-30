import { Fragment, Component } from 'react';
import { connect } from 'react-redux';
import { NavLink, Route, Switch } from 'react-router-dom';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { merchantFetch } from 'merchant/utils/ajax';
import RTracking from 'react-tracking';

import ShowWhen from 'merchant/components/ShowWhen';

import PartnerOnbr from 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr';
import AddMerchant from './AddMerchant';
import ReferralBox from './ReferralBox';
import Announcement from 'merchant/components/Announcements/Instant';
import { XSubMerchantList, PrimarySubMerchantList, CapitalSubMerchantList } from './AccountsList';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { trackAddNewMerchantEvents } from 'merchant/views/PartnerDashboard/ga';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

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
    const { user } = this.props;
    this.trackUserEvent('partnerships.dashboard.open', {
      fux: user?.isPartnershipFUX,
    });

    // triggered because Affiliate Razorpay Accounts is default view
    this.trackUserEvent('partnerships.dashboard.affiliate_account.payments');
  }
  getProductType = () => {
    const basePath = this.props?.match?.path;
    switch (this.props?.location?.pathname) {
      case `${basePath}`: {
        return PRODUCT_TYPE.PG;
      }
      case `${basePath}/x`: {
        return PRODUCT_TYPE.X;
      }
      case `${basePath}/capital`: {
        return PRODUCT_TYPE.CAPITAL;
      }
      default: {
        return PRODUCT_TYPE.X;
      }
    }
  };

  handleAddMerchant = () => {
    this.trackUserEvent('partnerships.submerchant.add', {
      source: 'navbar',
    });
    analyticsTrack({
      objectName: 'Add New Merchant',
      actionName: 'clicked',
      screen: 'affiliate accounts',
      properties: {
        location: 'navbar',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toCleverTap: true,
    });

    trackAddNewMerchantEvents('Click - Navbar');
    const { closeModal } = this.props;
    const { referralData } = this.state;
    const addMerchantType = this.getProductType();
    this.props.openModal({
      size: 'med-large',
      component: (
        <AddMerchant
          closeModal={closeModal}
          referralData={referralData}
          addType={addMerchantType}
        />
      ),
    });
  };

  handleShareReferralLink = () => {
    this.trackUserEvent('partnerships.submerchant.referral');
    analyticsTrack({
      objectName: 'Share Referral Link',
      actionName: 'clicked',
      screen: 'affiliate accounts',
      properties: {
        location: 'submerchant list',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toCleverTap: true,
    });
    const product = this.getProductType();
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
          product={product}
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
    const { isPartnershipForCapitalEnabled } = user;
    const not_pure_platform = user.isPartner() && !user.isPartner('pure_platform');
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
            <div>
              <NavLink
                exact
                to="/partners/submerchants"
                onClick={(e) => this.sendAnalytics(e, 'navlink-Payments')}
              >
                Payments Affiliate Accounts
              </NavLink>
              <ShowWhen
                additionalCondition={(currentUser) =>
                  not_pure_platform && currentUser.isPartnershipForXEnabled
                }
              >
                <NavLink
                  exact
                  to="/partners/submerchants/x"
                  onClick={(e) => this.sendAnalytics(e, 'navlink-X')}
                >
                  RazorpayX Affiliate Accounts
                </NavLink>
              </ShowWhen>
              <ShowWhen additionalCondition={() => isPartnershipForCapitalEnabled}>
                <NavLink exact to="/partners/submerchants/capital">
                  Corporate Credit Card Affiliate Accounts
                </NavLink>
              </ShowWhen>
            </div>
            {/* Moved Share Referral and Add New Accounts from content to header, 
            removed HeaderAction and added some CSS to fix screen responsive issue */}
            <div className="partner-dashboard-header-action">
              <ShowWhen
                additionalCondition={(currentUser) =>
                  currentUser.isPartner() && currentUser.isPartner('reseller', 'aggregator')
                }
              >
                <button
                  className="btn btn-link"
                  onClick={this.handleShareReferralLink}
                  type="button"
                >
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
                  className="btn btn-primary pull-right m-l"
                  onClick={this.handleAddMerchant}
                  type="button"
                >
                  <i className="i i-plus" /> Add New Accounts
                </button>
              </ShowWhen>
            </div>
          </header>
          <content>
            <div className="sub-merchants-list">
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
                {isPartnershipForCapitalEnabled ? (
                  <Route
                    path={`${this.props.match.path}/capital`}
                    render={(props) => (
                      <CapitalSubMerchantList
                        {...props}
                        product={PRODUCT_TYPE.CAPITAL}
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
