import { Component, Fragment } from 'react';
import { Field, reduxForm } from 'redux-form';

import InputField from 'rzp/ui/Forms/InputField';
import TableSlider from 'rzp/ui/TableSlider';
import AsyncButton from 'react-async-button';

import { required } from 'rzp/utils/validators';
@reduxForm({
  form: 'createBatch',
})
export default class BatchCreateModal extends Component {
  static defaultProps = {
    ctaText: 'Create',
    pendingText: 'Creating...',
  };

  //shift input caret to the end
  moveCaretAtEnd(e) {
    var temp_value = e.target.value;
    e.target.value = '';
    e.target.value = temp_value;
  }

  render() {
    const {
      parsedEntries,
      onCreateBatch,
      handleSubmit,
      pendingText,
      children,
    } = this.props;
    let ctaText = this.props.ctaText;

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
                BATCH FILE NAME{' '}
                <i
                  class="i i-info-circle m-l"
                  title="Maximum filename length is 255 characters."
                />
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
                maxlength="255"
                onFocus={this.moveCaretAtEnd}
              />
            </div>

            {/* extra fields sent with create batch */}
            {children}

            <AsyncButton
              type="button"
              class="btn btn-primary"
              text={ctaText}
              pendingText={pendingText}
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
