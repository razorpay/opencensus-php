import React, { Component } from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';
import * as storage from 'common/utils/localStorage';
import ShowWhen from 'merchant/components/ShowWhen';
import ModesDropdown from './SwitchMode';
import SwitchMerchant from './SwitchMerchant';
import OffersForYou from 'common/ui/OffersForYou';

class NavFragment extends Component {
  constructor(props) {
    super(props);

    this.hideModePopoverToken = 'hide-mode-dd-popover';
    this.showModePopoverToken = 'show-mode-dd-popover';

    const hideSwitchModeTooltip = storage.getItem(this.hideModePopoverToken);
    const showSwitchModeTooltip = storage.getItem(this.showModePopoverToken);

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
      modeFormatted,
      onSwitchMode,
      onSwitchMerchant,
      referee,
      canShowMtuPopup,
      mtuOfferCount,
    } = this.props;
    const { showSwitchModeTooltip } = this.state;

    const isReferredMerchant = referee?.status === 'signup';

    const canShowOnboardingOffers =
      !isReferredMerchant && canShowMtuPopup && user.isOnboardingCouponEnabled;

    const showOFYNitroFlow = user.isPartOfNeostone
      ? user.isNeostoneFlowEnabled('offers-for-you')
      : user.isProjectNitroEnabled;

    return (
      <React.Fragment>
        <ShowWhen
          // eslint-disable-next-line no-shadow
          additionalCondition={(user) =>
            showOFYNitroFlow ||
            canShowOnboardingOffers ||
            user.isProjectNitroCorporateCard ||
            user.isProjectMoonshineEnabled ||
            user.isProjectKeystoneCorporateCardsEnabled ||
            user.isProjectKeystoneCashAdvanceEnabled ||
            user.isGSExclusiveOfferEnabled ||
            user.isICICILinkedCAFlowEnabled('offers-for-you')
          }
        >
          <OffersForYou
            canShowOnboardingOffers={canShowOnboardingOffers}
            mtuOfferCount={mtuOfferCount}
          />
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
