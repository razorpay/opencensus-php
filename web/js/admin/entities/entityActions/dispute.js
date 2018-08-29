import { Component, Fragment } from 'react';

import { openModal, closeModal, confirm } from 'common/modal';
import fetch, { adminFetch } from 'common/fetch';
import { notifyError, notifySuccess } from 'common/modal';

import ShowWhen from 'admin/components/ShowWhen';
import AsyncButton from 'ui/AsyncButton';
import { ModalContent } from 'component/Modal';
import Form from 'ui/Form';
import Field, { SelectField, DateField, CheckField } from 'ui/Field';
import Table from 'ui/Table';

// Dispute Actions
export default ({ entity, mode, updateEntity }) => {
  function editDispute(body) {
    // Edit Dispute
    return fetch({
      url: `/admin/api/${mode}_${entity.merchant_id}/disputes/${entity.id}`,
      method: 'post',
      data: body,
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
        isEditMode={true}
        mode={mode}
      />
    );
  }

  function openDisputeFiles() {
    openModal(<DisputeFiles mode={mode} {...entity} />);
  }

  return (
    <Fragment>
      <ShowWhen permission="edit_dispute">
        <button
          class="btn btn-default label-pending"
          disabled={['won', 'lost'].indexOf(entity.status) !== -1}
          onClick={openDisputeModal}
        >
          Edit Dispute
        </button>
      </ShowWhen>
      <ShowWhen permission="fetch_dispute_files">
        <button class="btn btn-default" onClick={openDisputeFiles}>
          View Dispute Files
        </button>
      </ShowWhen>
    </Fragment>
  );
};

class DisputeFiles extends Component {
  state = {};

  componentWillMount() {
    adminFetch(
      `${this.props.mode}_${this.props.merchant_id}/disputes/${
        this.props.id
      }/files`
    ).then(data => {
      this.setState({ files: data.items });
    });
  }

  fields = [
    ['File Name', item => item.display_name],
    ['File Id', item => <code>{item.id}</code>],
    [
      'Download',
      item => (
        <DownloadFile
          mode={this.props.mode}
          merchantId={this.props.merchant_id}
          fileId={item.id}
        />
      ),
    ],
  ];

  render() {
    return (
      <ModalContent header="Dispute Files">
        <Table
          pending={!this.state.files}
          items={this.state.files}
          fields={this.fields}
        />
      </ModalContent>
    );
  }
}

class DownloadFile extends Component {
  state = { signedUrl: '' };

  componentWillMount() {
    adminFetch(
      `${this.props.mode}_${this.props.merchantId}/ufh/file/${
        this.props.fileId
      }/get-signed-url`
    ).then(data => {
      this.setState({
        signedUrl: data.signed_url,
      });
    });
  }

  render() {
    return !this.state.signedUrl ? (
      <span class="spin-btn visible large" />
    ) : (
      <a target="blank" href={this.state.signedUrl} class="btn btn-normal">
        View File
      </a>
    );
  }
}

// Dispute Form
export class DisputeForm extends Component {
  state = {};

  componentWillMount() {
    adminFetch(`${this.props.mode}/admin/dispute_reason`)
      .then(data => {
        this.setState({ reasonIds: data.items });
      })
      .catch(err => notifyError(JSON.stringify(err)));
  }

  cleanFields(body) {
    body.raised_on = moment(body.raised_on, 'DD/MM/YYYY').unix();
    body.expires_on = moment(body.expires_on, 'DD/MM/YYYY').unix();
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

    const raisedOn =
      isEditMode && entity.raised_on ? moment.unix(entity.raised_on) : '';
    const expiresOn =
      isEditMode && entity.expires_on ? moment.unix(entity.expires_on) : '';
    return (
      <ModalContent header={`${isEditMode ? 'Edit' : 'Create'} Dispute`}>
        <Form class="full-span full-elements">
          {/* Gate Dispute Id */}
          <Field
            label="Gateway Dispute Id"
            name="gateway_dispute_id"
            defaultValue={isEditMode ? entity.gateway_dispute_id : ''}
            required={!isEditMode}
            disabled={isEditMode}
          />

          {/* Gateway Dispute Status */}
          <Field
            label="Gateway Dispute Status"
            name="gateway_dispute_status"
            defaultValue={isEditMode ? entity.gateway_dispute_status : ''}
          />

          {/* Phase */}
          <SelectField
            label="Phase"
            name="phase"
            defaultValue={isEditMode ? entity.phase : ''}
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
            fieldClass="dispute-form"
            defaultValue={raisedOn ? raisedOn : undefined}
            required={!isEditMode}
            disabled={isEditMode}
            allowToday={true}
          />

          {/* Expires on Date */}
          <DateField
            name="expires_on"
            label="Expires on"
            fieldClass="dispute-form"
            defaultValue={expiresOn ? expiresOn : undefined}
            required={!isEditMode}
            allowAllDates={true}
          />

          {/* Status */}
          {isEditMode && (
            <SelectField
              label="Status"
              name="status"
              defaultValue={isEditMode ? entity.status : ''}
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
              defaultValue={isEditMode ? entity.reason_id : ''}
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
            defaultValue={isEditMode ? entity.amount : ''}
            required={!isEditMode}
            disabled={isEditMode}
          />

          {/* Accepted Amount */}
          {isEditMode && (
            <Field
              label="Accepted Amount (Paisa)"
              name="accepted_amount"
              defaultValue={isEditMode ? entity.accepted_amount : ''}
              required={!isEditMode}
              disabled={isEditMode}
            />
          )}

          {/* Merchant Email */}
          <Field
            label="Merchant Email"
            name="merchant_emails"
            defaultValue={isEditMode ? entity.merchant_emails : ''}
            disabled={isEditMode}
          />

          {/* Deduct on Onset */}
          <CheckField
            label="Deduct at Onset"
            name="deduct_at_onset"
            defaultChecked={isEditMode ? entity.deduct_at_onset : ''}
            disabled={isEditMode}
          />

          {/* Skip Merchant Email */}
          <CheckField
            label="Skip Merchant Email"
            name="skip_email"
            defaultChecked={isEditMode ? entity.skip_email : ''}
            disabled={isEditMode}
          />

          <AsyncButton
            text={isEditMode ? 'Update' : 'Create'}
            class="btn"
            pendingClass="small spinner"
            onSubmit={body => handleSubmit(this.cleanFields(body))}
          />
        </Form>
      </ModalContent>
    );
  }
}
