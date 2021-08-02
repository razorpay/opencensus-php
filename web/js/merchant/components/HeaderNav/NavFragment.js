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

const FIRST_TRANSACTION_TIMESTAMP = 1627842637; //from 2nd aug,2021
const POST_INSTANTLY_ACTIVATED_DAYS_TO_SHOW_OFFER = 3; //offer allowed to show post payment activated

class NavFragment extends Component {
  constructor(props) {
    super(props);

    var hideModePopoverToken = (this.hideModePopoverToken = 'hide-mode-dd-popover'),
      showModePopoverToken = (this.showModePopoverToken = 'show-mode-dd-popover');

    const hideSwitchModeTooltip = storage.getItem(hideModePopoverToken),
      showSwitchModeTooltip = storage.getItem(showModePopoverToken);

    const { user } = props;
    //number of day when payment get activated date to current date
    const numberOfDaysPaymentActivated =
      (user.activated_at && Math.abs(daysFromToday(user.activated_at))) || 0;

    this.canShowOnboardingOffers =
      user.activated &&
      numberOfDaysPaymentActivated > POST_INSTANTLY_ACTIVATED_DAYS_TO_SHOW_OFFER &&
      !user.isMtuCouponApplied &&
      user.isOnboardingCouponEnabled;

    this.state = {
      showSwitchModeTooltip: !hideSwitchModeTooltip && showSwitchModeTooltip,
      transactionAmount: 0,
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

  componentDidMount() {
    const { mode } = this.props;

    if (this.canShowOnboardingOffers) {
      merchantFetch({
        url: 'merchant/analytics',
        method: 'post',
        data: {
          filters: {
            default: [
              { created_at: { gte: FIRST_TRANSACTION_TIMESTAMP, lte: new Date().getTime() } },
            ],
          },
          aggregations: {
            transactionVolume: {
              agg_type: 'sum',
              details: { index: 'payments', column: 'base_amount', mode },
            },
          },
        },
      }).then((response) => {
        if (response?.data?.transactionVolume) {
          const payment = response.data.transactionVolume?.result[0].value;
          this.setState({
            transactionAmount: paiseToRupees(payment),
          });
          if (paiseToRupees(payment) > 0) {
            this.redeemOnboardingCoupon();
          }
        }
      });
    }
  }

  render() {
    const { user, mode, modeFormatted, onSwitchMode, onSwitchMerchant } = this.props;
    const { showSwitchModeTooltip, transactionAmount, isSuccessfullyCouponApplied } = this.state;

    const canShowOnboardingOffers = this.canShowOnboardingOffers && transactionAmount <= 0;

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
