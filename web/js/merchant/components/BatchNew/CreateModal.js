import { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';

import InputField from 'rzp/ui/Forms/InputField';
import TableSlider from 'rzp/ui/TableSlider';
import AsyncButton from 'react-async-button';

import { titleCase } from 'rzp/utils/rzp-utils';
import { required } from 'rzp/utils/validators';
import { email } from '../../../rzp/utils/validators';

const selector = formValueSelector('createBatch');

@connect(state => {
  return {
    sms_notify: selector(state, 'sms_notify'),
    email_notify: selector(state, 'email_notify'),
  };
}, null)
@reduxForm({
  form: 'createBatch',
})
export default class BatchCreateModal extends Component {
  generateCtaText = () => {
    let { sms_notify, email_notify } = this.props;
    let ctaText = '';

    if (sms_notify || email_notify) {
      ctaText = 'Create Batch & Send Payment Links';
    } else {
      ctaText = 'Create Batch';
    }
    return ctaText;
  };

  render() {
    const {
      closeModal,
      parsedEntries,
      batchType,
      onCreateBatch,
      handleSubmit,
    } = this.props;

    let ctaText = this.generateCtaText();
    return (
      <div class="modal-body">
        <p>This is how we are interpreting your data.</p>
        <TableSlider
          title="Batch Entries"
          className="table-bordered batch-table"
          columns={getTableColumns(parsedEntries[0])}
          rows={parsedEntries}
          limit={3}
          slideUnit={200}
        />
        <div class="modal-info stretch create">
          <form onSubmit={handleSubmit(onCreateBatch)}>
            <h5 class="file-name-head">
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
            <h5 class="send-link-head">
              <strong>SEND {titleCase(batchType)}S</strong>
            </h5>
            <div class="form-group send-links-form">
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
            <p>
              <i class="i i-info-circle m-r" />
              Payment Links with SMS and Email will be sent once the batch is
              created.
            </p>

            <AsyncButton
              type="button"
              class="btn btn-primary"
              text={ctaText}
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
