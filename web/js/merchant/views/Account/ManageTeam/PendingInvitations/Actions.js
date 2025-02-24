import { Component } from 'react';
import { connect } from 'react-redux';
import PropTypes from 'prop-types';

import * as InvitationActions from 'merchant/reducers/invitation';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { pickProps, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import ModalHeader from 'common/ui/ModalHeader';

import NewInvitation from '../components/NewInvitation';
import { analyticsTrack } from 'common/utils/analytics';
import {
  ActionList,
  ActionListItem,
  Dropdown,
  DropdownButton,
  DropdownOverlay,
  MoreVerticalIcon,
  Box,
  Button,
} from '@razorpay/blade/components';
import RolesList from 'merchant/helpers/permissions/roles-list';

class InvitationsActions extends Component {
  constructor(props) {
    super(props);
    this.state = {
      resending: false,
    };
  }

  static contextTypes = {
    confirm: PropTypes.func,
  };

  update = () => {
    const { invitation, updateInvitation, closeModal, pendingInvitationLength, ...props } =
      this.props;

    analyticsTrack({
      objectName: 'invitation update',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        location: 'manage team',
        role: invitation.role,
        pendingInvitations: pendingInvitationLength,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    const visibleFields = {
      role: true,
    };

    let defaults = pickProps(invitation, ['id', 'role']);
    if (invitation.role === RolesList.PARTNER_AGENT) {
      defaults = pickProps(invitation, ['id', 'role', 'email', 'metadata']);
    }

    props.openModal({
      size: 'large',
      component: (
        <>
          <ModalHeader title="Update Invitation" onCloseClick={closeModal} />
          <div className="modal-body">
            <NewInvitation
              isHandlingPosPartnerAgent={defaults.role === RolesList.PARTNER_AGENT}
              isUpdatingInvitation
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
                        role: e?.[0]?.role,
                        ...getCommonAnalyticsProperties(window.rzp_user),
                      },
                    });
                    return Promise.resolve();
                  })
                  .catch((error) => {
                    analyticsTrack({
                      objectName: 'invitation update',
                      actionName: 'status',
                      screen: 'my account',
                      properties: {
                        location: 'manage team',
                        status: 'failure',
                        failureReason: error.errors[0],
                        role: e?.[0]?.role,
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
    const { invitation, cancelInvitation, pendingInvitationLength } = this.props;

    analyticsTrack({
      objectName: 'invitation cancel',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        location: 'manage team',
        role: invitation.role,
        pendingInvitations: pendingInvitationLength,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
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
                  role: invitation.role,
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
                role: invitation.role,
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
    this.setState({ resending: true });
    const { invitation, loggedInUserName, pendingInvitationLength } = this.props;
    analyticsTrack({
      objectName: 'invitation resend',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        location: 'manage team',
        role: invitation.role,
        pendingInvitations: pendingInvitationLength,
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
              role: invitation.role,
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
            role: invitation.role,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      })
      .finally(() => {
        this.setState({ resending: false });
      });
  };

  render() {
    const { isJkOrg } = this.props;

    if (isJkOrg) {
      return (
        <Dropdown>
          <DropdownButton color="white" variant="primary" icon={MoreVerticalIcon} />
          <DropdownOverlay>
            <ActionList>
              <ActionListItem onClick={this.resend} title="Resend" />
              <ActionListItem onClick={this.update} title="Update" />
              <ActionListItem onClick={this.cancel} title="Cancel" />
            </ActionList>
          </DropdownOverlay>
        </Dropdown>
      );
    }
    return (
      <Box display="flex" gap="spacing.3">
        <Button onClick={this.resend} isLoading={this.state.resending}>
          Resend
        </Button>
        <Button variant="tertiary" onClick={this.update}>
          Update
        </Button>
        <Button variant="tertiary" onClick={this.cancel}>
          Cancel
        </Button>
      </Box>
    );
  }
}

export default connect(null, {
  ...InvitationActions,
  showNotification,
  ...ModalActions,
})(InvitationsActions);
