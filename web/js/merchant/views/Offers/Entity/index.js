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

import { deepClone } from 'razorx/helpers/utils';
import { PAYMENT_NETWORK_MAP, OFFER_TYPE_LABELS, ISSUERS } from '../constants';
import SubscriptionUsageDetails from './SubscriptionUsageDetails';

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

  get isSubscriptionOffer() {
    return (
      this.props.offer.product_type === 'subscription' &&
      this.props.user.isSubscriptionOffersEnabled
    );
  }

  UNSAFE_componentWillMount() {
    this.props.fetchOffer(this.props.id);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchOffer(nextProps.id);
    }
  }

  toggleActivation = () => {
    const { offer, tracking } = this.props;
    const actionName = offer.active ? 'Disable' : 'Enable';

    // analytics
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('Offer_edit', {
        edited_field: ['active'],
      }),
    );

    let message = (
      <div class="text-semi-muted">
        <p>The offer will be {actionName}d</p>
      </div>
    );

    if (this.isSubscriptionOffer) {
      message = (
        <div class="disable-offer-alert">
          <div class="heading">
            This Offer is active on {offer.current_offer_usage || 0} subscriptions!
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
        let activationValue = offer.active ? 0 : 1;
        offer.active = activationValue;

        return offer
          .save()
          .then((offer) => {
            this.props.showNotification({
              type: 'success',
              message: `Offer ${actionName}d!`,
            });

            this.props.fetchOffer(offer.id);
            this.props.updateOfferInReduxList(offer);

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

            this.props.showNotification({
              type: 'error',
              message: errors,
            });
          });
      },
    });
  };

  render() {
    const { loading, offer } = this.props;

    const discountType = offer.percent_rate !== null ? 'Percentage' : 'Flat';
    const isPercentageDiscount = discountType === 'Percentage';
    const isCardPayment = offer.payment_method == 'card';
    const iins = (offer.iins && offer.iins.join(', ')) || '--';

    let paymentMethod = offer.payment_method || '--';
    if (paymentMethod == 'card') {
      paymentMethod = 'Debit Card';
      if (offer.payment_method_type == 'credit') {
        paymentMethod = 'Credit Card';
      }
      if (offer.payment_method_type === null) {
        paymentMethod = 'Both Credit and Debit cards';
      }
    }

    return (
      <div class="Offers--Details content-wrapper content-sm txn-details">
        {loading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              <i class="i i-link text-primary icon--formal" /> <strong>{offer.id}</strong>
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

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <div class="list-group details-row-container">
                  <EntityDetailRow label="Created At">
                    <Time format="DD MMM YYYY, hh:mm a" value={offer.starts_at} />
                  </EntityDetailRow>

                  <EntityDetailRow label="Status">
                    <OfferStatusLabel status={offer.active ? 'enabled' : 'disabled'} />
                    <Button.Transparent
                      class="Button--Link"
                      style={{ marginLeft: 12 }}
                      onClick={this.toggleActivation}
                    >
                      {offer.active ? 'Disable' : 'Enable'}
                    </Button.Transparent>
                  </EntityDetailRow>

                  <EntityDetailRow label="Offer Name" value={offer.name} />

                  <EntityDetailRow label="Display Text" value={offer.display_text} />

                  <EntityDetailRow label="Terms" value={offer.terms} />

                  {/* TODO: Check this logic with BE */}
                  {offer.product_type && (
                    <EntityDetailRow label="Promotion Type" value={offer.product_type} />
                  )}

                  <EntityDetailRow label="Offer Usage" value={offer.current_offer_usage} />

                  <EntityDetailRow
                    label="On Offer Failure"
                    value={offer.block ? 'Block Payment' : 'Allow Payment'}
                  />

                  <EntityDetailRow
                    label="Checkout Visibility"
                    value={offer.default_offer ? 'Yes' : 'No'}
                  />

                  <EntityDetailRow label="Min Payment">
                    <Amount value={offer.min_amount} currency={'INR'} />
                  </EntityDetailRow>

                  <EntityDetailRow label="Start of Offer">
                    <Time format="DD MMM YYYY, hh:mm a" value={offer.starts_at} />
                  </EntityDetailRow>

                  <EntityDetailRow label="Expiry of Offer">
                    <Time format="DD MMM YYYY, hh:mm a" value={offer.ends_at} />
                  </EntityDetailRow>

                  <EntityDetailRow label="Discount Type" value={discountType} />

                  <EntityDetailRow label="Discount Worth">
                    {offer.percent_rate !== null ? (
                      <span>{offer.percent_rate / 100}%</span>
                    ) : (
                      <Amount value={offer.flat_cashback} currency={'INR'} />
                    )}
                  </EntityDetailRow>

                  {isPercentageDiscount && (
                    <EntityDetailRow label="Max Cashback">
                      <Amount value={offer.max_cashback} currency={'INR'} />
                    </EntityDetailRow>
                  )}

                  <EntityDetailRow label="Maximum Usage" value={offer.max_offer_usage} />

                  <EntityDetailRow label="Method" value={paymentMethod} />

                  {isCardPayment && (
                    <React.Fragment>
                      <EntityDetailRow label="IINs" value={iins} />
                      <EntityDetailRow
                        label="Network"
                        value={PAYMENT_NETWORK_MAP[offer.payment_network]}
                      />
                    </React.Fragment>
                  )}

                  <EntityDetailRow label="Bank Name" value={ISSUERS[offer.issuer]} />

                  <EntityDetailRow label="Offer Type" value={OFFER_TYPE_LABELS[offer.type]} />

                  {offer.emi_subvention && (
                    <EntityDetailRow
                      label="Emi Durations"
                      value={emiDurationString(offer.emi_durations)}
                    />
                  )}

                  {this.isSubscriptionOffer && (
                    <>
                      <EntityDetailRow label="Redemption Type" value={offer.redemption_type} />
                      <EntityDetailRow label="Number of Cycles" value={offer.no_of_cycles} />
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

// TODO: move this to helpers
export function emiDurationString(emiDurations) {
  let durations = deepClone(emiDurations);

  let lastDurationString = ' months';
  if (durations.length > 1) {
    lastDurationString = ` and ${durations.pop()}${lastDurationString}`;
  }

  return durations.join(', ') + lastDurationString;
}
