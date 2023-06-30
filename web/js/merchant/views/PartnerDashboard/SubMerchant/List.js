import { Component, Suspense } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, withRouter } from 'react-router-dom';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { merchantFetch } from 'merchant/utils/ajax';
import RTracking from 'react-tracking';

import ShowWhen from 'merchant/components/ShowWhen';
import lazy from 'merchant/routes/LazyLoader';

import PartnerOnbr from 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr';
import AddMerchant from './AddMerchant';
import ReferralBox from './ReferralBox';
import Announcement from 'merchant/components/Announcements/Instant';
import { XSubMerchantList, PrimarySubMerchantList, CapitalSubMerchantList } from './AccountsList';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { trackAddNewMerchantEvents } from 'merchant/views/PartnerDashboard/ga';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import ProductWrapper from 'common/ui/ProductWrapper';
import { Box, Spinner } from '@razorpay/blade/components';
import PGInvitesNavLinks from './components/PGInviteNavLinks';

const AllInvitesTable = lazy(() =>
  import(/* webpackChunkName: "AllInvitesTable" */ './components/AllInvitesTable'),
);

@withRouter
@connect(
  (state) => ({
    user: state.session.user,
    mode: state.session.mode,
    org: state.session.org,
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
  componentDidMount() {
    const { user, location } = this.props;
    if (location?.state?.addType) {
      if (location.state.addType === PRODUCT_TYPE.CAPITAL) {
        this.handleAddMerchant();
        const { addType: _deleted, ...state } = location.state;
        this.props.history.replace({ state });
      }
    }
    this.trackUserEvent('partnerships.dashboard.open', {
      fux: user?.isPartnershipFUX,
    });

    // triggered because Affiliate Razorpay Accounts is default view
    this.trackUserEvent('partnerships.dashboard.affiliate_account.payments');
  }

  componentDidUpdate(prevProps) {
    if (prevProps.location.pathname !== this.props.location.pathname) {
      const product = this.getProductType();
      if (product === PRODUCT_TYPE.PG) {
        this.sendAnalytics('navlink-Payments');
      }
      if (product === PRODUCT_TYPE.X) {
        this.sendAnalytics('navlink-X');
      }
      if (product === PRODUCT_TYPE.CAPITAL) {
        this.sendAnalytics('navlink-Capital');
      }
    }
  }

  getProductType = () => {
    const basePath = this.props?.match?.path;
    switch (this.props?.location?.pathname) {
      case `${basePath}`:
      case `${basePath}/all`:
        return PRODUCT_TYPE.PG;
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
    const { closeModal, openModal, org } = this.props;
    const { referralData } = this.state;
    const addMerchantType = this.getProductType();
    openModal({
      size: 'med-large',
      component: (
        <AddMerchant
          closeModal={closeModal}
          referralData={referralData}
          addType={addMerchantType}
          org={org}
        />
      ),
    });
  };

  handleShareReferralLink = () => {
    const { user } = this.props;
    const product = this.getProductType();
    if (product === PRODUCT_TYPE.CAPITAL) {
      analyticsTrack({
        screen: 'Affiliate accounts',
        objectName: 'partnerships.capital.affiliate accounts',
        actionName: 'share referral link clicked',
        properties: {
          partner_id: user.id,
        },
        toLumberjack: true,
      });
    } else {
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
    }
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

    this.showX =
      props.user.isPartner() &&
      !props.user.isPartner('pure_platform') &&
      props.user.isPartnershipForXEnabled &&
      !props.user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.RazorpayXAffiliateAccount);

    this.state = {
      tabsData: [
        { url: '/partners/submerchants', title: 'Payments', isActive: this.isPaymentsTabActive },
        {
          url: '/partners/submerchants/capital',
          title: 'Line Of Credit',
          hidden: !props.user.isPartnershipForCapitalEnabled,
        },
        {
          url: '/partners/submerchants/x',
          title: 'RazorpayX',
          hidden: !this.showX,
        },
      ],
    };
  }

  isPaymentsTabActive = () => {
    const product = this.getProductType();
    return product === PRODUCT_TYPE.PG;
  };

  trackUserEvent = (eventName, properties = {}) => {
    const { user, tracking } = this.props;
    tracking.trackEvent(
      window.rzpQ.onbr().interaction(eventName, {
        partnerID: user.id,
        ...properties,
      }),
    );
  };

  sendAnalytics = (type) => {
    const { user } = this.props;
    if (type === 'navlink-Payments') {
      this.trackUserEvent('partnerships.dashboard.affiliate_account.payments');
    }
    if (type === 'navlink-X') {
      this.trackUserEvent('partnerships.dashboard.affiliate_account.x');
    }
    if (type === 'navlink-Capital') {
      analyticsTrack({
        screen: 'Affiliate accounts',
        objectName: 'partnerships.capital',
        actionName: 'affiliate accounts.tab clicked',
        properties: {
          partner_id: user.id,
        },
        toLumberjack: true,
      });
    }
  };

  render() {
    const { user } = this.props;
    const { isPartnershipForCapitalEnabled } = user;
    const { tabsData } = this.state;
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
      <>
        <Announcement user={this.props.user} mode={this.props.mode} />
        <ProductWrapper
          tabsData={tabsData}
          extra={
            <>
              <ShowWhen
                additionalCondition={(currentUser) =>
                  currentUser.isPartner() &&
                  currentUser.isPartner('reseller', 'aggregator') &&
                  !currentUser.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.ReferalLinks)
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
                <button className="btn btn-primary" onClick={this.handleAddMerchant} type="button">
                  <i className="i i-plus" /> Add New Accounts
                </button>
              </ShowWhen>
            </>
          }
        >
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
                  exact
                />

                {this.props.user.isPartnershipsInviteFlowEnabled && (
                  <Route
                    path={`${this.props.match.path}/all`}
                    render={(props) => (
                      <>
                        <PGInvitesNavLinks prefix={this.props.match.path} />
                        <div className="content-wrapper">
                          <Suspense
                            fallback={
                              <Box
                                minHeight="300px"
                                display="flex"
                                justifyContent="center"
                                alignItems="center"
                              >
                                <Spinner accessibilityLabel="spinner" size="xlarge" />
                              </Box>
                            }
                          >
                            <AllInvitesTable {...props} />
                          </Suspense>
                        </div>
                      </>
                    )}
                    exact
                  />
                )}
              </Switch>
            </div>
          </content>
        </ProductWrapper>
      </>
    );
  }
}
