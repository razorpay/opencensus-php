import { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import PropTypes from 'prop-types';

import { cancelInvitation, updateInvitation, resendInvitation } from 'merchant/reducers/invitation';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { pickProps, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import ModalHeader from 'common/ui/ModalHeader';

import NewInvitation from '../components/NewInvitation';
import { analyticsTrack } from 'common/utils/analytics';

@connect(null, {
  cancelInvitation,
  updateInvitation,
  resendInvitation,
  showNotification,
  openModal,
  closeModal,
})
export default class InvitationsActions extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  update = () => {
    analyticsTrack({
      objectName: 'invitation update',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        location: 'manage team',
        // pending invitations left
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    const { invitation, updateInvitation, closeModal, ...props } = this.props;
    const visibleFields = {
      role: true,
    };

    const defaults = pickProps(invitation, ['id', 'role']);

    props.openModal({
      size: 'small',
      component: (
        <>
          <ModalHeader title="Update Invitation" onCloseClick={closeModal} />
          <div class="modal-body">
            <NewInvitation
              visibleFields={visibleFields}
              defaults={{ ...defaults }}
              ctaText="Update Invitation"
              successMsg="Invitation is updated successfully"
              closeModal={this.props.closeModal}
              onFormSubmit={(...e) => {
                return this.props
                  .updateInvitation(...e)
                  .then(() => {
                    analyticsTrack({
                      objectName: 'invitation update',
                      actionName: 'status',
                      screen: 'my account',
                      properties: {
                        location: 'manage team',
                        status: 'success',
                        ...getCommonAnalyticsProperties(window.rzp_user),
                      },
                    });
                    return Promise.resolve();
                  })
                  .catch((e) => {
                    analyticsTrack({
                      objectName: 'invitation update',
                      actionName: 'status',
                      screen: 'my account',
                      properties: {
                        location: 'manage team',
                        status: 'failure',
                        failureReason: e.errors[0],
                        ...getCommonAnalyticsProperties(window.rzp_user),
                      },
                    });
                    return Promise.reject();
                  });
              }}
            />
          </div>
        </>
      ),
    });
  };

  cancel = () => {
    analyticsTrack({
      objectName: 'invitation cancel',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        location: 'manage team',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    const { invitation, cancelInvitation } = this.props;
    this.context.confirm({
      header: 'Cancel Invitation',
      message: (
        <>
          Are you sure you want to cancel invitation sent to <strong>{invitation.email}</strong>?
        </>
      ),
      affirmativeLabel: 'Yes, Cancel',
      affirmativePendingLabel: 'Cancelling...',
      abortLabel: "No, Don't Cancel",
      abort: () => {
        analyticsTrack({
          objectName: 'invitation cancel popup',
          actionName: 'clicked',
          screen: 'my account',
          properties: {
            location: 'manage team',
            action: 'no',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      },
      action: () => {
        analyticsTrack({
          objectName: 'invitation cancel popup',
          actionName: 'clicked',
          screen: 'my account',
          properties: {
            location: 'manage team',
            action: 'yes',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        return cancelInvitation(invitation.id)
          .then((response) => {
            if (response) {
              analyticsTrack({
                objectName: 'invitation cancel',
                actionName: 'status',
                screen: 'my account',
                properties: {
                  location: 'manage team',
                  status: 'success',
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
              this.props.showNotification({
                type: 'success',
                message: 'Invitation successfully cancelled',
              });
            }
          })
          .catch(({ errors }) => {
            analyticsTrack({
              objectName: 'invitation cancel',
              actionName: 'status',
              screen: 'my account',
              properties: {
                location: 'manage team',
                status: 'failure',
                failureReason: errors[0],
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            this.props.showNotification({
              type: 'error',
              message: errors,
            });
          });
      },
    });
  };

  resend = () => {
    const { invitation, loggedInUserName } = this.props;
    analyticsTrack({
      objectName: 'invitation resend',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        location: 'manage team',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    return this.props
      .resendInvitation({
        id: invitation.id,
        sender_name: loggedInUserName,
      })
      .then((response) => {
        if (response) {
          analyticsTrack({
            objectName: 'invitation resend',
            actionName: 'status',
            screen: 'my account',
            properties: {
              status: 'success',
              location: 'manage team',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          this.props.showNotification({
            type: 'success',
            message: 'Invitation resent successfully',
          });
        }
      })
      .catch(({ errors }) => {
        analyticsTrack({
          objectName: 'invitation resend',
          actionName: 'status',
          screen: 'my account',
          properties: {
            location: 'manage team',
            status: 'failure',
            failureReason: errors[0],
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    return (
      <>
        <AsyncButton
          class="btn btn-primary m-r"
          onClick={this.resend}
          text="Resend"
          pendingText="Resending..."
        />
        <button class="btn btn-primary m-r" onClick={this.update}>
          Update
        </button>

        <AsyncButton
          class="btn btn-default"
          text="Cancel"
          pendingText="Cancelling"
          onClick={this.cancel}
        />
      </>
    );
  }
}
