import { NavLink, Navigate, useLocation } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import { useI18Service } from 'common/i18';
import { CLICK_ON_BALANCES_TAB, CLICK_ON_CREDITS_TAB } from './ga';
import { analyticsTrack } from 'common/utils/analytics';
import { connect } from 'react-redux';
import DashboardBanner from 'common/ui/DashboardBanner';
import { useState, useEffect } from 'react';
import getMobileDetect from 'common/utils/mobileDetect';
import { isJKOfflineMerchant } from '@libs/shared-utils';
import { fetchMerchantWebsiteDetails } from 'merchant/reducers/websitecompliance';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { isTrustedBadgeAllowed } from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import { Badge, OffersIcon } from '@razorpay/blade/components';
import { StyledHeader } from 'merchant/views/AccountAndSettings/Pricing/Pricing.styles';
import { withRouter } from 'common/deprecated/withRouter';
import { useSplitzService } from 'common/splitz';

const {
  ACCOUNT_AND_SETTINGS,
  WEBSITE_APP_SETTINGS,
  TRUSTED_BADGE,
  MANAGE_TEAM_DETAILS,
  PRICING_PLANS,
  CREDITS,
} = ROUTES_INFO;

const MyAccount = (props) => {
  const [isWebView, setWebView] = useState(false);
  const { pathname } = useLocation();
  const { isConfigTagEnabled } = useI18Service();
  const { abExperiments } = useSplitzService();
  const extraConfig = { abExperiments, isConfigTagEnabled };

  const shouldHideNavLinks = isJKOfflineMerchant(props.org, props.user);

  useEffect(() => {
    if (getMobileDetect().isWebView()) {
      setWebView(true);
    }
  }, []);

  useEffect(() => {
    if (
      !Object.keys(props.websiteSectionDetailsData.data).length &&
      !props.websiteSectionDetailsData.error
    ) {
      if (props.user.isWebsiteComplianceFlowEnabled) props.fetchMerchantWebsiteDetails();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const { websiteSectionDetailsData, user, children } = props;

  if (user.isAccountAndSettingsRevampEnabled) {
    switch (pathname) {
      case '/website-app-details':
        return <Navigate to={WEBSITE_APP_SETTINGS} replace />;
      case '/trustedbadge':
        return <Navigate to={TRUSTED_BADGE} replace />;
      case '/team':
        return <Navigate to={MANAGE_TEAM_DETAILS} replace />;
      case '/pricing-plans':
        return <Navigate to={PRICING_PLANS} replace />;
      case '/addfunds':
        return <Navigate to={CREDITS} replace />;
      default:
        return <Navigate to={ACCOUNT_AND_SETTINGS} replace />;
    }
  }

  return (
    <>
      <div className="banner-container">
        <DashboardBanner />
      </div>
      <tabbed-container>
        {/* To make the header scrollable we just need to add this new class to the header component */}
        {!isWebView && (
          <StyledHeader showTopBorder id="myaccount-header" className="scrollable-tab-header">
            <ShowWhen additionalCondition={(user) => user.isAllowedView('profile')}>
              <NavLink to="/profile">Profile</NavLink>
            </ShowWhen>
            <ShowWhen additionalCondition={() => !shouldHideNavLinks}>
              <ShowWhen
                additionalCondition={(user) =>
                  user.isWebsiteComplianceFlowEnabled &&
                  websiteSectionDetailsData.data.isWebsiteSectionsApplicable &&
                  !isConfigTagEnabled('contact.website_app_details')
                }
              >
                <NavLink to="/website-app-details">Website/App details</NavLink>
              </ShowWhen>
              <ShowWhen additionalCondition={(user) => isTrustedBadgeAllowed(user, extraConfig)}>
                <NavLink to="/trustedbadge">Trusted Badge</NavLink>
              </ShowWhen>

              <ShowWhen
                additionalCondition={(user) =>
                  user.isAllowedView('credits') && !isConfigTagEnabled('account.credits')
                }
              >
                <NavLink
                  to="/credits"
                  onClick={() => {
                    analyticsTrack(CLICK_ON_CREDITS_TAB);
                  }}
                >
                  Credits
                </NavLink>
              </ShowWhen>

              <ShowWhen
                additionalCondition={(user) =>
                  user.isAllowedView('add_funds') && !isConfigTagEnabled('account.balances')
                }
              >
                <NavLink
                  to="/addfunds"
                  onClick={() => {
                    analyticsTrack(CLICK_ON_BALANCES_TAB);
                  }}
                >
                  Balances
                </NavLink>
              </ShowWhen>

              <ShowWhen featureEnabled="Referral">
                <NavLink to="/referrals">Referrals</NavLink>
              </ShowWhen>
            </ShowWhen>

            <ShowWhen additionalCondition={(user) => user.isAllowedTeamManagement}>
              <NavLink to="/team">Manage Team</NavLink>
            </ShowWhen>
            <ShowWhen additionalCondition={() => !shouldHideNavLinks}>
              <ShowWhen
                myRole="owner admin"
                additionalCondition={(user) =>
                  user.isFdTicketsEnabled &&
                  !user.isComdelApiEnabled &&
                  !isConfigTagEnabled('account.support_history')
                }
              >
                <NavLink
                  onClick={() => {
                    window.rzpAnalytics?.({
                      eventCategory: 'Ticket Dashboard',
                      eventAction: 'Support tickets tab clicked',
                      eventLabel: `Tickets`,
                    });
                  }}
                  to="/ticket-support/tickets"
                >
                  {props.user.isMobileSignupCareActive ? `Support History` : `Support Tickets`}
                </NavLink>
              </ShowWhen>
              <ShowWhen additionalCondition={(user) => user?.isBundlePricingEnabled}>
                <NavLink className="flex-link" to="/pricing-plans">
                  Pricing Plans
                  <Badge emphasis="subtle" size="medium" icon={OffersIcon} color="positive">
                    NEW
                  </Badge>
                </NavLink>
              </ShowWhen>
            </ShowWhen>
          </StyledHeader>
        )}
        <content>{children}</content>
      </tabbed-container>
    </>
  );
};

export default withRouter(
  connect(
    (state) => ({
      user: state.session.user,
      websiteSectionDetailsData: state.websiteCompliance.websiteSectionDetailsData,
      enrollmentStatus: state?.bundlePricing?.enrollmentStatus || {},
      org: state.session.org,
    }),
    { fetchMerchantWebsiteDetails },
  )(MyAccount),
);
