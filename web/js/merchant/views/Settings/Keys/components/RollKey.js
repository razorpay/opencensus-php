import { Component } from 'react';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import ModalHeader from 'common/ui/ModalHeader';
import Alert from 'common/ui/Forms/Alert';
import { closeModal } from 'merchant_common/reducers/modals';
import RadioButton from 'common/ui/Forms/RadioButton';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { showNotification } from 'merchant_common/reducers/notifications';

class RollKey extends Component {
  constructor(props) {
    super(props);
    this.state = {
      errors: null,
    };
  }

  save = (props) => {
    const params = this.props.params;
    params.delay_roll = props.delay_roll;
    params.merchantId = this.props.merchantId;

    analyticsTrack({
      objectName: 'regenerate key popup',
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: 'API Keys',
        actionName: 'ok',
        option:
          props.delay_roll === '0'
            ? 'De-activate old key immediately'
            : 'De-activate old key in 24 hours',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    return this.props.generateKey(params).catch(({ errors }) => {
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
          title="Roll Key"
          onCloseClick={() => {
            analyticsTrack({
              objectName: 'regenerate key popup',
              actionName: 'clicked',
              screen: 'settings',
              properties: {
                location: 'API Keys',
                actionName: 'close',
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            this.props.closeModal();
          }}
        />

        <form class="form-horizontal" onSubmit={handleSubmit(this.save)}>
          <div class="modal-body">
            <Alert type="error" message={this.state.errors} />

            <div class="radio rollkey" data-test="deactivate-immediately">
              <Field
                component={RadioButton}
                name="delay_roll"
                htmlValue="0"
                label={() => <span>De-activate old key immediately</span>}
              />
            </div>
            <div class="radio">
              <Field
                component={RadioButton}
                name="delay_roll"
                htmlValue="1"
                label={() => <span>De-activate old key in 24 hours</span>}
              />
            </div>
          </div>

          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-default"
              onClick={() => {
                analyticsTrack({
                  objectName: 'regenerate key popup',
                  actionName: 'clicked',
                  screen: 'settings',
                  properties: {
                    location: 'API Keys',
                    actionName: 'cancel',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
                this.props.closeModal();
              }}
            >
              Cancel
            </button>

            <AsyncButton
              type="submit"
              class="btn btn-primary"
              text="OK"
              onClick={handleSubmit(this.save)}
            />
          </div>
        </form>
      </div>
    );
  }
}

export default compose(
  connect((state) => state.session, { closeModal, showNotification }),
  reduxForm({
    form: 'newKeyModal',
  }),
)(RollKey);
