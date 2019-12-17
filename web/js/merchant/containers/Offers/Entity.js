import React, { Component, Fragment } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import * as OffersActions from 'merchant/reducers/offers/offersList';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';
import RTracking from 'react-tracking';

const OfferDetails = props => {
  let { user, offer, isLoading, statusMsg } = props;

  let discountType = offer.percent_rate !== null ? 'Percentage' : 'Flat';
  let discountWorth =
    offer.percent_rate !== null ? (
      <span>{offer.percent_rate / 100}%</span>
    ) : (
      <Amount value={offer.flat_cashback} cureency={'INR'} />
    );

  const ISSUERS = {
    HDFC: 'HDFC Bank',
    HSBC: 'HSBC Bank',
    ICIC: 'ICICI Bank',
    INDB: 'INDUSIND Bank',
    KKBK: 'Kotak Mahindra Bank',
    RATN: 'Ratnakar Bank Bank',
    SCBL: 'Standard Chartered Bank',
    UTIB: 'Axis Bank',
    YESB: 'Yes Bank',
    CITI: 'Citi Bank',
    SBIN: 'State Bank of India',
    BARB: 'Bank of Baroda Bank',
    paytm: 'Paytm',
    payzapp: 'PAYZAPP',
    mobikwik: 'MOBIKWIK',
    payumoney: 'PayU Money',
    olamoney: 'OLA Money',
    airtelmoney: 'Airtel Money',
    amazonpay: 'Amazon Pay',
    freecharge: 'Freecharge',
    jiomoney: 'JIO Money',
    sbibuddy: 'SBI buddy',
    openwallet: 'OPEN WALLET',
    mpesa: 'M PESA',
    phonepe: 'Phone Pe',
    paypal: 'Paypal',
  };

  const renderPaymentDetails = () => {
    let paymentMethod = offer.payment_method || '--';
    let iins = (offer.iins && offer.iins.join(', ')) || '--';
    if (paymentMethod == 'card') {
      paymentMethod =
        {
          credit: 'Credit Card',
          debit: 'Debit Card',
        }[offer.payment_method_type] || 'Both Credit and Debit Cards';
    }

    return (
      <React.Fragment>
        <EntityDetailRow label="Method" value={paymentMethod} />
        {offer.payment_method == 'card' ? (
          <React.Fragment>
            <EntityDetailRow label="IINs" value={iins} />
            <EntityDetailRow
              label="Network"
              value={offer.payment_network || '--'}
            />
          </React.Fragment>
        ) : null}
        <EntityDetailRow
          label="Bank Name"
          value={ISSUERS[offer.issuer] || '--'}
        />
      </React.Fragment>
    );
  };
  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            <i class="i i-link text-primary icon--formal" />{' '}
            <strong>{offer.id}</strong>
            <div class="btn-toolbar pull-right" />
          </div>

          <div class="SliderPanel__Body">
            <div class="panel-body">
              <div class="list-group details-row-container">
                <EntityDetailRow
                  label="Created At"
                  value={() => (
                    <Time
                      format="DD MMM YYYY, hh:mm a"
                      value={offer.starts_at || '--'}
                    />
                  )}
                />
                <EntityDetailRow
                  label="Status"
                  value={() => (
                    <Fragment>
                      {offer.active ? 'Enabled' : 'Disabled'}
                      <Button.Transparent
                        class="Button--Link"
                        style={{ marginLeft: 12 }}
                        onClick={props.onActivationToggle}
                      >
                        {offer.active ? 'Disable' : 'Enable'}
                      </Button.Transparent>
                    </Fragment>
                  )}
                />

                <EntityDetailRow
                  label="Offer Name"
                  value={offer.name || '--'}
                />
                <EntityDetailRow
                  label="Display Text"
                  value={offer.display_text || '--'}
                />
                <EntityDetailRow label="Terms" value={offer.terms || '--'} />

                <EntityDetailRow
                  label="Offer Usage"
                  value={offer.current_offer_usage || '--'}
                />
                <EntityDetailRow
                  label="On Offer Failure"
                  value={offer.block ? 'Block Payment' : 'Allow Payment'}
                />
                <EntityDetailRow
                  label="Min Payment"
                  children={
                    <Amount value={offer.min_amount} currency={'INR'} /> || '--'
                  }
                />
                <EntityDetailRow
                  label="Start of Offer"
                  value={() => (
                    <Time
                      format="DD MMM YYYY, hh:mm a"
                      value={offer.starts_at}
                    />
                  )}
                />
                <EntityDetailRow
                  label="Expiry of Offer"
                  value={() => (
                    <Time format="DD MMM YYYY, hh:mm a" value={offer.ends_at} />
                  )}
                />

                <EntityDetailRow label="Discount Type" value={discountType} />
                <EntityDetailRow
                  label="Discount Worth"
                  children={discountWorth}
                />
                {discountType == 'Percentage' ? (
                  <EntityDetailRow
                    label="Max Cashback"
                    children={
                      <Amount value={offer.max_cashback} currency={'INR'} /> ||
                      '--'
                    }
                  />
                ) : null}

                <EntityDetailRow
                  label="Maximum Usage"
                  value={offer.max_offer_usage || '--'}
                />
                {renderPaymentDetails()}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

@connect(state => ({ ...state.offer, ...state.session }), {
  ...OffersActions,
  ...ModalActions,
  ...NotificationsActions,
})
@RTracking(() => window.rzpQ.component('Entity'))
export default class Entity extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor(props) {
    super(...arguments);
    this.state = {
      statusMsg: {},
    };
  }

  componentWillMount() {
    this.props.fetchOffer(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchOffer(nextProps.id);
    }
  }

  toggleActivation = () => {
    let offer = this.props.offer;
    const actionName = offer.active ? 'Disable' : 'Enable';
    let header = `${actionName} Offer`;
    const tracking = this.props.tracking;
    //analytics
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('Offer_edit', {
        edited_field: ['active'],
      })
    );
    this.context.confirm({
      header,
      message: () => (
        <div class="text-semi-muted">
          <p>The offer will be {actionName}d</p>
        </div>
      ),
      affirmativeLabel: `Yes, ${actionName}`,
      affirmativePendingLabel: 'Requesting...',
      abortLabel: "No, don't!",
      action: () => {
        let activationValue = offer.active ? 0 : 1;
        offer.active = activationValue;
        return offer
          .save()
          .then(offer => {
            this.props.fetchOffer(offer.id);
            this.props.updateOfferInReduxList(offer);
            //analytics code here
            tracking.trackEvent(
              window.rzpQ.merchantActions().success('Offer_edit', {
                edited_field: ['active'],
                field_value: [activationValue],
              })
            );
            this.props.showNotification({
              type: 'success',
              message: `Offer ${actionName}d!`,
            });
          })
          .catch(({ errors }) => {
            if (
              !errors ||
              (errors instanceof Array === true &&
                (!errors.length || !errors[0]))
            ) {
              errors = 'Some network error has occurred';
            }

            this.props.showNotification({
              type: 'error',
              message: errors,
            });
          });
      },
      onMount: () => {
        //analytics code here
      },
      abort: () => {
        //analytics code here
      },
    });
  };

  render() {
    let { loading, offer, user } = this.props;
    let statusMsg = this.state.statusMsg;

    return (
      <OfferDetails
        user={user}
        offer={offer}
        isLoading={loading}
        statusMsg={statusMsg}
        onIssue={() => {}}
        onActivationToggle={this.toggleActivation}
        editPaymentLink={() => {}}
        isRoleAllowedEdit={user.isAllowedEdit('offers')}
      />
    );
  }
}
