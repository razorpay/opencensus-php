import React, { Component } from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';
import storage from 'common/utils/localStorage';
import ShowWhen from 'merchant/components/ShowWhen';
import ModesDropdown from './SwitchMode';
import SwitchMerchant from './SwitchMerchant';
import OffersForYou from 'common/ui/OffersForYou';
import SuccessFullCreditModal from 'common/ui/OnboardingCoupons/SuccessFullCreditModal';
import { merchantFetch } from 'merchant/utils/ajax';
import { daysFromToday, paiseToRupees } from 'common/utils/rzp-utils';

const TRANSACTION_TIMESTAMP = 1628015429; // 4th aug,2021
const POST_INSTANTLY_ACTIVATED_DAYS_TO_SHOW_OFFER = 3; //offer allowed to show post payment activated

class NavFragment extends Component {
  constructor(props) {
    super(props);

    var hideModePopoverToken = (this.hideModePopoverToken = 'hide-mode-dd-popover'),
      showModePopoverToken = (this.showModePopoverToken = 'show-mode-dd-popover');

    const hideSwitchModeTooltip = storage.getItem(hideModePopoverToken),
      showSwitchModeTooltip = storage.getItem(showModePopoverToken);

    const { user, mode } = props;
    //number of day when payment get activated date to current date
    const numberOfDaysPaymentActivated =
      (user.activated_at && Math.abs(daysFromToday(user.activated_at))) || 0;

    this.canShowOnboardingOffers =
      user.activated &&
      mode === 'live' &&
      numberOfDaysPaymentActivated > POST_INSTANTLY_ACTIVATED_DAYS_TO_SHOW_OFFER &&
      !user.isMtuCouponApplied &&
      user.isOnboardingCouponEnabled;

    this.isNewMerchantPostMTUCouponLive = user.created_at > TRANSACTION_TIMESTAMP;
    this.isMtuOfferShowed = storage.getItem(`showed_popup--${user.current}`);

    this.state = {
      showSwitchModeTooltip: !hideSwitchModeTooltip && showSwitchModeTooltip,
      transactionAmount: null,
      isSuccessfullyCouponApplied: false,
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

  redeemOnboardingCoupon = () => {
    merchantFetch({
      url: 'coupons/apply/mtu',
      method: 'POST',
      data: JSON.stringify({}),
    }).then((response) => {
      if (response?.data?.success) {
        this.setState({ isSuccessfullyCouponApplied: true });
      }
    });
  };

  isMerchantAlreadyMTU = async () => {
    const { user } = this.props;
    if (!this.isNewMerchantPostMTUCouponLive) {
      const response = await merchantFetch({
        url: 'merchant/analytics',
        method: 'post',
        data: {
          filters: {
            default: [{ created_at: { gte: user.created_at, lte: TRANSACTION_TIMESTAMP } }],
          },
          aggregations: {
            transactionVolume: {
              agg_type: 'sum',
              details: { index: 'payments', column: 'base_amount', mode: 'live' },
            },
          },
        },
      });
      const amount =
        response?.data?.transactionVolume &&
        paiseToRupees(response.data.transactionVolume?.result[0].value);
      this.setState({
        transactionAmount: amount,
      });
      return amount > 0;
    }
    return false;
  };

  applyMTUCoupon = async () => {
    const { user } = this.props;
    const isMerchantAlreadyMTU = await this.isMerchantAlreadyMTU();

    if (!isMerchantAlreadyMTU && this.canShowOnboardingOffers) {
      const from = this.isNewMerchantPostMTUCouponLive ? user.created_at : TRANSACTION_TIMESTAMP;

      merchantFetch({
        url: 'merchant/analytics',
        method: 'post',
        data: {
          filters: {
            default: [{ created_at: { gte: from, lte: new Date().getTime() } }],
          },
          aggregations: {
            transactionVolume: {
              agg_type: 'sum',
              details: { index: 'payments', column: 'base_amount', mode: 'live' },
            },
          },
        },
      }).then((res) => {
        if (res?.data?.transactionVolume) {
          const payment = paiseToRupees(res.data.transactionVolume?.result[0].value);
          this.setState({
            transactionAmount: payment,
          });
          if (payment > 0 && this.isMtuOfferShowed === 'visited') {
            this.redeemOnboardingCoupon();
          }
        }
      });
    }
  };

  componentDidMount() {
    if (this.canShowOnboardingOffers) {
      this.applyMTUCoupon();
    }
  }

  render() {
    const { user, mode, modeFormatted, onSwitchMode, onSwitchMerchant } = this.props;
    const { showSwitchModeTooltip, transactionAmount, isSuccessfullyCouponApplied } = this.state;

    const canShowOnboardingOffers = this.canShowOnboardingOffers && transactionAmount == 0;

    return (
      <React.Fragment>
        <ShowWhen
          additionalCondition={(user) =>
            canShowOnboardingOffers ||
            user.isProjectNitroEnabled ||
            user.isProjectNitroCorporateCard
          }
        >
          <OffersForYou canShowOnboardingOffers={canShowOnboardingOffers} />
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
        {isSuccessfullyCouponApplied && (
          <SuccessFullCreditModal
            onCloseModal={() => this.setState({ isSuccessfullyCouponApplied: false })}
          />
        )}
      </React.Fragment>
    );
  }
}

export default NavFragment;
