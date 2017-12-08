import { Component } from 'react';

import { openModal, closeModal, confirm } from 'common/modal';
import { adminFetch } from 'util/fetch';
import { notifyError, notifySuccess } from 'common/modal';

import ShowWhen from 'admin/components/ShowWhen';
import AsyncButton from 'ui/AsyncButton';
import BaseModal from 'ui/BaseModal';
import Form from 'ui/Form';
import Field, { SelectField, DateField, CheckField } from 'ui/Field';

// Dispute Actions
export default ({ entity, mode, updateEntity }) => {
  function editDispute(body) {
    // Edit Dispute
    return fetch({
      url: '/admin/generic',
      method: 'patch',
      params: {
        route_name: 'dispute_edit',
        url_params: {
          id: entity.id,
        },
        mode: mode,
      },
      data: {
        body,
      },
    })
      .then(data => {
        if (data) {
          notifySuccess('Dispute is successfully updated');
          updateEntity(data);
          closeModal();

          if (
            typeof data.id !== 'undefined' &&
            data.id.indexOf('w_action') === 0 &&
            typeof data.workflow_id !== 'undefined'
          ) {
            setTimeout(() => window.open(`/admin/requests/${data.id}`), 1000);
          }
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err));
      });
  }

  function openDisputeModal() {
    openModal(
      <DisputeForm
        entity={entity}
        handleSubmit={editDispute}
        isEditMode={mode}
      />
    );
  }

  return (
    <ShowWhen permission="edit_dispute">
      <button
        class="btn btn-default label-pending"
        disabled={['won', 'lost'].indexOf(entity.status) !== -1}
        onClick={openDisputeModal}
      >
        Edit Dispute
      </button>
    </ShowWhen>
  );
};

// Dispute Form
export class DisputeForm extends Component {
  state = {};

  componentWillMount() {
    adminFetch({
      route_name: 'admin_fetch_entity_multiple',
      url_params: {
        type: 'dispute_reason',
      },
      mode: this.props.mode,
    })
      .then(data => {
        this.setState({ reasonIds: data.items });
      })
      .catch(err => notifyError(JSON.stringify(err)));
  }

  cleanFields(body) {
    body.raised_on =
      new Date(moment(body.raised_on, 'DD/MM/YYYY')).getTime() / 1000;
    body.expires_on =
      new Date(moment(body.expires_on, 'DD/MM/YYYY')).getTime() / 1000;
    body.amount = body.amount;
    body.deduct_at_onset = body.deduct_at_onset ? 1 : 0;
    body.skip_email = body.skip_email ? 1 : 0;
    if (body.merchant_emails) {
      body.merchant_emails = body.merchant_emails
        .replace(/,*$/, '')
        .split(',')
        .map(function(item) {
          return item.trim();
        });
    }

    // Pruning as Edit mode needs only 3 fields in req payload
    if (this.props.isEditMode) {
      var editModeDisputeFields = {};
      if (body.expires_on) {
        editModeDisputeFields.expires_on = body.expires_on;
      }
      if (body.gateway_dispute_status) {
        editModeDisputeFields.gateway_dispute_status =
          body.gateway_dispute_status;
      }
      if (body.status) {
        editModeDisputeFields.status = body.status;
      }
      if (body.accepted_amount) {
        editModeDisputeFields.accepted_amount = body.accepted_amount;
      }

      body = editModeDisputeFields;
    }

    return body;
  }

  render() {
    const { isEditMode, handleSubmit, entity } = this.props;

    const raisedOn = entity.raised_on
      ? moment(new Date(entity.raised_on * 1000)).format('DD/MM/YYY')
      : null;
    const expiresOn = entity.expires_on
      ? moment(new Date(entity.expires_on * 1000)).format('DD/MM/YYY')
      : null;

    return (
      <BaseModal header={`${isEditMode ? 'Edit' : 'Create'} Dispute`}>
        <Form class="full-span full-elements">
          {/* Gate Dispute Id */}
          <Field
            label="Gateway Dispute Id"
            name="gateway_dispute_id"
            defaultValue={entity.gateway_dispute_id}
            required={!isEditMode}
            disabled={isEditMode}
          />

          {/* Gateway Dispute Status */}
          <Field
            label="Gateway Dispute Status"
            name="gateway_dispute_status"
            defaultValue={entity.gateway_dispute_status}
          />

          {/* Phase */}
          <SelectField
            label="Phase"
            name="phase"
            defaultValue={entity.phase}
            required={!isEditMode}
            disabled={isEditMode}
          >
            <option value="" disabled>
              Select..
            </option>
            <option value="chargeback">Chargeback</option>
            <option value="pre_arbitration">Pre Arbitration</option>
            <option value="arbitration">Arbitration</option>
            <option value="retrieval">Retrieval</option>
            <option value="fraud">Fraud</option>
          </SelectField>

          {/* Raised on Date */}
          {/* TODO: format the date before passing to default value*/}
          <DateField
            name="raised_on"
            label="Raised on"
            defaultValue={raisedOn}
            required={!isEditMode}
            disabled={isEditMode}
          />

          {/* Expires on Date */}
          <DateField
            name="expires_on"
            label="Expires on"
            defaultValue={expiresOn}
            required={!isEditMode}
          />

          {/* Status */}
          {isEditMode && (
            <SelectField
              label="Status"
              name="status"
              defaultValue={entity.status}
            >
              <option value="" disabled>
                Select..
              </option>
              <option value="open">Open</option>
              <option value="under_review">Under Review</option>
              <option value="lost">Lost</option>
              <option value="won">Won</option>
              <option value="closed">Closed</option>
            </SelectField>
          )}

          {/* Reason Id */}
          {this.state.reasonIds && (
            <SelectField
              label="Reason Id"
              name="reason_id"
              defaultValue={entity.reason_id}
              disabled={isEditMode}
              required={!isEditMode}
            >
              <option value="" disabled>
                Select a Reason Id
              </option>
              {this.state.reasonIds.map(reason => (
                <option key={reason.id} value={reason.id}>
                  {reason.network} - {reason.gateway_code} : {reason.code}
                </option>
              ))}
            </SelectField>
          )}

          {/* Amount */}
          <Field
            label="Amount (Paisa)"
            name="amount"
            defaultValue={entity.amount}
            required={!isEditMode}
            disabled={isEditMode}
          />

          {/* Accepted Amount */}
          {isEditMode && (
            <Field
              label="Accepted Amount (Paisa)"
              name="accepted_amount"
              defaultValue={entity.accepted_amount}
              required={!isEditMode}
              disabled={isEditMode}
            />
          )}

          {/* Merchant Email */}
          <Field
            label="Merchant Email"
            name="merchant_emails"
            defaultValue={entity.merchant_emails}
            disabled={isEditMode}
          />

          {/* Deduct on Onset */}
          <CheckField
            label="Deduct at Onset"
            name="deduct_at_onset"
            defaultChecked={entity.deduct_at_onset}
            disabled={isEditMode}
          />

          {/* Skip Merchant Email */}
          <CheckField
            label="Skip Merchant Email"
            name="skip_email"
            defaultChecked={entity.skip_email}
            disabled={isEditMode}
          />

          <AsyncButton
            text={isEditMode ? 'Update' : 'Create'}
            class="btn"
            pendingClass="small spinner"
            onSubmit={body => handleSubmit(this.cleanFields(body))}
          />
        </Form>
      </BaseModal>
    );
  }
}
