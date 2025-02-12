import { Modules } from 'common/constant/enums';
import InputField from 'common/ui/Forms/InputField';
import ModalHeader from 'common/ui/ModalHeader';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { required } from 'common/utils/validators';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import React, { PureComponent } from 'react';
import AsyncButton from 'react-async-button';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { Field, reduxForm } from 'redux-form';

class MerchantConfigForm extends PureComponent {
  constructor(props) {
    super(props);

    this.props.initialize({
      [props.attribute]: props.value,
    });

    this.resetValue = this.resetValue.bind(this);
    this.handleSubmit = this.handleSubmit.bind(this);
  }

  onAnalyticsTrack = (action) => {
    const { config_type } = this.props;
    analyticsTrackWithUserInfo({
      objectName: `${config_type ? config_type : 'display name'} edit popup`,
      actionName: 'clicked',
      screen: this.props.isNewAccountAndSettingsPage
        ? Modules.AccountAndSettings
        : Modules.MyAccount,
      properties: {
        action,
      },
    });
  };

  resetValue() {
    this.onAnalyticsTrack('reset');
    this.props.change(this.props.attribute, this.props.value);
  }

  handleSubmit(e) {
    this.onAnalyticsTrack('update');
    this.props.handleSubmit(this.props.updateMerchantConfig)(e);
  }

  render() {
    const { handleSubmit, validateConfig = [] } = this.props;
    return (
      <form
        onSubmit={(...a) => {
          this.onAnalyticsTrack('update');
          return handleSubmit(this.props.updateMerchantConfig)(...a);
        }}
      >
        <ModalHeader
          title={`Edit ${this.props.label}`}
          onCloseClick={(args) => {
            this.onAnalyticsTrack('cancel');
            this.props.closeModal(args);
          }}
        />
        <div className="modal-body">
          <div className="form-group">
            <label className="label-required">{this.props.label}</label>
            <div className="pull-right">
              <button type="button" className="btn btn-link no-padding" onClick={this.resetValue}>
                Reset
              </button>
            </div>
            <Field
              label={this.props.label}
              component={InputField}
              placeholder={this.props.label}
              name={this.props.attribute}
              className="form-control"
              validate={[required(), ...validateConfig]}
              autoFocus={true}
            />
            <small className="help-block">{this.props.desc}</small>
          </div>

          <div className="Modal__actions">
            <AsyncButton
              type="submit"
              className="btn btn-primary btn-block"
              text="Update"
              pendingText="Updating..."
              onClick={this.handleSubmit}
            />
          </div>
        </div>
      </form>
    );
  }
}

export default compose(
  connect(null, { closeModal, showNotification }),
  reduxForm({
    form: 'updateMerchantConfigForm',
  }),
)(MerchantConfigForm);
