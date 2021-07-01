import React, { Component } from 'react';
import { Link } from 'react-router-dom';

import Popover, { PopoverBody } from 'common/ui/Popover';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import storage from 'common/utils/localStorage';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import ShowWhen from 'merchant/components/ShowWhen';

import ModesDropdown from './SwitchMode';
import SwitchMerchant from './SwitchMerchant';
import OffersForYou from 'common/ui/OffersForYou';

class NavFragment extends Component {
  constructor(props) {
    super(props);

    var hideModePopoverToken = (this.hideModePopoverToken = 'hide-mode-dd-popover'),
      showModePopoverToken = (this.showModePopoverToken = 'show-mode-dd-popover');

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
    const { user, mode, showGSTModal, modeFormatted, onSwitchMode, onSwitchMerchant } = this.props;

    const { showSwitchModeTooltip } = this.state;

    return (
      <React.Fragment>
        <ShowWhen additionalCondition={(user) => user.isProjectNitroEnabled } >
          <OffersForYou/>
        </ShowWhen>
        <li>
          <ModesDropdown
            mode={mode}
            modeFormatted={modeFormatted}
            onSwitchMode={onSwitchMode}
            isTestModeBlocked={user.isTestModeBlocked}
          />
          {showSwitchModeTooltip && (
            <Popover persistent={true} theme="dark">
              <PopoverBody>
                <p>You can switch between Live Mode and Test Mode anytime from here.</p>
                <div className="clearfix">
                  <a className="pull-right" onClick={this.hideSwitchModeTooltip}>
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
      </React.Fragment>
    );
  }
}

export default NavFragment;
