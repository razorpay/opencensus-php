import React, { PureComponent, Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import RTracking from 'react-tracking';
import ModalHeader from 'common/ui/ModalHeader';
import { reduxForm, Field } from 'redux-form';
import { required } from 'common/utils/validators';
import InputField from 'common/ui/Forms/InputField';
import { updatePassword } from 'merchant/reducers/profile';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

@connect(null, { closeModal })
@reduxForm({
  form: 'AddFundsForm',
})
export default class AddFundsForm extends Component {
  addFunds = fieldProps => {
    this.props.closeModal();
    this.props.openCheckout(fieldProps);
  };

  render() {
    const { handleSubmit } = this.props;

    return (
      <form onSubmit={handleSubmit(this.addFunds)}>
        <ModalHeader title="Add Funds" onCloseClick={this.props.closeModal} />
        <div class="modal-body">
          <div class="form-group">
            <Field
              component={InputField}
              type="text"
              placeholder="Enter Description"
              name="description"
              class="form-control"
              validate={required()}
              autoFocus={true}
            />
          </div>
          <div class="form-group">
            <Field
              component={InputField}
              type="text"
              placeholder="Enter Amount(INR)"
              name="amountInINR"
              class="form-control"
              validate={required()}
            />
          </div>
          <div class="Modal__actions">
            <AsyncButton
              type="submit"
              class="btn btn-primary btn-block"
              text="Add Funds"
              pendingText="Adding..."
              onClick={handleSubmit(this.addFunds)}
            />
          </div>
        </div>
      </form>
    );
  }
}
