import { Component, Suspense } from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { Route, Routes } from 'react-router-dom';
import RTracking from 'react-tracking';

import { withRouter } from 'common/deprecated/withRouter';
import { withI18Service } from 'common/i18';
import ProductWrapper from 'common/ui/ProductWrapper';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import Announcement from 'merchant/components/Announcements/Instant';
import ShowWhen, { RouteGuard } from 'merchant/components/ShowWhen';
import lazy from 'merchant/routes/LazyLoader';
import { merchantFetch } from 'merchant/utils/ajax';
import PartnerOnbr from 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr';
import InviteMerchantModal from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal';
import ShareReferralLink from 'merchant/views/PartnerDashboard/SubMerchant/components/ShareReferralLink';
import {
  ADD_NEW_MERCHANT_ELIGIBLE_ROLES,
  PRODUCT_TYPE,
} from 'merchant/views/PartnerDashboard/constants';
import { trackAddNewMerchantEvents } from 'merchant/views/PartnerDashboard/ga';
import withPartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hocs/withPartnerDashboardExperiments';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { XSubMerchantList, PrimarySubMerchantList, CapitalSubMerchantList } from './AccountsList';
import AddMerchant from './AddMerchant';
import { trackAcceptedInvitesClick, trackAllInvitesClick } from './analytics';
import { INVITE_MERCHANT_STEPS } from './components/InviteMerchantModal/constants';
import InviteNavLinks from './components/InviteNavLinks';

const AllInvitesTable = lazy(() =>
  import(/* webpackChunkName: "AllInvitesTable" */ './components/AllInvitesTable'),
);

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
class SubMerchantsList extends Component {
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
    const basePath = '/partners/submerchants';
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
    analyticsTrack({
      objectName: 'Add New Merchant',
      actionName: 'Clicked',
      screen: 'affiliate accounts',
      properties: {
        location: 'navbar',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toCleverTap: true,
    });

    trackAddNewMerchantEvents('Click - Navbar');
    const {
      closeModal,
      openModal,
      org,
      experiments,
      i18: { isConfigTagEnabled },
    } = this.props;
    const { referralData } = this.state;
    const product = this.getProductType();
    const { isPartnershipsInviteFlowEnabled, isPlatformPartnerInviteFlowEnabled } = experiments;

    const isPlatformPartnerWithPGInviteFlow =
      isPlatformPartnerInviteFlowEnabled && product === PRODUCT_TYPE.PG;
    if (
      isPlatformPartnerWithPGInviteFlow ||
      (product === PRODUCT_TYPE.PG && isPartnershipsInviteFlowEnabled)
    ) {
      this.setState({ isInviteMerchantModalOpen: true });
    } else {
      openModal({
        size: 'med-large',
        component: (
          <AddMerchant
            closeModal={closeModal}
            referralData={referralData}
            addType={product}
            org={org}
            isConfigTagEnabled={isConfigTagEnabled}
          />
        ),
      });
    }
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
      isNew: true,
      component: (
        <ShareReferralLink referralData={this.state.referralData} initialProductType={product} />
      ),
    });
  };

  constructor(props) {
    if (!props.user.isPartner('pure_platform')) {
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
    }
    super(props);

    this.showX =
      props.user.isPartner() &&
      !props.user.isPartner('pure_platform') &&
      !props.i18.isConfigTagEnabled('partnership.razorpay_x_affiliate_account');

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
    const {
      user,
      experiments,
      i18: { isConfigTagEnabled },
    } = this.props;
    const { isPartnershipsInviteFlowEnabled, isPlatformPartnerInviteFlowEnabled } = experiments;
    const product = this.getProductType();
    const isPlatformPartnerWithPGInviteFlow =
      isPlatformPartnerInviteFlowEnabled && product === PRODUCT_TYPE.PG;

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
                additionalCondition={
                  (currentUser) =>
                    currentUser.isPartner() &&
                    currentUser.isPartner('reseller', 'aggregator') &&
                    !isConfigTagEnabled('partnership.referral_links')
                  // TODO v2: enable Share Referral Link for isPlatformPartnerWithPGInviteFlow
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
                myRole={ADD_NEW_MERCHANT_ELIGIBLE_ROLES}
                additionalCondition={(currentUser) =>
                  currentUser.isPartner() &&
                  (isPlatformPartnerWithPGInviteFlow || !currentUser.isPartner('pure_platform'))
                }
              >
                <button className="btn btn-primary" onClick={this.handleAddMerchant} type="button">
                  <i className="i i-plus" /> Add New Clients
                </button>
              </ShowWhen>
            </>
          }
        >
          <content>
            <div className="sub-merchants-list">
              <Routes>
                <Route
                  path="x"
                  element={
                    <RouteGuard>
                      <XSubMerchantList
                        product={PRODUCT_TYPE.X}
                        referralData={this.state.referralData}
                      />
                    </RouteGuard>
                  }
                />

                {isPartnershipForCapitalEnabled ? (
                  <Route
                    path="capital"
                    element={
                      <RouteGuard>
                        <CapitalSubMerchantList
                          product={PRODUCT_TYPE.CAPITAL}
                          referralData={this.state.referralData}
                        />
                      </RouteGuard>
                    }
                  />
                ) : (
                  ''
                )}
                <Route
                  path="*"
                  element={
                    <RouteGuard>
                      <PrimarySubMerchantList
                        product={PRODUCT_TYPE.PG}
                        referralData={this.state.referralData}
                      />
                    </RouteGuard>
                  }
                />
                {isPartnershipsInviteFlowEnabled || isPlatformPartnerWithPGInviteFlow ? (
                  <Route
                    path="all"
                    element={
                      <>
                        <InviteNavLinks
                          productType={PRODUCT_TYPE.PG}
                          onAcceptedInvitesClick={trackAcceptedInvitesClick}
                          onAllInvitesClick={trackAllInvitesClick}
                        />
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
                            <RouteGuard>
                              <AllInvitesTable />
                            </RouteGuard>
                          </Suspense>
                        </div>
                      </>
                    }
                  />
                ) : null}
              </Routes>
            </div>
          </content>
        </ProductWrapper>
        {isPartnershipsInviteFlowEnabled || isPlatformPartnerWithPGInviteFlow ? (
          <InviteMerchantModal
            initialProductType={product}
            initialStep={
              isPlatformPartnerInviteFlowEnabled
                ? INVITE_MERCHANT_STEPS.CHOOSE_OAUTH_APP
                : INVITE_MERCHANT_STEPS.INVITE_TABS
            }
            isOpen={this.state.isInviteMerchantModalOpen}
            onDismiss={() => this.setState({ isInviteMerchantModalOpen: false })}
          />
        ) : null}
      </>
    );
  }
}
export default withPartnerDashboardExperiments(withRouter(withI18Service(SubMerchantsList)));
