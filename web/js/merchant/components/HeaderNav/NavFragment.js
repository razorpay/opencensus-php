import React, { Component } from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import GrowthAssetEB from 'common/ui/GrowthAssetEB';
import OffersForYou from 'common/ui/OffersForYou';
import Popover, { PopoverBody } from 'common/ui/Popover';
import * as storage from 'common/utils/localStorage';
import ShowWhen from 'merchant/components/ShowWhen';
import { fetchExclusiveOffer as fetchExclusiveOfferProp } from 'merchant/reducers/growthService';

import { FtuxModal } from './FtuxModal';
import SwitchMerchant from './SwitchMerchant';
import ModesDropdown from './SwitchMode';

class NavFragment extends Component {
  constructor(props) {
    super(props);

    this.hideModePopoverToken = 'hide-mode-dd-popover';
    this.showModePopoverToken = 'show-mode-dd-popover';

    const hideSwitchModeTooltip = storage.getItem(this.hideModePopoverToken);
    const showSwitchModeTooltip = storage.getItem(this.showModePopoverToken);

    this.state = {
      showSwitchModeTooltip: !hideSwitchModeTooltip && showSwitchModeTooltip,
      showFtuxModal: false,
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

  handleModalVisibilty() {
    const expireAt = moment().add(1, 'day').unix();
    localStorage.setItem('ftux_modal', expireAt);
  }

  shouldShowFtuxModal() {
    const storedTimeStamp = localStorage.getItem('ftux_modal');
    // if the item doesn't exist, store expire time and return null
    if (!storedTimeStamp) {
      this.handleModalVisibilty();
      this.setState({
        showFtuxModal: false,
      });
    }
    if (!this.props.user.isFtuxEnabled || this.props.user.isTransacted) {
      this.setState({
        showFtuxModal: false,
      });
      return;
    }
    const current = moment().unix();
    if (current > Number(storedTimeStamp) && !this.state.showFtuxModal) {
      this.setState({
        showFtuxModal: true,
      });
    }
  }

  componentDidMount() {
    const { fetchExclusiveOffer } = this.props;
    if (this.props.user?.current) {
      fetchExclusiveOffer({ fromWhere: 'gsExclusiveOffer' });
    }
    if (this.props.user.isFtuxEnabled) {
      this.shouldShowFtuxModal();
    }
  }

  componentDidUpdate() {
    if (this.props.user.isFtuxEnabled && !this.props.user.isTransacted) {
      this.shouldShowFtuxModal();
    }
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
      exclusive_offers,
      isRTUXHomepage,
    } = this.props;
    const { showSwitchModeTooltip, showFtuxModal } = this.state;

    const isReferredMerchant = referee?.status === 'signup';

    const shouldShowGSExclusiveOffers = Object.keys(exclusive_offers || {}).length > 0;

    const canShowOnboardingOffers =
      !isReferredMerchant && canShowMtuPopup && user.isOnboardingCouponEnabled;

    const showOFYNitroFlow = user.isProjectNitroEnabled;

    const closeModal = () => {
      this.setState({
        showFtuxModal: false,
      });
    };

    return (
      <React.Fragment>
        {!isRTUXHomepage && (
          <GrowthAssetEB>
            <ShowWhen
              // eslint-disable-next-line no-shadow
              additionalCondition={(user) =>
                showOFYNitroFlow ||
                canShowOnboardingOffers ||
                user.isProjectMoonshineEnabled ||
                user?.isICICILinkedCAFlowEnabled?.('offers-for-you') ||
                shouldShowGSExclusiveOffers
              }
            >
              <OffersForYou
                canShowOnboardingOffers={canShowOnboardingOffers}
                mtuOfferCount={mtuOfferCount}
              />
            </ShowWhen>
          </GrowthAssetEB>
        )}
        <li>
          <ModesDropdown
            mode={mode}
            modeFormatted={modeFormatted}
            onSwitchMode={onSwitchMode}
            isTestModeBlocked={user.isTestModeBlocked}
            isRTUXHomepage={isRTUXHomepage}
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
        {!isRTUXHomepage && Object.keys(user.merchants).length > 1 ? (
          <li class="SwitchMerchantDropdown">
            <SwitchMerchant user={user} onSwitchMerchant={onSwitchMerchant} />
          </li>
        ) : null}
        {showFtuxModal ? (
          <FtuxModal closeModal={closeModal} handleModalVisibilty={this.handleModalVisibilty} />
        ) : null}
      </React.Fragment>
    );
  }
}

export default compose(
  connect(
    (state) => {
      return {
        ...state?.growthService?.exclusive_offers,
      };
    },
    {
      fetchExclusiveOffer: fetchExclusiveOfferProp,
    },
  ),
)(withRouter(NavFragment));
