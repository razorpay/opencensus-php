import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import Alert from 'rzp/ui/Forms/Alert';
import ModalHeader from 'rzp/ui/ModalHeader';
import { required } from 'rzp/utils/validators';
import { saveWebhook } from 'merchant/modules/webhooks';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';
import ShowWhen from 'merchant/components/ShowWhen';

const WebhookEventCheckbox = ({ eventName }) => {
  return (
    <div class="checkbox">
      <label>
        <Field
          name={`events['${eventName}']`}
          component="input"
          type="checkbox"
        />
        {eventName}
      </label>
    </div>
  );
};

@connect(null, { saveWebhook, ...ModalActions, ...NotificationsActions })
@reduxForm({
  form: 'newWebhook',
})
export default class AddWebhook extends Component {
  state = {
    errors: null,
  };

  componentWillMount() {
    if (this.props.webhook) {
      this.props.initialize(this.props.webhook);
    }
  }

  save = props => {
    return this.props
      .saveWebhook(props)
      .then(webhook => {
        this.props.onSave(webhook);
        this.props.showNotification({
          type: 'success',
          message: 'Webhook saved successfully',
        });
      })
      .catch(err => {
        this.setState({
          errors: err.errors,
        });
      });
  };

  render() {
    const { handleSubmit } = this.props;
    const isEdit = !!this.props.webhook;

    return (
      <div>
        <ModalHeader
          title={isEdit ? 'Edit Webhook' : 'New Webhook'}
          onCloseClick={this.props.closeModal}
        />

        <form class="form-horizontal" onSubmit={handleSubmit(this.save)}>
          <div class="modal-body">
            <Alert type="error" message={this.state.errors} />

            <div class="form-group">
              <label class="col-md-3 control-label label-required">
                Webhook URL
              </label>
              <div class="col-md-9">
                <Field
                  name="url"
                  component={InputField}
                  class="form-control"
                  autoFocus={true}
                  validate={required()}
                />
              </div>
            </div>

            {isEdit && (
              <div class="form-group">
                <label class="col-md-3 control-label">Active</label>
                <div class="col-md-9">
                  <div class="checkbox">
                    <label>
                      <Field name="active" component="input" type="checkbox" />
                    </label>
                    <div class="help-block">
                      <small>
                        Whether the webhook is enabled or not. You can enable
                        the webhook here if it was disabled due to multiple
                        errors.
                      </small>
                    </div>
                  </div>
                </div>
              </div>
            )}

            <div class="form-group">
              <label class="col-md-3 control-label">Secret</label>
              <div class="col-md-9">
                <Field
                  name="secret"
                  component={InputField}
                  class="form-control"
                />
                {isEdit && (
                  <div class="help-block">
                    <small>
                      You can leave the secret blank to leave it unedited.
                    </small>
                  </div>
                )}
              </div>
            </div>

            <div class="form-group">
              <label class="col-md-3 control-label">Active Events</label>
              <div class="col-md-9">
                <WebhookEventCheckbox eventName="payment.authorized" />
                <WebhookEventCheckbox eventName="payment.captured" />
                <WebhookEventCheckbox eventName="payment.failed" />
                <WebhookEventCheckbox eventName="invoice.paid" />
                <WebhookEventCheckbox eventName="invoice.expired" />
                <WebhookEventCheckbox eventName="order.paid" />

                <ShowWhen apiFeatureEnabled="subscriptions">
                  <div>
                    <WebhookEventCheckbox eventName="subscription.activated" />
                    <WebhookEventCheckbox eventName="subscription.charged" />
                    <WebhookEventCheckbox eventName="subscription.pending" />
                    <WebhookEventCheckbox eventName="subscription.halted" />
                    <WebhookEventCheckbox eventName="subscription.cancelled" />
                    <WebhookEventCheckbox eventName="subscription.completed" />
                  </div>
                </ShowWhen>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-default"
              onClick={this.props.closeModal}
            >
              Cancel
            </button>

            <AsyncButton
              type="submit"
              class="btn btn-primary"
              text="Save"
              pendingText="Saving..."
              onClick={handleSubmit(this.save)}
            />
          </div>
        </form>
      </div>
    );
  }
}

AddWebhook.defaultProps = {
  onSave: () => {},
};
