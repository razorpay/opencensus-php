import { Component, Fragment } from 'react';
import { openModal, closeModal, confirm } from 'common/modal';
import { adminFetch, adminPost } from 'util/fetch';
import { notifyError, notifySuccess, notifyDone } from 'common/modal';
import { getFields } from '../Entity';
import user from 'admin/user';

import AsyncButton from 'ui/AsyncButton';
import BaseModal from 'ui/BaseModal';
import Form from 'ui/Form';
import Field from 'ui/Field';
import Table from 'ui/Table';
import { DisputeForm } from './dispute';
import ToggleEntityRow from 'ui/ToggleEntityRow';
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
        updateEntity(data);
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
          //TODO: Update entity that dispute is created
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
    }).then(response => {
      if (response) {
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
        Payment Analytics
      </button>
      {entity.status === 'failed' &&
        !entity.verified && (
          <AsyncButton
            class="btn"
            confirm="Are you sure you want to Authorize this failed payment?"
            onClick={authorizePayment}
          >
            Authorize Pyament
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
          class="btn"
          confirm="Are you sure you want to Refund this authorized payment?"
          onClick={refundAuthorizedPayment}
        >
          Refund
        </AsyncButton>
      )}

      <AsyncButton
        onClick={verifyPayment}
        class="btn btn-default"
        pendingClass="btn btn-default btn-pending"
        confirm="Are you sure you want to Verify this payment?"
      >
        Verify Payment
        <span class="spin-btn" />
      </AsyncButton>

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
          pending={typeof this.state.data === 'undefined'}
          fields={this.fields()}
          model={this.state.data}
        />
      </BaseModal>
    );
  }
}

export class PaymentRefunds extends Component {
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
          s
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
      <ToggleEntityRow label="Refunds">
        <Table
          pending={typeof this.state.refunds === 'undefined'}
          fields={this.fields}
          items={this.state.refunds}
        />
      </ToggleEntityRow>
    );
  }
}
