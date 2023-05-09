import { Route, NavLink, Redirect } from 'react-router-dom';
import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';
import TrustedBadge from 'merchant/views/Account/TrustedBadge';
import Profile from 'merchant/views/Account/Profile';
import WebsiteAppDetails from 'merchant/views/Account/WebsiteAppDetails';
import Balances from 'merchant/views/Account/Balances';
import Credits from 'merchant/views/Account/Credits/List';
import ManageTeam from 'merchant/views/Account/ManageTeam';
import PricingPlans from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans';
import Referrals from 'merchant/views/Account/Referrals/List';
import Conversations from 'merchant/views/TicketSupport/components/Conversations';
import { CLICK_ON_BALANCES_TAB, CLICK_ON_CREDITS_TAB } from './ga';
import { analyticsTrack } from 'common/utils/analytics';
import TicketsContainer from 'merchant/views/TicketSupport/components/TicketsContainer';
import Tickets from 'merchant/views/TicketSupport/components/Tickets';
import { connect } from 'react-redux';
import DashboardBanner from 'common/ui/DashboardBanner';
import { useState, useEffect } from 'react';
import getMobileDetect from 'common/utils/mobileDetect';
import { fetchMerchantWebsiteDetails } from 'merchant/reducers/websitecompliance';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { isTrustedBadgeAllowed } from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import { Badge, OffersIcon } from '@razorpay/blade/components';
import { StyledHeader } from 'merchant/views/AccountAndSettings/Pricing/Pricing.styles';

const {
  ACCOUNT_AND_SETTINGS,
  WEBSITE_APP_SETTINGS,
  TRUSTED_BADGE,
  MANAGE_TEAM_DETAILS,
  PRICING_PLANS,
} = ROUTES_INFO;

const MyAccount = (props) => {
  const [isWebView, setWebView] = useState(false);

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

  const {
    websiteSectionDetailsData,
    user,
    location: { pathname },
  } = props;

  if (user.isAccountAndSettingsRevampEnabled) {
    switch (pathname) {
      case '/website-app-details':
        return <Redirect to={WEBSITE_APP_SETTINGS} />;
      case '/trustedbadge':
        return <Redirect to={TRUSTED_BADGE} />;
      case '/team':
        return <Redirect to={MANAGE_TEAM_DETAILS} />;
      case '/pricing-plans':
        return <Redirect to={PRICING_PLANS} />;
      default:
        return <Redirect to={ACCOUNT_AND_SETTINGS} />;
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

            <ShowWhen
              additionalCondition={(user) =>
                user.isWebsiteComplianceFlowEnabled &&
                websiteSectionDetailsData.data.isWebsiteSectionsApplicable &&
                !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.WebsiteAppDetails)
              }
            >
              <NavLink to="/website-app-details">Website/App details</NavLink>
            </ShowWhen>
            <ShowWhen additionalCondition={isTrustedBadgeAllowed}>
              <NavLink to="/trustedbadge">Trusted Badge</NavLink>
            </ShowWhen>

            <ShowWhen
              additionalCondition={(user) =>
                user.isAllowedView('credits') &&
                !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Credits)
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
                user.isAllowedView('add_funds') &&
                !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Balances)
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

            <ShowWhen additionalCondition={(user) => user.isAllowedTeamManagement}>
              <NavLink to="/team">Manage Team</NavLink>
            </ShowWhen>

            <ShowWhen
              myRole="owner admin"
              additionalCondition={(user) =>
                user.isFdTicketsEnabled &&
                !user.isComdelApiEnabled &&
                !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.SupportHistory)
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
                <Badge contrast="low" variant="positive" size="medium" icon={OffersIcon}>
                  NEW
                </Badge>
              </NavLink>
            </ShowWhen>
          </StyledHeader>
        )}
        <content>
          <ShowWhenRoute
            path="/trustedbadge"
            component={TrustedBadge}
            additionalCondition={isTrustedBadgeAllowed}
          />
          <Route path="/profile" component={Profile} />
          <ShowWhenRoute
            path="/website-app-details"
            component={WebsiteAppDetails}
            additionalCondition={(user) =>
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.WebsiteAppDetails)
            }
          />
          <ShowWhenRoute
            path="/credits"
            component={Credits}
            additionalCondition={(user) =>
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Credits)
            }
          />
          <ShowWhenRoute
            path="/addfunds"
            component={Balances}
            additionalCondition={(user) =>
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Balances)
            }
          />
          <Route path="/referrals" component={Referrals} />
          <Route path="/team" component={ManageTeam} />
          <ShowWhenRoute
            path="/pricing-plans"
            component={PricingPlans}
            additionalCondition={(user) => user?.isBundlePricingEnabled}
          />
          {props.user.isMobileSignupCareActive ? (
            <Route path="/ticket-support/tickets" component={TicketsContainer} />
          ) : (
            <Route path="/ticket-support/tickets" component={Tickets} />
          )}
          <Route
            path="/ticket-support/:instance/:id/:ticketType/conversation"
            component={Conversations}
          />
        </content>
      </tabbed-container>
    </>
  );
};

export default connect(
  (state) => ({
    user: state.session.user,
    websiteSectionDetailsData: state.websiteCompliance.websiteSectionDetailsData,
    enrollmentStatus: state?.bundlePricing?.enrollmentStatus || {},
  }),
  { fetchMerchantWebsiteDetails },
)(MyAccount);
