import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import Alert from 'rzp/ui/Forms/Alert';
import ModalHeader from 'rzp/ui/ModalHeader';
import { required } from 'rzp/utils/validators';
import * as ItemActions from 'merchant/modules/items';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';

@connect(null, {
  ...ItemActions,
  ...ModalActions,
  ...NotificationsActions,
})
@reduxForm({
  form: 'newItem',
})
export default class AddItem extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      errors: null,
    };
  }

  componentWillMount() {
    if (this.props.item) {
      this.props.initialize(this.props.item);
    }
  }

  componentDidMount() {
    this.props.onMount && this.props.onMount(this.props.item);
  }

  componentWillUnmount() {
    this.props.onUnmount && this.props.onUnmount(this.props.item);
  }

  save = props => {
    return this.props
      .saveItem(props)
      .then(item => {
        this.props.onSave && this.props.onSave(item, this.props.item);
        this.props.showNotification({
          type: 'success',
          message: 'Item saved successfully',
        });
      })
      .catch(err => {
        this.setState({
          errors: err.errors,
        });
      });
  };

  render() {
    const { handleSubmit, invalid, item } = this.props;

    return (
      <div>
        <ModalHeader
          title={item && item.id ? 'Edit Item' : 'New Item'}
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body">
          <Alert type="error" message={this.state.errors} />

          <form onSubmit={handleSubmit(this.save)}>
            <div class="form-group">
              <label class="label-required">Name</label>
              <div>
                <Field
                  name="name"
                  component={InputField}
                  class="form-control"
                  autoFocus={true}
                  validate={required()}
                />
              </div>
            </div>

            <div class="form-group">
              <label class="label-required">Rate</label>
              <div>
                <div class="input-group">
                  <span class="input-group-addon">INR</span>
                  <Field
                    name="amountInINR"
                    component={InputField}
                    class="form-control"
                    validate={required()}
                  />
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>Description</label>
              <div>
                <Field
                  name="description"
                  component="textarea"
                  class="form-control"
                />
              </div>
            </div>

            <div class="Modal__actions">
              <AsyncButton
                type="submit"
                class="btn btn-primary btn-block"
                text={this.props.saveLabel}
                pendingText="Saving..."
                disabled={invalid}
                onClick={handleSubmit(this.save)}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}

AddItem.defaultProps = {
  onSave: () => {},
  saveLabel: 'Save',
};
