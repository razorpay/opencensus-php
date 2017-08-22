import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import ModalHeader from 'rzp/ui/ModalHeader';
import Alert from 'rzp/ui/Forms/Alert';
import { isBlank } from 'rzp/utils/rzp-utils';
import { closeModal } from 'rzp/modules/modals';
import RadioButton from 'rzp/ui/Forms/RadioButton';
import { showNotification } from 'rzp/modules/notifications';
import { cancelSubscription } from 'merchant/modules/subscriptions';

@connect(state => state.session, {
  closeModal,
  cancelSubscription,
  showNotification,
})
@reduxForm({
  form: 'cancelSubscription',
  initialValues: {
    cancel_at_cycle_end: '1',
  },
})
export default class CancellationModal extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      errors: null,
    };
  }

  save = params => {
    params.id = this.props.subscriptionId;

    var cancellationMessages = [
      'Subscription cancelled successfully',
      'Subscription will be cancelled at the end of current billing cycle.',
    ];

    return this.props
      .cancelSubscription(params)
      .then(() => {
        this.props.closeModal();

        this.props.showNotification({
          type: 'success',
          message: cancellationMessages[params.cancel_at_cycle_end],
        });
      })
      .catch(({ errors }) => {
        this.setState({
          errors,
        });
      });
  };

  render() {
    const { handleSubmit } = this.props;

    return (
      <div>
        <ModalHeader
          title="Cancel Subscription?"
          onCloseClick={this.props.closeModal}
        />

        <form class="form-horizontal" onSubmit={handleSubmit(this.save)}>
          <div class="modal-body">
            <Alert type="error" message={this.state.errors} />

            <div class="text-muted">
              <b>Important:</b> This action can not be undone. The subscription
              will move to cancelled state.
            </div>

            <div
              style={{
                padding: '8px 0',
              }}
            >
              <Field
                component={RadioButton}
                name="cancel_at_cycle_end"
                htmlValue="1"
                label={() =>
                  <div>
                    <div>
                      <b>Cancel at end of current billing cycle</b>
                      <div class="text-muted">
                        Next payment will not be charged
                      </div>
                    </div>
                    <div />
                  </div>}
              />
              <Field
                component={RadioButton}
                name="cancel_at_cycle_end"
                htmlValue="0"
                label={() => <b>Cancel Immediately</b>}
              />
            </div>
            <div class="btn-toolbar">
              <button
                type="button"
                class="btn btn-default btn-half"
                onClick={this.props.closeModal}
              >
                No, don't
              </button>

              <AsyncButton
                type="submit"
                class="btn btn-primary btn-half"
                text="Yes, Cancel"
                pendingText="Cancelling..."
                onClick={handleSubmit(this.save)}
              />
            </div>
          </div>
        </form>
      </div>
    );
  }
}
