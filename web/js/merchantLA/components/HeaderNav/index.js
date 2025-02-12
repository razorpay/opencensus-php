import React, { Component } from 'react';
import { connect } from 'react-redux';

import NavFragment from 'merchant/components/HeaderNav/NavFragment';
import ProfileDropdown from 'merchantLA/containers/Header/ProfileDropdown';
import { toggleMobileMenu } from 'merchantLA/reducers/app';

const analytics = (action) => {
  window.rzpAnalytics({
    eventCategory: 'LA Dashboard - Header',
    eventAction: action,
  });
};

class HeaderNav extends Component {
  constructor(props) {
    super(props);

    this.onToggleAppMenu = this.onToggleAppMenu.bind(this);
  }

  onToggleAppMenu() {
    analytics('Click - Sidebar Toggle');
    this.props.toggleMobileMenu();
  }

  render() {
    const {
      user,
      mode,
      modeFormatted,
      onSwitchMode,
      onSwitchMerchant,
      showMobileNav,
      activePageName,
    } = this.props;
    const fragmentSpecificProps = {
      mode,
    };
    const commonProps = {
      user,
      modeFormatted,
      onSwitchMode,
      onSwitchMerchant,
    };

    return (
      <nav className="navbar navbar-default navbar-fixed-top navbar-left-alignment">
        <div className="container-fluid navbar-container">
          <div className="navbar-collapse" id="headerNav123">
            {showMobileNav && (
              <div className="pull-left navbar-toggle-container">
                <button type="button" className="navbar-toggle" onClick={this.onToggleAppMenu}>
                  <span className="i-bar" />
                  <span className="i-bar" />
                  <span className="i-bar" />
                </button>{' '}
                {activePageName || 'Dashboard'}
              </div>
            )}
            <ul className="nav navbar-nav navbar-right">
              {!showMobileNav && (
                <NavFragment analytics={analytics} {...fragmentSpecificProps} {...commonProps} />
              )}
              <li id="profile-dropdown" className="profile-dropdown-section">
                <ProfileDropdown
                  analytics={analytics}
                  showMobileNav={showMobileNav}
                  mode={mode}
                  onSwitchMode={onSwitchMode}
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

export default connect(
  (state) => ({
    activePageName: state.app.activePageName,
  }),
  { toggleMobileMenu },
)(HeaderNav);
