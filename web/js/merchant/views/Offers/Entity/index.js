import React from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import PropTypes from 'prop-types';

import * as OffersActions from 'merchant/reducers/offers/offersList';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import Banner from 'common/ui/Banner';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Amount from 'common/ui/Amount';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { OfferStatusLabel } from 'merchant/components/StatusLabel';

import { PAYMENT_NETWORK_MAP, OFFER_TYPE_LABELS, ISSUERS } from '../constants';
import SubscriptionUsageDetails from './SubscriptionUsageDetails';
import { emiDurationString } from 'merchant/views/Offers/New/helpers';

@connect((state) => ({ ...state.offer, user: state.session.user }), {
  ...OffersActions,
  ...ModalActions,
  ...NotificationsActions,
})
@RTracking(() => window.rzpQ.component('OffersDetails'))
export default class OffersDetails extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = {
    prevID: this.props.id,
    fetchOfferID: this.props.fetchOffer,
  };

  get isSubscriptionOffer() {
    const { offer, user } = this.props;
    return offer?.product_type === 'subscription' && user?.isSubscriptionOffersEnabled;
  }

  componentDidMount() {
    const { fetchOffer, id } = this.props;
    fetchOffer(id);
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

  toggleActivation = () => {
    const { offer, tracking, fetchOffer, updateOfferInReduxList, showNotification } = this.props;
    const { id, active, current_offer_usage } = offer;
    const actionName = active ? 'Disable' : 'Enable';

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

  render() {
    const { loading, offer } = this.props;
    const {
      id,
      name,
      display_text,
      percent_rate,
      payment_method,
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
    } = offer;

    const discountType = percent_rate !== null ? 'Percentage' : 'Flat';
    const isPercentageDiscount = discountType === 'Percentage';
    const isCardPayment = payment_method == 'card';
    const iins = (offerIINs && offerIINs.join(', ')) || '--';

    let paymentMethod = payment_method || '--';
    if (paymentMethod === 'card') {
      paymentMethod = 'Debit Card';
      if (payment_method_type == 'credit') {
        paymentMethod = 'Credit Card';
      }
      if (payment_method_type === null) {
        paymentMethod = 'Both Credit and Debit cards';
      }
    }

    return (
      <div className="Offers--Details content-wrapper content-sm txn-details">
        {loading ? (
          <div className="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div className="panel panel-default SliderPanel">
            <div className="panel-heading">
              <i className="i i-link text-primary icon--formal" /> <strong>{id}</strong>
            </div>

            {this.isSubscriptionOffer && (
              <>
                <Banner class="download-report">
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
                      class="Button--Link"
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

                  <EntityDetailRow
                    label="Checkout Visibility"
                    value={default_offer ? 'Yes' : 'No'}
                  />

                  <EntityDetailRow label="Min Payment">
                    <Amount value={min_amount} currency="INR" />
                  </EntityDetailRow>

                  <EntityDetailRow label="Start of Offer">
                    <Time format="DD MMM YYYY, hh:mm a" value={starts_at} />
                  </EntityDetailRow>

                  <EntityDetailRow label="Expiry of Offer">
                    <Time format="DD MMM YYYY, hh:mm a" value={ends_at} />
                  </EntityDetailRow>

                  <EntityDetailRow label="Discount Type" value={discountType} />

                  <EntityDetailRow label="Discount Worth">
                    {percent_rate !== null ? (
                      <span>{percent_rate / 100}%</span>
                    ) : (
                      <Amount value={flat_cashback} currency="INR" />
                    )}
                  </EntityDetailRow>

                  {isPercentageDiscount && (
                    <EntityDetailRow label="Max Cashback">
                      <Amount value={max_cashback} currency="INR" />
                    </EntityDetailRow>
                  )}

                  <EntityDetailRow label="Maximum Usage" value={max_offer_usage} />

                  <EntityDetailRow label="Method" value={paymentMethod} />

                  {paymentMethod === 'emi' && (
                    <EntityDetailRow
                      label="Method Type"
                      value={payment_method_type === 'debit' ? 'Debit Card' : 'Credit Card'}
                    />
                  )}

                  {isCardPayment && (
                    <>
                      <EntityDetailRow label="IINs" value={iins} />
                      <EntityDetailRow
                        label="Network"
                        value={PAYMENT_NETWORK_MAP[payment_network]}
                      />
                    </>
                  )}

                  <EntityDetailRow label="Bank Name" value={ISSUERS[issuer]} />

                  <EntityDetailRow label="Offer Type" value={OFFER_TYPE_LABELS[type]} />

                  {emi_subvention && (
                    <EntityDetailRow
                      label="Emi Durations"
                      value={emiDurationString(emi_durations)}
                    />
                  )}

                  {this.isSubscriptionOffer && (
                    <>
                      <EntityDetailRow label="Redemption Type" value={redemption_type} />
                      <EntityDetailRow label="Number of Cycles" value={no_of_cycles} />
                    </>
                  )}
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }
}
