import React, { Component } from 'react';
import { Link } from 'react-router-dom';

import Popover, { PopoverBody } from 'rzp/ui/Popover';
import { DropdownTrigger, DropdownContent } from 'rzp/ui/Dropdown';
import storage from 'rzp/utils/localStorage';

import ModesDropdown from './SwitchMode';
import SwitchMerchant from './SwitchMerchant';

class NavFragment extends Component {
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
    } = this.props;

    const { showSwitchModeTooltip } = this.state;

    console.log('....', onSwitchMerchant);

    return (
      <React.Fragment>
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
                  You can switch between Live Mode and Test Mode anytime from
                  here.
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
            <SwitchMerchant user={user} onSwitchMerchant={onSwitchMerchant} />
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
      </React.Fragment>
    );
  }
}

export default NavFragment;
