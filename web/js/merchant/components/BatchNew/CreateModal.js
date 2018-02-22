import { Component, Fragment } from 'react';
import { Field, reduxForm } from 'redux-form';

import InputField from 'rzp/ui/Forms/InputField';
import TableSlider from 'rzp/ui/TableSlider';
import Table from 'rzp/ui/Table/Index';
import AsyncButton from 'react-async-button';

import { titleCase } from 'rzp/utils/rzp-utils';
import { required } from 'rzp/utils/validators';

@reduxForm({
  form: 'createBatch',
  initialValues: {
    sms_notify: 0,
    email_notify: 0,
  },
})
export default class BatchCreateModal extends Component {
  render() {
    const {
      closeModal,
      parsedEntries,
      batchType,
      onCreateBatch,
      handleSubmit,
    } = this.props;

    return (
      <div class="modal-body">
        <p>This is how we are interpreting your data.</p>
        <TableSlider
          title="Batch Entries"
          className="table-bordered batch-table"
          columns={getTableColumns(parsedEntries[0])}
          rows={parsedEntries}
          limit={3}
          tabWidth={30}
          slideUnit={100}
        />
        <div class="modal-info stretch create">
          <form onSubmit={handleSubmit(onCreateBatch)}>
            <h5>
              <strong>
                CREATE BATCH FILE NAME <i class="i i-info-circle m-l" />
              </strong>
            </h5>
            <div class="form-group">
              <Field
                name="name"
                key="field"
                component={InputField}
                class="form-control"
                autoFocus={true}
                validate={[required()]}
              />
            </div>
            <h5>
              <strong style={{ textTransform: 'uppercase' }}>
                SEND {titleCase(batchType)}S
              </strong>
            </h5>
            <div class="form-group">
              <div class="checkbox rzpCheckbox next m-r">
                <Field
                  name="sms_notify"
                  id="sms_notify"
                  component="input"
                  type="checkbox"
                />
                <label for="sms_notify" class="icon i-check">
                  Send SMS
                </label>
              </div>
              <div class="checkbox rzpCheckbox next m-r">
                <Field
                  name="email_notify"
                  id="email_notify"
                  component="input"
                  type="checkbox"
                />
                <label for="email_notify" class="icon i-check">
                  Send Email
                </label>
              </div>
            </div>
            <p class="m-t">
              <i class="i i-info-circle m-r" />
              Payment Links with SMS and Email will be sent once the batch is
              created.
            </p>

            <AsyncButton
              type="button"
              class="btn btn-primary"
              text="Create & Send Payment Links"
              pendingText="Creating..."
              onClick={handleSubmit(onCreateBatch)}
            />
          </form>
        </div>
      </div>
    );
  }
}

const getTableColumns = entries => {
  return Object.keys(entries).map(entry => ({
    title: entry,
    value: item => item[entry],
  }));
};
