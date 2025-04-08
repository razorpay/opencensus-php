import React, { Component } from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import { withSplitzService } from 'common/splitz';
import GrowthAssetEB from 'common/ui/GrowthAssetEB';
import OffersForYou from 'common/ui/OffersForYou';
import Popover, { PopoverBody } from 'common/ui/Popover';
import * as storage from 'common/utils/localStorage';
import ShowWhen from 'merchant/components/ShowWhen';
import { fetchExclusiveOffer as fetchExclusiveOfferProp } from 'merchant/reducers/growthService';

import { FtuxModal } from './FtuxModal';
import SwitchMerchant from './SwitchMerchant';
import ModesDropdown from './SwitchMode';
import { isEligibleForFtuxV1 } from '../Activation/ActivationUtils';

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

    const { splitz, user } = this.props;
    const { abExperiments } = splitz ?? {};

    const isFtuxEnabled = isEligibleForFtuxV1({ user, abExperiments });

    if (!isFtuxEnabled || user.isTransacted) {
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
    const { fetchExclusiveOffer, splitz, user } = this.props;
    if (user?.current) {
      fetchExclusiveOffer({ fromWhere: 'gsExclusiveOffer' });
    }

    const { abExperiments } = splitz ?? {};

    const isFtuxEnabled = isEligibleForFtuxV1({ user, abExperiments });
    if (isFtuxEnabled) {
      this.shouldShowFtuxModal();
    }
  }

  componentDidUpdate() {
    const { user, splitz } = this.props;
    const { abExperiments } = splitz ?? {};

    const isFtuxEnabled = isEligibleForFtuxV1({ user, abExperiments });

    if (isFtuxEnabled && !user.isTransacted) {
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
      showNewHomePage,
    } = this.props;
    const { showSwitchModeTooltip, showFtuxModal } = this.state;

    const shouldShowGSExclusiveOffers = Object.keys(exclusive_offers || {}).length > 0;

    const showOFYNitroFlow = user.isProjectNitroEnabled;

    const closeModal = () => {
      this.setState({
        showFtuxModal: false,
      });
    };

    return (
      <React.Fragment>
        {!showNewHomePage && (
          <GrowthAssetEB>
            <ShowWhen
              // eslint-disable-next-line no-shadow
              additionalCondition={(user) =>
                showOFYNitroFlow ||
                user.isProjectMoonshineEnabled ||
                user?.isICICILinkedCAFlowEnabled?.('offers-for-you') ||
                shouldShowGSExclusiveOffers
              }
            >
              <OffersForYou canShowOnboardingOffers={false} mtuOfferCount={mtuOfferCount} />
            </ShowWhen>
          </GrowthAssetEB>
        )}
        <li>
          <ModesDropdown
            mode={mode}
            modeFormatted={modeFormatted}
            onSwitchMode={onSwitchMode}
            isTestModeBlocked={user.isTestModeBlocked}
            showNewHomePage={showNewHomePage}
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
        {!showNewHomePage && Object.keys(user.merchants).length > 1 ? (
          <li className="SwitchMerchantDropdown">
            <SwitchMerchant user={user} onSwitchMerchant={onSwitchMerchant} />
          </li>
        ) : null}
        {/* TODO[IMP]: Move this to new header component for connected navigation post inital release */}
        {showFtuxModal ? (
          <FtuxModal
            closeModal={closeModal}
            user={user}
            handleModalVisibilty={this.handleModalVisibilty}
          />
        ) : null}
      </React.Fragment>
    );
  }
}

export default withSplitzService(
  compose(
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
  )(withRouter(NavFragment)),
);
