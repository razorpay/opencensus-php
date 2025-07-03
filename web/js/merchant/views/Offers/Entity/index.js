import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';

import { Modules } from 'common/constant/enums';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import Banner from 'common/ui/Banner';
import Spinner from 'common/ui/Spinner';
import Time from 'common/ui/Time';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { OfferStatusLabel } from 'merchant/components/StatusLabel';
import * as OffersActions from 'merchant/reducers/offers/offersList';
import { emiDurationString } from 'merchant/views/Offers/New/helpers';
import {
  PAYMENT_NETWORK_MAP,
  OFFER_TYPE_LABELS,
  ISSUERS,
  OFFER_DISABLE_CTA_NETWORKS,
  PAYER_ACCOUNT_TYPES_DISPLAY,
  PAYMENT_METHODS,
  OFFER_TYPES,
} from 'merchant/views/Offers/constants';
import { withSplitzService } from 'common/splitz';
import { isOfferIdClickable, getAdditionalBenefitFromRules } from 'merchant/views/Offers/utils';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { withI18Service } from 'common/i18';

import SubscriptionUsageDetails from './SubscriptionUsageDetails';
import { UPI_APPS_SELECT_OPTIONS } from 'merchant/views/Offers/New/components/UPISelector';
import { compose } from 'redux';

const OfferContainer = ({ children }) => {
  return <div className="Offers--Details content-wrapper content-sm txn-details">{children}</div>;
};

export class OffersDetails extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = {
    prevID: this.props.id,
    fetchOfferID: this.props.fetchOffer,
  };

  get isSubscriptionOffer() {
    const { offer, user, i18 } = this.props;
    const { isConfigTagEnabled } = i18;
    return (
      offer?.product_type === 'subscription' &&
      user?.isSubscriptionOffersEnabled &&
      !isConfigTagEnabled('subscriptions.subscription_offers')
    );
  }

  componentDidMount() {
    const { fetchOffer, id, user } = this.props;
    fetchOffer(id).then(() => {
      if (isOfferIdClickable(user))
        selfServeTrackSuccess({
          selfServeAction: 'Offer Details Fetched',
          page: 'Offers',
          screen: Modules.Offers,
        });
    });
  }

  static getDerivedStateFromProps(props, state) {
    const { id } = props;
    const { prevID, fetchOfferID } = state;
    if (id !== prevID) {
      fetchOfferID(id);
      return { prevID: id };
    }
    return null;
  }

  close = () => {
    this.context.confirm({
      forceClose: true,
    });
  };

  showBajajDisableAlert = () => {
    const message = (
      <div className="text-semi-muted">
        <p>
          Bajaj is a No Cost EMI, disabling this offer would lead to disabling this method. Please
          reach out to our support team, if you would like to disable this method
        </p>
        <div className="confirm-container">
          <button
            type="button"
            className="btn btn-primary btn-expanded"
            onClick={() => {
              this.close();
            }}
          >
            Close
          </button>
        </div>
      </div>
    );
    this.context.confirm({
      header: 'Disable Offer',
      message,
      affirmativeLabel: 'No',
      hideActions: true,
    });
  };

  toggleActivation = () => {
    const { offer, tracking, fetchOffer, updateOfferInReduxList, showNotification, user } =
      this.props;
    const { id, active, current_offer_usage, payment_network, emi_subvention } = offer;
    const actionName = active ? 'Disable' : 'Enable';

    const showDisableAlert = OFFER_DISABLE_CTA_NETWORKS.includes(payment_network) && emi_subvention;

    if (active && showDisableAlert) {
      // Track disable offer click for Bajaj
      tracking.trackEvent(
        window.rzpQ.merchantActions().clicked('bajaj_offer_disable', {
          clicked_on: 'disable',
          merchant_id: user.merchant.id,
          offer_id: id,
        }),
      );
      this.showBajajDisableAlert();
      return;
    }

    // analytics
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('Offer_edit', {
        edited_field: ['active'],
      }),
    );

    let message = (
      <div className="text-semi-muted">
        <p>The offer will be {actionName}d</p>
      </div>
    );

    if (this.isSubscriptionOffer) {
      message = (
        <div className="disable-offer-alert">
          <div className="heading">
            This Offer is active on {current_offer_usage || 0} subscriptions!
          </div>

          <div>
            Disabling this offer will not remove it from these subscriptions and it has to be
            removed manually to stop the discounts.
          </div>

          <div>Please download the report to see details of such subscriptions.</div>

          <div>Are you sure you want to disable this offer?</div>
        </div>
      );
    }

    this.context.confirm({
      header: `${actionName} Offer`,
      message,
      affirmativeLabel: `Yes, ${actionName}`,
      affirmativePendingLabel: 'Requesting...',
      abortLabel: "No, don't!",
      action: () => {
        const activationValue = active ? 0 : 1;
        offer.active = activationValue;

        return offer
          .save()
          .then((offer) => {
            showNotification({
              type: 'success',
              message: `Offer ${actionName}d!`,
            });

            fetchOffer(id);
            updateOfferInReduxList(offer);

            //analytics code here
            tracking.trackEvent(
              window.rzpQ.merchantActions().success('Offer_edit', {
                edited_field: ['active'],
                field_value: [activationValue],
              }),
            );
          })
          .catch(({ errors }) => {
            if (!errors || (errors instanceof Array === true && (!errors.length || !errors[0]))) {
              errors = 'Some network error has occurred';
            }

            showNotification({
              type: 'error',
              message: errors,
            });
          });
      },
    });
  };

  mapKeysToText(keys, values, defaultValue = 'ALL') {
    if (!keys.length) return defaultValue;
    const setValues = new Set(keys);

    return values
      .filter(({ name }) => setValues.has(name))
      .map(({ label }) => label)
      .join(', ');
  }

  renderDiscountWorthField = ({
    discountWorthBenefit,
    isUptoTypeBenefit,
    percent_rate,
    flat_cashback,
  }) => {
    if (discountWorthBenefit) {
      return (
        <EntityDetailRow label="Discount Worth">
          {isUptoTypeBenefit ? (
            <span>{discountWorthBenefit}%</span>
          ) : (
            <Amount value={discountWorthBenefit} currency="INR" />
          )}
        </EntityDetailRow>
      );
    }

    if (percent_rate) {
      return (
        <EntityDetailRow label="Discount Worth">
          <span>{percent_rate / 100}%</span>
        </EntityDetailRow>
      );
    }

    if (flat_cashback) {
      return (
        <EntityDetailRow label="Discount Worth">
          <Amount value={flat_cashback} currency="INR" />
        </EntityDetailRow>
      );
    }

    return null;
  };

  render() {
    const { loading, offer, splitz } = this.props;

    if (loading) {
      return (
        <OfferContainer>
          <div className="page-spinner-container">
            <Spinner />
          </div>
        </OfferContainer>
      );
    }

    if (!offer) {
      return <OfferContainer></OfferContainer>;
    }

    const {
      id,
      name,
      display_text,
      percent_rate,
      payment_method,
      instruments,
      iins: offerIINs,
      payment_method_type,
      payment_network,
      issuer,
      starts_at,
      ends_at,
      active,
      product_type,
      current_offer_usage,
      block,
      default_offer,
      min_amount,
      flat_cashback,
      max_cashback,
      max_offer_usage,
      type,
      emi_subvention,
      emi_durations,
      redemption_type,
      terms,
      no_of_cycles,
      max_order_amount,
      upi,
      rules,
    } = offer;

    const discountType = percent_rate ? 'Percentage' : 'Flat';
    const isPercentageDiscount = discountType === 'Percentage';

    const paymentMethods =
      payment_method === PAYMENT_METHODS.Multiple
        ? instruments?.map(({ method }) => method)
        : [payment_method];

    const paymentMethodsSet = new Set(paymentMethods);

    const isCardPayment =
      paymentMethodsSet.size === 1 && paymentMethodsSet.has(PAYMENT_METHODS.Card);
    const iins = (offerIINs && offerIINs.join(', ')) || '--';

    let paymentMethod = paymentMethods.join(', ') || '--';
    if (paymentMethod === 'card') {
      paymentMethod = 'Debit Card';
      if (payment_method_type == 'credit') {
        paymentMethod = 'Credit Card';
      }
      if (!payment_method_type) {
        paymentMethod = 'Both Credit and Debit cards';
      }
    }

    const isBajajNcEmiOffer = OFFER_DISABLE_CTA_NETWORKS.includes(payment_network);

    const disabledButtonClass = isBajajNcEmiOffer ? `link-disabled` : '';

    const {
      discountWorthBenefit,
      maxCashbackBenefit,
      isUptoTypeBenefit,
      isClubbedOffer,
      emiTypeBenefit,
    } = getAdditionalBenefitFromRules(rules, payment_method);

    return (
      <div className="Offers--Details content-wrapper content-sm txn-details">
        <div className="panel panel-default SliderPanel">
          <div className="panel-heading">
            <i className="i i-link text-primary icon--formal" /> <strong>{id}</strong>
          </div>

          {this.isSubscriptionOffer && (
            <>
              <Banner className="download-report">
                Download the report containing usage details for this offer across subscriptions.
                <AsyncBtn.Primary>Download Report</AsyncBtn.Primary>
              </Banner>

              <SubscriptionUsageDetails id={this.props.id} />
            </>
          )}

          <div className="SliderPanel__Body">
            <div className="panel-body">
              <div className="list-group details-row-container">
                <EntityDetailRow label="Created At">
                  <Time format="DD MMM YYYY, hh:mm a" value={starts_at} />
                </EntityDetailRow>

                <EntityDetailRow label="Status">
                  <OfferStatusLabel status={active ? 'enabled' : 'disabled'} />
                  <Button.Transparent
                    className={`Button--Link ${disabledButtonClass}`}
                    style={{ marginLeft: 12 }}
                    onClick={this.toggleActivation}
                  >
                    {active ? 'Disable' : 'Enable'}
                  </Button.Transparent>
                </EntityDetailRow>

                <EntityDetailRow label="Offer Name" value={name} />

                <EntityDetailRow label="Display Text" value={display_text} />

                <EntityDetailRow label="Terms" value={terms} />

                {/* TODO: Check this logic with BE */}
                {product_type && <EntityDetailRow label="Promotion Type" value={product_type} />}

                <EntityDetailRow label="Offer Usage" value={current_offer_usage} />

                <EntityDetailRow
                  label="On Offer Failure"
                  value={block ? 'Block Payment' : 'Allow Payment'}
                />

                <EntityDetailRow label="Checkout Visibility" value={default_offer ? 'Yes' : 'No'} />

                <EntityDetailRow label="Min Payment">
                  <Amount value={min_amount} currency="INR" />
                </EntityDetailRow>

                {max_order_amount && (
                  <EntityDetailRow label="Max Payment">
                    <Amount value={max_order_amount} currency="INR" />
                  </EntityDetailRow>
                )}

                <EntityDetailRow label="Start of Offer">
                  <Time format="DD MMM YYYY, hh:mm a" value={starts_at} />
                </EntityDetailRow>

                <EntityDetailRow label="Expiry of Offer">
                  <Time format="DD MMM YYYY, hh:mm a" value={ends_at} />
                </EntityDetailRow>

                <EntityDetailRow
                  label="Offer Type"
                  value={
                    isClubbedOffer
                      ? OFFER_TYPE_LABELS[OFFER_TYPES.Clubbed]
                      : emi_subvention && emiTypeBenefit
                      ? 'EMI'
                      : OFFER_TYPE_LABELS[type]
                  }
                />

                <EntityDetailRow label="Bank Name" value={ISSUERS[issuer]} />

                <EntityDetailRow label="Method" value={paymentMethod} />

                {paymentMethod === PAYMENT_METHODS.EMI && (
                  <EntityDetailRow
                    label="Method Type"
                    value={payment_method_type === 'debit' ? 'Debit Card' : 'Credit Card'}
                  />
                )}

                {emi_subvention && emiTypeBenefit ? (
                  <EntityDetailRow label="EMI Type" value={emiTypeBenefit} />
                ) : null}

                {emi_subvention && (
                  <EntityDetailRow label="Emi Durations" value={emiDurationString(emi_durations)} />
                )}

                <EntityDetailRow label="Maximum Usage" value={max_offer_usage} />

                <EntityDetailRow
                  label="Discount Type"
                  value={isUptoTypeBenefit ? 'Percentage' : discountType}
                />

                {this.renderDiscountWorthField({
                  discountWorthBenefit,
                  isUptoTypeBenefit,
                  percent_rate,
                  flat_cashback,
                })}

                {maxCashbackBenefit || (isPercentageDiscount && max_cashback) ? (
                  <EntityDetailRow label="Max Cashback">
                    <Amount value={maxCashbackBenefit || max_cashback} currency="INR" />
                  </EntityDetailRow>
                ) : null}

                {isCardPayment && (
                  <>
                    <EntityDetailRow label="IINs" value={iins} />
                    <EntityDetailRow label="Network" value={PAYMENT_NETWORK_MAP[payment_network]} />
                  </>
                )}

                {this.isSubscriptionOffer && (
                  <>
                    <EntityDetailRow label="Redemption Type" value={redemption_type} />
                    <EntityDetailRow label="Number of Cycles" value={no_of_cycles} />
                  </>
                )}
                {upi ? (
                  <>
                    <EntityDetailRow
                      data-testid="upi-apps"
                      label="UPI Apps"
                      value={this.mapKeysToText(upi.apps, UPI_APPS_SELECT_OPTIONS, 'ALL UPI Apps')}
                    />
                    <EntityDetailRow
                      data-testid="payer-account-types"
                      label="Payer Account Type"
                      value={this.mapKeysToText(
                        upi.payer_account_type,
                        PAYER_ACCOUNT_TYPES_DISPLAY,
                        'All Payer Account Types',
                      )}
                    />
                  </>
                ) : null}
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default compose(
  connect((state) => ({ ...state.offer, user: state.session.user }), {
    ...OffersActions,
    ...ModalActions,
    ...NotificationsActions,
  }),
  rTracking(() => window.rzpQ.component('OffersDetails')),
  withI18Service,
  withSplitzService,
)(OffersDetails);
