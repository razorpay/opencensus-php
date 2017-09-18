import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import Alert from 'rzp/ui/Forms/Alert';
import ModalHeader from 'rzp/ui/ModalHeader';
import { required } from 'rzp/utils/validators';
import { saveAddOn } from 'merchant/modules/addons';
import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

@connect(null, {
  saveAddOn,
  showNotification,
  ...ModalActions,
})
@reduxForm({
  form: 'newAddOn',
})
export default class CreateAddOn extends Component {
  state = {};

  componentWillMount() {
    let { addon, subscriptionId } = this.props;

    let initProps = {
      addon,
      subscription_id: subscriptionId,
      item: { currency: 'INR' },
    };

    this.props.initialize(initProps);
  }

  handleSubmit = props => {
    return this.props
      .saveAddOn(props)
      .then(response => {
        this.props.showNotification({
          type: 'success',
          message: 'Add-on details successfully created',
        });
        this.props.onSave(response.data.id); // For highlighting the row
      })
      .catch(err => {
        this.setState({
          errors: err.errors,
        });
      });
  };

  render() {
    const { handleSubmit, invalid, addon } = this.props;

    return (
      <div class="addon-create">
        <ModalHeader
          title={addon && addon.id ? 'Edit Add-on' : 'Create Add-on'}
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body">
          <Alert type="error" message={this.state.errors} />

          <form onSubmit={handleSubmit(this.handleSubmit)}>
            <div class="form-group">
              <label class="colcontrol-label label-required">
                Subscription Id
              </label>
              <Field
                name="subscription_id"
                placeholder="sub_8cR2a11NVALA1s"
                component={InputField}
                class="form-control"
                autoFocus={true}
                validate={required()}
              />
            </div>

            <div class="form-group">
              <label class="colcontrol-label label-required">Name</label>
              <Field
                name="item[name]"
                component={InputField}
                class="form-control"
                validate={required()}
              />
            </div>

            <div class="form-group">
              <label class="control-label">Description</label>
              <Field
                name="item[description]"
                placeholder="Enter optional  description here"
                component="textarea"
                class="form-control"
              />
            </div>

            <div class="form-group">
              <div style={{ display: 'inline-block', width: '62%' }}>
                <label class="control-label label-required">
                  Price per unit (in ₹)
                </label>
                <Field
                  name="item[amount]"
                  placeholder="0.00"
                  component={InputField}
                  class="form-control"
                  validate={required()}
                />
              </div>
              <div
                style={{
                  display: 'inline-block',
                  width: '28%',
                  float: 'right',
                }}
              >
                <label class="control-label label-required">No. of Units</label>
                <Field
                  name="quantity"
                  component={InputField}
                  class="form-control"
                  validate={required()}
                />
              </div>
            </div>

            <div class="Modal__actions clearfix" style={{ paddingTop: '16px' }}>
              <AsyncButton
                type="submit"
                class="btn btn-primary col-lg-12"
                text="Create and Include"
                disabled={invalid}
                pendingText="Creating..."
                onClick={handleSubmit(this.handleSubmit)}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}
