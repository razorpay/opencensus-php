import { openModal, closeModal, confirm } from 'common/modal';
import { adminPost, adminPut } from 'common/fetch';
import { notifyError, notifySuccess } from 'common/modal';
import { ModalContent } from 'component/Modal';
import Form from 'ui/Form';
import Field, { SelectField } from 'ui/Field';

import ShowWhen from 'admin/components/ShowWhen';
import AsyncButton from 'ui/AsyncButton';

// refund Actions
export default ({ entity, mode, updateEntity }) => {
  function retryRefund(body) {
    return adminPost(`${mode}/refunds/${entity.id}/retry`)
      .then(data => {
        if (data) {
          notifySuccess('Refund is successful');
          setTimeout(() => window.location.reload(), 1500);
        }
      })
      .catch(err => {
        notifyError(
          'There was an error while retrying to refund. ' + JSON.stringify(err)
        );
      });
  }

  function updateRefund(body) {
    let payload = {
      data: {
        status: body.status || entity.status,
        reference1: body.reference1,
      },
      url: `${mode}/refunds/${entity.id}/status`,
    };

    return adminPut(payload)
      .then(data => {
        if (data) {
          notifySuccess('Refund is successfully updated.');
          entity.status = data.status;
          entity.reference1 = body.reference1;
          updateEntity(entity);
          closeModal();
        }
      })
      .catch(err => {
        notifyError(
          'There was an error while updating to refund. ' + JSON.stringify(err)
        );
      });
  }

  function openEditRefund() {
    openModal(<EditRefundForm entity={entity} handleSubmit={updateRefund} />);
  }

  return (
    <div class="refund-actions">
      <ShowWhen permission="retry_refund_failed">
        {entity.status === 'failed' && (
          <AsyncButton
            class="btn btn-default text-primary"
            pendingClass="btn btn-default text-primary btn-pending"
            confirm="Are you sure you want retry this refund?"
            onClick={retryRefund}
          >
            Retry Refund
            <span class="spin-btn" />
          </AsyncButton>
        )}
      </ShowWhen>

      <ShowWhen permission="edit_refund">
        {entity.status !== 'processed' && (
          <button class="btn" onClick={openEditRefund}>
            Edit
          </button>
        )}
      </ShowWhen>
    </div>
  );
};

const EditRefundForm = ({ entity, handleSubmit }) => {
  let statusOptions = [<option value="processed"> Processed </option>];

  if (entity.status === 'created') {
    statusOptions.push(<option value="failed"> Failed </option>);
  }

  return (
    <ModalContent header={`Edit Refund - ${entity.id}`}>
      <Form class="full-span" onSubmit={handleSubmit}>
        <SelectField label="Status" name="status">
          <option value=""> Select status </option>
          {statusOptions}
        </SelectField>
        <Field
          label="Reference1"
          name="reference1"
          defaultValue={entity.reference1}
        />
        <button type="submit" class="btn">
          Save
        </button>
      </Form>
    </ModalContent>
  );
};
