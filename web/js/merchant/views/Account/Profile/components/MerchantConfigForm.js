import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import ModalHeader from 'common/ui/ModalHeader';
import { reduxForm, Field } from 'redux-form';
import { required } from 'common/utils/validators';
import InputField from 'common/ui/Forms/InputField';
import { compose } from 'redux';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';

class MerchantConfigForm extends PureComponent {
  constructor(props) {
    super(props);

    this.props.initialize({
      [props.attribute]: props.value,
    });

    this.resetValue = this.resetValue.bind(this);
    this.handleSubmit = this.handleSubmit.bind(this);
  }

  resetValue() {
    analyticsTrack({
      objectName: 'display name edit popup',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        action: 'reset',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    this.props.change(this.props.attribute, this.props.value);
  }

  handleSubmit(e) {
    analyticsTrack({
      objectName: 'display name edit popup',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        action: 'update',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    this.props.handleSubmit(this.props.updateMerchantConfig)(e);
  }

  render() {
    const { handleSubmit } = this.props;
    return (
      <form
        onSubmit={(...a) => {
          analyticsTrack({
            objectName: 'display name edit popup',
            actionName: 'clicked',
            screen: 'my account',
            properties: {
              action: 'update',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          return handleSubmit(this.props.updateMerchantConfig)(...a);
        }}
      >
        <ModalHeader
          title={`Edit ${this.props.label}`}
          onCloseClick={(args) => {
            analyticsTrack({
              objectName: 'display name edit popup',
              actionName: 'clicked',
              screen: 'my account',
              properties: {
                action: 'cancel',
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            this.props.closeModal(args);
          }}
        />
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
