import React, { Component } from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';
import * as storage from 'common/utils/localStorage';
import ShowWhen from 'merchant/components/ShowWhen';
import ModesDropdown from './SwitchMode';
import SwitchMerchant from './SwitchMerchant';
import OffersForYou from 'common/ui/OffersForYou';
import SuccessFullCreditModal from 'common/ui/OnboardingCoupons/SuccessFullCreditModal';
import { merchantFetch } from 'merchant/utils/ajax';
import { daysFromToday, paiseToRupees } from 'common/utils/rzp-utils';
import { fetchModalConfigDetails } from 'merchant/reducers/ModalConfigApi';

const TRANSACTION_TIMESTAMP = 1628015429; // 4th aug,2021
const POST_INSTANTLY_ACTIVATED_DAYS_TO_SHOW_OFFER = 2; //offer allowed to show post payment activated

class NavFragment extends Component {
  constructor(props) {
    super(props);

    this.hideModePopoverToken = 'hide-mode-dd-popover';
    this.showModePopoverToken = 'show-mode-dd-popover';

    const hideSwitchModeTooltip = storage.getItem(this.hideModePopoverToken);
    const showSwitchModeTooltip = storage.getItem(this.showModePopoverToken);

    const { user, mode, referee } = props;
    //number of day when payment get activated date to current date
    const numberOfDaysPaymentActivated =
      (user.activated_at && Math.abs(daysFromToday(user.activated_at))) || 0;
    const isReferredMerchant = referee?.status === 'signup';

    this.canShowOnboardingOffers =
      !isReferredMerchant &&
      user.activated &&
      mode === 'live' &&
      numberOfDaysPaymentActivated >= POST_INSTANTLY_ACTIVATED_DAYS_TO_SHOW_OFFER &&
      !user.isMtuCouponApplied &&
      user.isOnboardingCouponEnabled;

    this.isNewMerchantPostMTUCouponLive = user.created_at > TRANSACTION_TIMESTAMP;

    this.state = {
      showSwitchModeTooltip: !hideSwitchModeTooltip && showSwitchModeTooltip,
      transactionAmount: null,
      isSuccessfullyCouponApplied: false,
      mtuOfferCount: null,
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
            default: [
              {
                created_at: { gte: user.created_at, lte: TRANSACTION_TIMESTAMP },
                authorized_at: { gt: 0 },
              },
            ],
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

  fetchMTUOfferCount = async () => {
    const namespace = 'onboarding';
    const res = await fetchModalConfigDetails(namespace);
    if (res?.data) {
      const count = Number(res.data?.mtu_coupon_popup_count);
      this.setState({ mtuOfferCount: count });
      return count;
    }
    return 0;
  };

  applyMTUCoupon = async () => {
    const { user } = this.props;
    const isMerchantAlreadyMTU = await this.isMerchantAlreadyMTU();

    if (!isMerchantAlreadyMTU && this.canShowOnboardingOffers) {
      const mtuCount = await this.fetchMTUOfferCount();

      const from = this.isNewMerchantPostMTUCouponLive ? user.created_at : TRANSACTION_TIMESTAMP;

      merchantFetch({
        url: 'merchant/analytics',
        method: 'post',
        data: {
          filters: {
            default: [
              { created_at: { gte: from, lte: new Date().getTime() }, authorized_at: { gt: 0 } },
            ],
          },
          aggregations: {
            firstTransaction: {
              agg_type: 'oldest',
              details: {
                index: 'payments',
                column: 'created_at',
                mode: 'live',
                limit: 1,
                result_fields: ['base_amount'],
              },
            },
          },
        },
      }).then((res) => {
        if (res?.data?.firstTransaction) {
          const payment = res.data.firstTransaction?.result?.length
            ? paiseToRupees(res.data.firstTransaction?.result[0].base_amount)
            : 0;
          this.setState({
            transactionAmount: payment,
          });
          if (payment > 0 && mtuCount > 0) {
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
    const {
      showSwitchModeTooltip,
      transactionAmount,
      isSuccessfullyCouponApplied,
      mtuOfferCount,
    } = this.state;

    const canShowOnboardingOffers = this.canShowOnboardingOffers && transactionAmount == 0;
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
            user.isProjectKeystoneCashAdvanceEnabled
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
