import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link, withRouter } from 'react-router-dom';
import RTracking from 'react-tracking';

import NotificationsDropdown from 'common/ui/NotificationsDropdown';
import WhatsNew from 'common/ui/WhatsNew/Old';
import NotificationIcon from 'common/ui/WhatsNew/Icon';
import { toggleMobileMenu } from 'merchant/reducers/app';

import ShowWhen from 'merchant/components/ShowWhen';
import NavFragment from './NavFragment';
import ModesDropdown from './SwitchMode';
import AppSwitcher from './AppSwitcher';
import ProfileDropdown from './ProfileDropdown';

const analytics = (action) => {
  window.rzpAnalytics({
    eventCategory: 'Dashboard - Header',
    eventAction: action,
  });
};

function toggleDropdown() {
  document.querySelector('#profile-dropdown .dropdown-toggle').click();
}

@RTracking(() => window.rzpQ.component('HeaderNav'))
@withRouter
@connect(
  (state) => ({
    activePageName: state.app.activePageName,
    user: state.session.user,
  }),
  { toggleMobileMenu },
)
export default class HeaderNav extends Component {
  constructor(props) {
    super(props);

    this.onToggleAppMenu = this.onToggleAppMenu.bind(this);
  }

  componentDidMount() {
    const hash = this.props.history.location.hash;
    if (hash === '#profile_dropdown') {
      toggleDropdown();
    }

    if (this.props.user.isAppSwitcherEnabled) {
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().success('dashboard.display_appswitcher', {
          menu_title: 'App Switcher',
          session_id: window.session_id,
        }),
      );
    }
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.location.hash !== this.props.location.hash) {
      if (nextProps.location.hash === '#profile_dropdown') {
        toggleDropdown();
      }
    }
  }

  onToggleAppMenu() {
    analytics('Click - Sidebar Toggle');
    this.props.toggleMobileMenu();
  }

  render() {
    const {
        user,
        mode,
        showGSTModal,
        modeFormatted,
        onSwitchMode,
        onSwitchMerchant,
        showMobileNav,
        analytics,
        activePageName,
      } = this.props,
      fragmentSpecificProps = {
        mode,
      },
      commonProps = {
        user,
        showGSTModal,
        modeFormatted,
        onSwitchMode,
        onSwitchMerchant,
      };

    return (
      <nav class="navbar navbar-default navbar-fixed-top">
        <div class="container-fluid">
          <div className="navbar-collapse" id="headerNav">
            {!showMobileNav && !user.isOrgRZP && !user.isOrgAxis && (
              <img
                src="/img/branding/powered-by-razorpay-dashboard.png"
                class="rzp-branding-logo logo-header"
                alt="Powered by Razorpay"
              />
            )}
            {showMobileNav && (
              <div className="pull-left navbar-toggle-container">
                <button type="button" className="navbar-toggle" onClick={this.onToggleAppMenu}>
                  <span class="i-bar" />
                  <span class="i-bar" />
                  <span class="i-bar" />
                </button>{' '}
                {activePageName || 'Dashboard'}
              </div>
            )}
            <ul className="nav navbar-nav navbar-right">
              {(!showMobileNav && (
                <NavFragment analytics={analytics} {...fragmentSpecificProps} {...commonProps} />
              )) || (
                <li>
                  <ModesDropdown
                    mode={mode}
                    modeFormatted={modeFormatted}
                    onSwitchMode={onSwitchMode}
                    isTestModeBlocked={user.isTestModeBlocked}
                  />
                </li>
              )}
              <ShowWhen
                additionalCondition={(user) =>
                  user.isOrgAllowedFunctionality('external_links') &&
                  !user.isWhatsNewSectionEnabled &&
                  !user.isOrgAxis
                }
              >
                <li id="notifications-dropdown">
                  <NotificationsDropdown
                    analytics={analytics}
                    showMobileNav={showMobileNav}
                    {...commonProps}
                  />
                </li>
              </ShowWhen>
              <ShowWhen
                additionalCondition={(user) =>
                  user.isOrgAllowedFunctionality('external_links') &&
                  (user.isAnnouncementTextEnabled || user.isWhatsNewTextEnabled)
                }
              >
                <li id="whats-new-section">
                  {user.isWhatsNewLazyEnabled ? (
                    <NotificationIcon
                      analytics={analytics}
                      showMobileNav={showMobileNav}
                      {...commonProps}
                    />
                  ) : (
                    <WhatsNew
                      analytics={analytics}
                      showMobileNav={showMobileNav}
                      {...commonProps}
                    />
                  )}
                </li>
              </ShowWhen>
              <ShowWhen
                additionalCondition={(user) => user.isAppSwitcherEnabled && user.isAccepted}
              >
                <li id="app-switcher">
                  <AppSwitcher analytics={analytics} {...commonProps} />
                </li>
              </ShowWhen>
              <ShowWhen
                additionalCondition={(user) =>
                  user.isOrgAllowedFunctionality('external_links') &&
                  user.isWhatsNewSectionEnabled &&
                  !user.isAnnouncementTextEnabled &&
                  !user.isWhatsNewTextEnabled
                }
              >
                <li id="whats-new-section">
                  {user.isWhatsNewLazyEnabled ? (
                    <NotificationIcon
                      analytics={analytics}
                      showMobileNav={showMobileNav}
                      {...commonProps}
                    />
                  ) : (
                    <WhatsNew
                      analytics={analytics}
                      showMobileNav={showMobileNav}
                      {...commonProps}
                    />
                  )}
                </li>
              </ShowWhen>
              <li id="profile-dropdown">
                <ProfileDropdown
                  analytics={analytics}
                  showMobileNav={showMobileNav}
                  {...commonProps}
                />
              </li>
            </ul>
          </div>
        </div>
      </nav>
    );
  }
}
