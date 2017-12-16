import { Component, Fragment } from 'react';
import { openModal, closeModal, confirm } from 'common/modal';
import { adminFetch, adminPost } from 'util/fetch';
import { notifyError, notifySuccess, notifyDone } from 'common/modal';
import { getFields } from '../Entity';
import user from 'admin/user';

import Duplex from 'ui/Duplex';
import AsyncButton from 'ui/AsyncButton';
import BaseModal from 'ui/BaseModal';
import Form from 'ui/Form';
import Field, { CheckField, TextAreaField } from 'ui/Field';
import Table from 'ui/Table';
import { DisputeForm } from './dispute';
import { formatDate } from 'util/index';
import Amount from 'ui/Amount';

// Payment Actions
export default ({ entity, mode, updateEntity }) => {
  function verifyPayment() {
    return adminFetch({
      route_name: 'payment_verify',
      mode: mode,
      url_params: {
        id: entity.id,
      },
    }).then(data => {
      if (data) {
        notifyDone();
        updateEntity(data.payment);
      }
    });
  }

  function capturePayment() {
    return adminPost({
      route_name: 'payment_capture',
      mode: mode,
      url_params: {
        id: entity.id,
      },
      body: {
        amount: entity.amount,
        currency: entity.currency,
      },
      merchant_id: entity.merchant_id,
    }).then(data => {
      if (data) {
        notifyDone();
        updateEntity(data);
      }
    });
  }

  function refundAuthorizedPayment() {
    return adminPost({
      route_name: 'payment_authorize_refund',
      mode: mode,
      url_params: {
        id: entity.id,
      },
      merchant_id: entity.merchant_id,
    }).then(data => {
      if (data) {
        notifyDone();
        // data belongs to refund entity, not payment
        location.reload();
      }
    });
  }

  function refundPayment(body) {
    body.amount = parseInt(body.amount);

    const unrefundedAmount =
      parseInt(entity.amount) - parseInt(entity.amount_refunded);

    return adminPost({
      route_name: 'payment_refund',
      mode: mode,
      url_params: {
        id: entity.id,
      },
      merchant_id: entity.merchant_id,
      body,
    }).then(data => {
      if (data) {
        notifySuccess('Refund is successful');
        closeModal();

        setTimeout(() => window.location.reload(), 1000);
      }
    });
  }

  function createDispute(body) {
    adminPost({
      route_name: 'payment_disputes',
      url_params: {
        id: entity.id,
      },
      mode: mode,
      body,
    })
      .then(data => {
        if (data) {
          notifySuccess('Dispute is created');
          if (
            typeof data.id !== 'undefined' &&
            data.id.indexOf('w_action') === 0 &&
            typeof data.workflow_id !== 'undefined'
          ) {
            setTimeout(() => window.open(`/admin/requests/${data.id}`), 1000);
          } else {
            location.reload();
          }
        }
      })
      .catch(err => notifyError(JSON.stringify(err)));
  }

  function authorizePayment() {
    return adminPost({
      url_params: {
        id: entity.id,
      },
      mode: mode,
      route_name: 'payment_authorize_failed',
    }).then(data => {
      if (data) {
        updateEntity(data);
        notifySuccess('Payment Authorized Successfully.');
        closeModal();
      }
    });
  }

  return (
    <Fragment>
      <button
        class="btn btn-default text-primary"
        onClick={_ =>
          openModal(<PaymentAnalytics mode={mode} paymentId={entity.id} />)
        }
      >
        <i
          class="i-chart-bar text-success"
          style={{ marginRight: '6px', fontSize: '11px' }}
        />
        Payment Analytics
      </button>
      {entity.gateway != null &&
        entity.status === 'failed' &&
        entity.verified === 0 && (
          <AsyncButton
            class="btn"
            confirm="Are you sure you want to Authorize this failed payment?"
            onClick={authorizePayment}
          >
            Authorize Payment
          </AsyncButton>
        )}
      {entity.status === 'authorized' &&
        user.permissions &&
        user.permissions.indexOf('edit_payment_capture') !== -1 && (
          <AsyncButton
            class="btn"
            pendingClass="small spinner"
            confirm="Are you sure you want to Capture this payment?"
            onClick={capturePayment}
          >
            Capture
          </AsyncButton>
        )}
      {entity.status === 'authorized' && (
        <AsyncButton
          class="btn btn-default text-primary"
          confirm="Are you sure you want to Refund this authorized payment?"
          onClick={refundPayment}
        >
          Refund
        </AsyncButton>
      )}

      {entity.status === 'captured' &&
        entity.refund_status !== 'full' && (
          <AsyncButton
            class="btn btn-default text-primary"
            onClick={() =>
              openModal(
                <PaymentRefundModal
                  refundPayment={refundPayment}
                  maxRefundableAmount={entity.amount - entity.amount_refunded}
                  currency={entity.currency}
                />
              )
            }
          >
            Refund
          </AsyncButton>
        )}

      {entity.gateway != null && (
        <AsyncButton
          onClick={verifyPayment}
          class="btn btn-default text-primary"
          pendingClass="btn btn-default text-primary btn-pending"
          confirm="Are you sure you want to Verify this payment?"
        >
          Verify Payment
          <span class="spin-btn" />
        </AsyncButton>
      )}

      {!entity.disputed && (
        <button
          class="btn danger"
          onClick={_ =>
            openModal(
              <DisputeForm
                entity={entity}
                handleSubmit={createDispute}
                isEditMode={false}
              />
            )
          }
        >
          Create Dispute
        </button>
      )}
    </Fragment>
  );
};

// Edit Offer Form
const EditOfferForm = ({ entity, handleSubmit }) => {
  return (
    <BaseModal header="Edit Offer">
      <Form class="full-span full-elements">
        <Field label="Name" name="name" defaultValue={entity.name} />
        {['netbanking', 'wallet', 'upi'].indexOf(entity.payment_method) ===
          -1 && (
          <Field
            label="iins"
            name="iins"
            placeholder="Enter comma(,) separated values"
            defaultValue={entity.iins}
          />
        )}
        {entity.payment_method === 'card' && (
          <Field
            label="max Payment Count"
            name="max_payment_count"
            defaultValue={entity.max_payment_count}
          />
        )}
        <Field label="Name" name="name" defaultValue={entity.name} />

        <Field
          label="Linked Offer ids"
          name="linked_offer_ids"
          defaultValue={entity.linked_offer_ids}
        />
        <Field
          label="Display Text"
          name="display_text"
          defaultValue={entity.display_text}
        />
        <Field
          label="Error Message"
          name="error_message"
          defaultValue={entity.error_message}
        />
        <Field label="Terms" name="terms" defaultValue={entity.terms} />

        <AsyncButton
          text="Submit"
          class="btn"
          pendingClass="small spinner"
          onSubmit={handleSubmit}
        />
      </Form>
    </BaseModal>
  );
};

class PaymentAnalytics extends Component {
  state = {};
  fields = this::getFields;

  componentWillMount() {
    adminFetch({
      route_name: 'admin_fetch_entity_multiple',
      url_params: {
        type: 'payment_analytics',
      },
      mode: this.props.mode,
      query_params: {
        payment_id: this.props.paymentId,
      },
    }).then(data => {
      if (data) {
        this.setState({ data: data.items.length ? data.items[0] : {} });
      }
    });
  }

  render() {
    return (
      <BaseModal header="Payment Analytics">
        <Duplex
          pending={!this.state.data}
          fields={this.fields()}
          model={this.state.data}
          mode={this.props.mode}
        />
      </BaseModal>
    );
  }
}

class PaymentRefundModal extends Component {
  state = { amountToRefund: this.props.maxRefundableAmount, partial: false };

  handleAmountChange = e => {
    if (e.target.value < this.props.maxRefundableAmount) {
      this.setState({ partial: true });
    }
    if (e.target.value == this.props.maxRefundableAmount) {
      this.setState({ partial: false });
    }
    this.setState({ amountToRefund: e.target.value });
  };

  handleCheckboxChange = e => {
    if (this.state.amountToRefund === this.props.maxRefundableAmount) {
      return;
    }
    if (!e.target.checked) {
      this.setState({ amountToRefund: this.props.maxRefundableAmount });
    }
    this.setState({ partial: e.target.checked });
  };

  render() {
    const { refundPayment, maxRefundableAmount, currency } = this.props;

    return (
      <BaseModal header="Refund Payment">
        <Form class="full-span" style={{ width: '500px' }}>
          <Field
            label="Amount to Refund (Paisa)"
            name="amount"
            type="number"
            onChange={this.handleAmountChange}
            value={this.state.amountToRefund}
          />
          <CheckField
            label="Partial Refund"
            checked={this.state.partial}
            onChange={this.handleCheckboxChange}
            readOnly
          />
          <TextAreaField
            label="Comment"
            name="notes[admin_comment]"
            placeholder="Add a comment (visible to merchant)"
            style={{ width: '300px' }}
          />

          <AsyncButton
            class="btn"
            pendingClass="small spinner"
            disabled={
              this.state.amountToRefund > this.props.maxRefundableAmount
            }
            onSubmit={refundPayment}
          >
            Refund
          </AsyncButton>
        </Form>
      </BaseModal>
    );
  }
}

export class PaymentRefundsList extends Component {
  state = {};

  fields = [
    ['Amount', item => <Amount value={item.amount} />],
    [
      'Refund ID',
      item => (
        <a
          class="link"
          href={`/admin/entity/refund/${this.props.mode}/${item.id}`}
        >
          {item.id}
        </a>
      ),
    ],
    ['Created At', item => formatDate(item.created_at)],
  ];

  componentWillMount() {
    adminFetch({
      route_name: 'payment_fetch_refunds',
      merchant_id: this.props.merchant_id,
      mode: this.props.mode,
      url_params: {
        id: this.props.id,
      },
    }).then(response => {
      if (response) {
        this.setState({ refunds: response.items });
      }
    });
  }
  render() {
    return (
      <Table
        pending={typeof this.state.refunds === 'undefined'}
        fields={this.fields}
        items={this.state.refunds}
      />
    );
  }
}
