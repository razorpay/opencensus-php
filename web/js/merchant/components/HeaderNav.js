import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import { PowerSelect } from 'react-power-select';

import Popover, { PopoverBody } from 'rzp/ui/Popover';
import Dropdown, { DropdownTrigger, DropdownContent } from 'rzp/ui/Dropdown';
import storage from 'rzp/utils/localStorage';

import ShowWhen from 'merchant/components/ShowWhen';
import ProfileDropdown from 'merchant/containers/Header/ProfileDropdown';

const analytics = action => {
  window.rzpAnalytics({
    eventCategory: 'Dashboard - Header',
    eventAction: action,
  });
};

const ModesDropdown = ({ mode, modeFormatted, onSwitchMode }) => {
  return (
    <Dropdown>
      <DropdownTrigger class="dropdown-toggle">
        <span
          class={`ModeIndicator ${
            mode === 'test' ? 'ModeIndicator--test' : 'ModeIndicator--live'
          }`}
        />{' '}
        {modeFormatted} Mode <span class="caret" />
      </DropdownTrigger>
      <DropdownContent>
        <ul class="dropdown-menu">
          <li>
            <a onClick={() => onSwitchMode('test')}>Test Mode</a>
          </li>
          <li>
            <a onClick={() => onSwitchMode('live')}>Live Mode</a>
          </li>
        </ul>
      </DropdownContent>
    </Dropdown>
  );
};

const SwitchMerchant = ({ user, onSwitchMerchant }) => {
  let merchants = user.merchants;
  merchants = Object.keys(merchants).map(merchantId => merchants[merchantId]);
  return (
    <PowerSelect
      options={merchants}
      placeholder="Switch Merchant"
      searchIndices={['name']}
      showClear={false}
      optionComponent={({ option }) => {
        return (
          <a class="SwitchMerchantDropdown__option">
            {option.id === user.current ? (
              <i class="i i-done text-success pull-right" />
            ) : null}
            <span>{option.name}</span>
          </a>
        );
      }}
      onChange={({ option, select }) => {
        if (option) {
          onSwitchMerchant(option);
        }
      }}
    />
  );
};

export default class HeaderNav extends Component {
  constructor(props) {
    super(props);

    var hideModePopoverToken = (this.hideModePopoverToken =
        'hide-mode-dd-popover'),
      showModePopoverToken = (this.showModePopoverToken =
        'show-mode-dd-popover');

    const hideSwitchModeTooltip = storage.getItem(hideModePopoverToken),
      showSwitchModeTooltip = storage.getItem(showModePopoverToken);

    this.state = {
      showSwitchModeTooltip: !hideSwitchModeTooltip && showSwitchModeTooltip,
    };

    if (hideSwitchModeTooltip && showSwitchModeTooltip) {
      storage.removeItem(this.showModePopoverToken);
    }

    this.hideSwitchModeTooltip = this.hideSwitchModeTooltip.bind(this);
  }

  hideSwitchModeTooltip() {
    this.setState({
      showSwitchModeTooltip: false,
    });

    storage.setItem(this.hideModePopoverToken, 'true');
    storage.removeItem(this.showModePopoverToken);
  }

  render() {
    const {
      user,
      mode,
      showGSTModal,
      modeFormatted,
      onSwitchMode,
      onSwitchMerchant,
      toggleMobileNav,
      showMobileNav,
    } = this.props;

    const { showSwitchModeTooltip } = this.state;

    return (
      <nav class="navbar navbar-default navbar-fixed-top">
        <div class="container-fluid">
          <div class="navbar-header">
            <button
              type="button"
              class="navbar-toggle"
              data-toggle="collapse"
              onClick={toggleMobileNav}
            >
              <span class="i-bar" />
              <span class="i-bar" />
              <span class="i-bar" />
            </button>
          </div>
          <div
            class={`${showMobileNav ? '' : 'collapse '}navbar-collapse`}
            id="headerNav"
          >
            <ul class="nav navbar-nav navbar-right">
              <ShowWhen myRole="owner finance">
                <li>
                  <a onClick={showGSTModal}>GST Details</a>
                </li>
              </ShowWhen>
              <li>
                <ModesDropdown
                  mode={mode}
                  modeFormatted={modeFormatted}
                  onSwitchMode={onSwitchMode}
                />
                {showSwitchModeTooltip && (
                  <Popover persistent={true} theme="dark">
                    <PopoverBody>
                      <p>
                        You can switch between Live Mode and Test Mode anytime
                        from here.
                      </p>
                      <div className="clearfix">
                        <a
                          className="pull-right"
                          onClick={this.hideSwitchModeTooltip}
                        >
                          OK. Got it
                        </a>
                      </div>
                    </PopoverBody>
                  </Popover>
                )}
              </li>
              {Object.keys(user.merchants).length > 1 ? (
                <li class="SwitchMerchantDropdown">
                  <SwitchMerchant
                    user={user}
                    onSwitchMerchant={onSwitchMerchant}
                  />
                </li>
              ) : null}
              <li>
                <a
                  target="_blank"
                  href="https://docs.razorpay.com"
                  onClick={() => analytics('Go To - Documentation')}
                >
                  <span>Documentation</span>
                </a>
              </li>
              <li id="profile-dropdown">
                <ProfileDropdown analytics={analytics} />
              </li>
            </ul>
          </div>
        </div>
      </nav>
    );
  }
}
