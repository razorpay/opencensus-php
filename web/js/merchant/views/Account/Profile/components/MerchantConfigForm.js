import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import ModalHeader from 'common/ui/ModalHeader';
import { reduxForm, Field } from 'redux-form';
import { required } from 'common/utils/validators';
import InputField from 'common/ui/Forms/InputField';

import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

@connect(null, { closeModal, showNotification })
@reduxForm({
  form: 'updateMerchantConfigForm',
})
export default class MerchantConfigForm extends PureComponent {
  constructor(props) {
    super(props);

    this.props.initialize({
      [props.attribute]: props.value,
    });
  }

  resetValue = this.resetValue.bind(this);

  resetValue() {
    this.props.change(this.props.attribute, this.props.value);
  }

  render() {
    const { handleSubmit } = this.props;
    return (
      <form onSubmit={handleSubmit(this.props.updateMerchantConfig)}>
        <ModalHeader title={'Edit ' + this.props.label} onCloseClick={this.props.closeModal} />
        <div class="modal-body">
          <div class="form-group">
            <label class="label-required">{this.props.label}</label>
            <div class="pull-right">
              <button type="button" class="btn btn-link no-padding" onClick={this.resetValue}>
                Reset
              </button>
            </div>
            <Field
              label={this.props.label}
              component={InputField}
              placeholder={this.props.label}
              name={this.props.attribute}
              class="form-control"
              validate={required()}
              autoFocus={true}
            />
            <small class="help-block">{this.props.desc}</small>
          </div>

          <div class="Modal__actions">
            <AsyncButton
              type="submit"
              class="btn btn-primary btn-block"
              text="Update"
              pendingText="Updating..."
              onClick={handleSubmit(this.props.updateMerchantConfig)}
            />
          </div>
        </div>
      </form>
    );
  }
}
