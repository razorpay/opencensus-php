import { Route, NavLink } from 'react-router-dom';
import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';
import TrustedBadge from 'merchant/views/Account/TrustedBadge';
import Profile from 'merchant/views/Account/Profile';
import WebsiteAppDetails from 'merchant/views/Account/WebsiteAppDetails';
import Balances from 'merchant/views/Account/Balances';
import Credits from 'merchant/views/Account/Credits/List';
import ManageTeam from 'merchant/views/Account/ManageTeam';
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
import { isOrgFeatureExist } from 'merchant/models/User';
import { fetchMerchantWebsiteDetails } from 'merchant/reducers/websitecompliance';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';

const MyAccount = (props) => {
  const [isWebView, setWebView] = useState(false);

  const isTrustedBadge = props?.user?.isOrgAxis
    ? false
    : !isOrgFeatureExist('hide_razorpay_text_link');

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
  }, []);

  const { websiteSectionDetailsData } = props;

  return (
    <>
      <div className="banner-container">
        <DashboardBanner />
      </div>
      <tabbed-container>
        {/* To make the header scrollable we just need to add this new class to the header component */}
        {!isWebView && (
          <header id="myaccount-header" className="scrollable-tab-header">
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
            <ShowWhen
              additionalCondition={(user) =>
                isTrustedBadge && !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.TrustedBadge)
              }
            >
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
          </header>
        )}
        <content>
          <ShowWhenRoute
            path="/trustedbadge"
            component={TrustedBadge}
            additionalCondition={(user) =>
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.TrustedBadge)
            }
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
  }),
  { fetchMerchantWebsiteDetails },
)(MyAccount);
