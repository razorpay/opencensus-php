import { Route, NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
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
import TicketsContainer from '../TicketSupport/components/TicketsContainer';
import Tickets from '../TicketSupport/components/Tickets';
import { connect } from 'react-redux';
import DashboardBanner from '../../../common/ui/DashboardBanner';
import { useState, useEffect } from 'react';
import getMobileDetect from 'common/utils/mobileDetect';
import { isOrgFeatureExist } from 'merchant/models/User';

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

            <ShowWhen additionalCondition={(_) => false}>
              <NavLink to="/website-app-details">Website/App details</NavLink>
            </ShowWhen>
            <ShowWhen additionalCondition={() => isTrustedBadge}>
              <NavLink to="/trustedbadge">Trusted Badge</NavLink>
            </ShowWhen>

            <ShowWhen additionalCondition={(user) => user.isAllowedView('credits')}>
              <NavLink
                to="/credits"
                onClick={() => {
                  analyticsTrack(CLICK_ON_CREDITS_TAB);
                }}
              >
                Credits
              </NavLink>
            </ShowWhen>

            <ShowWhen additionalCondition={(user) => user.isAllowedView('add_funds')}>
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
              additionalCondition={(user) => user.isFdTicketsEnabled && !user.isComdelApiEnabled}
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
          <Route path="/trustedbadge" component={TrustedBadge} />
          <Route path="/profile" component={Profile} />
          <Route path="/website-app-details" component={WebsiteAppDetails} />
          <Route path="/credits" component={Credits} />
          <Route path="/addfunds" component={Balances} />
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

export default connect((state) => ({ user: state.session.user }))(MyAccount);
