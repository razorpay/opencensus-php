import { Component } from 'react';
import AsyncButton from 'react-async-button';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';

import { withSplitzService } from 'common/splitz';
import InputField from 'common/ui/Forms/InputField';
import TwoFactorVerificationContext from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import { required, email } from 'common/utils/validators';
import { updateSession } from 'merchant/reducers/session';
import User from 'merchantLA/models/User';
import { sendInvitation, fetchTeamDetails } from 'merchantLA/reducers/team';
import { closeModal as close2faModal } from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

// eslint-disable-next-line no-unused-vars
const selector = formValueSelector('newInvitation');

class NewInvitation extends Component {
  state = {
    isProcessing: false,
  };

  updateUserSession = () => {
    const updatedUser = new User(this.props.user);
    if (!updatedUser.isTwoFactorSetupDone) {
      return updatedUser
        .fetch()
        .then((res) => {
          this.props.updateSession({ user: res.data });
        })
        .catch((err) => {
          this.props.showNotification({
            type: 'error',
            message: 'Failed to update user data! Please refresh the page.',
          });
        });
    }
  };

  save = (props) => {
    const user = this.props.user.user;
    return this.props
      .sendInvitation({ ...props, sender_name: user.name })
      .then(() => {
        this.props.fetchTeamDetails({ merchant_id: this.props.user.current });
        this.props.showNotification({
          type: 'success',
          message: `Invitation has been successfully sent to ${props.email}`,
        });
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  handleCriticalFlow = (criticalFlow, handleSubmit) => {
    this.setState({ isProcessing: true });

    return new Promise((resolve) => {
      criticalFlow({
        enforceVerifyOtp: true,
        modes: ['live', 'test'],
        onUserTwoFaVerified: () => {
          this.props.close2faModal();
          Promise.resolve(handleSubmit(this.save)())
            .then(() => this.updateUserSession())
            .then(resolve)
            .finally(() => this.setState({ isProcessing: false }));
        },
        onFlowTermination: () => {
          this.props.showNotification({
            type: 'error',
            message: 'Authentication failed. Please reload the page and try again.',
          });
          this.setState({ isProcessing: false });
        },
      });
    });
  };

  render() {
    const { handleSubmit } = this.props;
    const { isProcessing } = this.state;

    return (
      <form onSubmit={handleSubmit(this.save)} style={{ marginBottom: '35px' }}>
        <div className="row">
          <div className="col-md-5">
            <div className="form-group">
              <Field
                name="email"
                component={InputField}
                className="form-control"
                placeholder="Email address of the user"
                autoFocus={true}
                validate={[
                  required(),
                  email('Invalid Email'),
                  (value) => {
                    if (value === this.props.user.user.email) {
                      return "You can't invite yourself";
                    }
                    return null;
                  },
                ]}
              />
            </div>
          </div>

          <div className="col-md-3">
            <div className="form-group">
              <TwoFactorVerificationContext.Consumer>
                {({ criticalFlow }) => (
                  <AsyncButton
                    className="btn btn-primary"
                    text="Send Invitation"
                    pendingText="Sending Invitation..."
                    disabled={isProcessing}
                    onClick={() => this.handleCriticalFlow(criticalFlow, handleSubmit)}
                  />
                )}
              </TwoFactorVerificationContext.Consumer>
            </div>
          </div>
        </div>

        <div className="form-group">
          <div className="alert alert-info text-center">
            Allows access to all views except access for Bank Details and Team Management
          </div>
        </div>
      </form>
    );
  }
}

const mapStateToProps = (state) => {
  return {
    ...state.session,
  };
};

export default compose(
  withSplitzService,
  connect(mapStateToProps, {
    sendInvitation,
    fetchTeamDetails,
    close2faModal,
    updateSession,
    ...NotificationsActions,
  }),
  reduxForm({
    form: 'newInvitation',
    initialValues: {
      email: '',
      role: 'linked_account_admin',
    },
  }),
)(NewInvitation);
