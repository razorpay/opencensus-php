import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import * as OffersActions from 'merchant/reducers/offers/offerDetails';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { updatePLInReduxList } from 'merchant/reducers/invoices/list';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Amount from 'common/ui/Amount';

const OfferDetails = props => {
  let { user, offer, isLoading, statusMsg } = props;

  let discountType = offer.percent_rate !== null ? 'Percentage' : 'Flat';
  let discountWorth =
    offer.percent_rate !== null ? (
      <span>{offer.percent_rate / 100}%</span>
    ) : (
      <Amount value={offer.flat_cashback} cureency={'INR'} />
    );
  const renderPaymentMethod = () => {
    let paymentMethod = offer.payment_method;
    if (paymentMethod === null) {
      return '--';
    }
    if (paymentMethod == 'card') {
      if (offer.payment_method_type == 'credit') {
        return 'Credit Card';
      }
      return 'Debit Card';
    }
    return paymentMethod;
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
                  value={offer.active ? 'Active' : 'Inactive'}
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

                <EntityDetailRow label="Method" value={renderPaymentMethod} />
                <EntityDetailRow
                  label="Bank Name"
                  value={offer.issuer || '--'}
                />
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
  updatePLInReduxList,
})
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
        onCancel={() => {}}
        editPaymentLink={() => {}}
        isRoleAllowedEdit={user.isAllowedEdit('offers')}
      />
    );
  }
}
