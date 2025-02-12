import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import ModalHeader from 'common/ui/ModalHeader';
import Alert from 'common/ui/Forms/Alert';
import { closeModal } from 'merchant_common/reducers/modals';
import RadioButton from 'common/ui/Forms/RadioButton';
import { showNotification } from 'merchant_common/reducers/notifications';
import { cancelSubscription } from 'merchant/reducers/subscriptions';
import analytics from '../../analytics';
import { compose } from 'redux';

class CancellationModal extends Component {
  constructor() {
    // eslint-disable-next-line prefer-rest-params
    super(...arguments);
    this.state = {
      errors: null,
    };
  }

  componentDidMount() {
    if (this.props.onMount) {
      this.props.onMount(this.props.subscriptionId);
    }
  }

  componentWillUnmount() {
    if (this.props.onUnmount) {
      this.props.onUnmount(this.props.subscriptionId);
    }
  }

  save = (params) => {
    params.id = this.props.subscriptionId;

    const cancellationMessages = [
      'Subscription cancelled successfully',
      'Subscription will be cancelled at the end of current billing cycle.',
    ];

    return this.props
      .cancelSubscription(params)
      .then(() => {
        if (this.props.onSubscriptionCancel) {
          this.props.onSubscriptionCancel(this.props.subscriptionId, params.cancel_at_cycle_end);
        }

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
        <ModalHeader title="Cancel Subscription?" onCloseClick={this.props.closeModal} />

        <form className="form-horizontal" onSubmit={handleSubmit(this.save)}>
          <div className="modal-body">
            <Alert type="error" message={this.state.errors} />

            <div className="text-muted">
              <b>Important:</b> This action can not be undone. The subscription will move to
              cancelled state.
            </div>

            <div style={{ padding: '8px 0' }}>
              <Field
                component={RadioButton}
                name="cancel_at_cycle_end"
                htmlValue="1"
                label={() => (
                  <div>
                    <div>
                      <b>Cancel at end of current billing cycle</b>
                      <div className="text-muted">Next payment will not be charged</div>
                    </div>
                    <div />
                  </div>
                )}
              />
              <Field
                component={RadioButton}
                name="cancel_at_cycle_end"
                htmlValue="0"
                label={() => <b>Cancel Immediately</b>}
              />
            </div>
            <div className="btn-toolbar">
              <button
                type="button"
                className="btn btn-default btn-half"
                onClick={() => {
                  analytics.track('subscription.cancel.discard');
                  this.props.closeModal();
                }}
              >
                No, don&#39;t
              </button>

              <AsyncButton
                type="submit"
                className="btn btn-primary btn-half"
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

export default compose(
  connect(null, {
    closeModal,
    cancelSubscription,
    showNotification,
  }),
  reduxForm({
    form: 'cancelSubscription',
    initialValues: {
      cancel_at_cycle_end: '1',
    },
  }),
)(CancellationModal);
