import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link, withRouter } from 'react-router-dom';

import ShowWhen from 'merchant/components/ShowWhen';
import ProfileDropdown from 'merchant/containers/Header/ProfileDropdown';
import { toggleMobileMenu } from 'merchant/modules/app';

import NavFragment from './NavFragment';
import ModesDropdown from './SwitchMode';

const analytics = action => {
  window.rzpAnalytics({
    eventCategory: 'Dashboard - Header',
    eventAction: action,
  });
};

@connect(
  state => ({
    activePageName: state.app.activePageName,
  }),
  { toggleMobileMenu }
)
export default class HeaderNav extends Component {
  constructor(props) {
    super(props);

    this.onToggleAppMenu = this.onToggleAppMenu.bind(this);
  }

  onToggleAppMenu() {
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
            {showMobileNav && (
              <div className="pull-left navbar-toggle-container">
                <button
                  type="button"
                  className="navbar-toggle"
                  onClick={this.onToggleAppMenu}
                >
                  <span class="i-bar" />
                  <span class="i-bar" />
                  <span class="i-bar" />
                </button>{' '}
                {activePageName || 'Dashboard'}
              </div>
            )}
            <ul className="nav navbar-nav navbar-right">
              {(!showMobileNav && (
                <NavFragment
                  analytics={analytics}
                  {...fragmentSpecificProps}
                  {...commonProps}
                />
              )) || (
                <li>
                  <ModesDropdown
                    mode={mode}
                    modeFormatted={modeFormatted}
                    onSwitchMode={onSwitchMode}
                  />
                </li>
              )}
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
