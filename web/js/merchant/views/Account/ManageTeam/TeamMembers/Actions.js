import { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import PropTypes from 'prop-types';

import ShowWhen, { showWhenUtil } from 'merchant/components/ShowWhen';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification as showNotificationReducer } from 'merchant_common/reducers/notifications';
import { pickProps, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import rolesList from 'merchant/helpers/permissions/roles-list';
import ModalHeader from 'common/ui/ModalHeader';
import NewInvitation from '../components/NewInvitation';
import { analyticsTrack } from 'common/utils/analytics';
import ChangeOwner from 'merchant/views/Settings/EmailSelfServe/components/SameTeam/ChangeOwner';
import {
  removeMember as removeMemberReducer,
  updateMember as updateMemberReducer,
  updateOwner as updateOwnerReducer,
} from 'merchant/reducers/team';

class MembersActions extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  update = () => {
    const { member, items } = this.props;

    analyticsTrack({
      objectName: 'team member update',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        location: 'manage team',
        noOfTeamMembers: items.length,
        role: member.role,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    const visibleFields = {
      role: showWhenUtil({
        additionalCondition: (user) => user.isAllowedEdit('team'),
      }),
      contactMobile: showWhenUtil({
        additionalCondition: (user) => user.isMerchantRestricted,
      }),
    };

    const toBePickedFields = getToBePickedUpFields(visibleFields, ['id']);

    const defaults = pickProps(member, toBePickedFields);
    const onFormSubmit = (...e) => {
      const { role } = e[0];
      if (role === rolesList.OWNER) {
        return this.props
          .updateOwner(member.email, true)
          .then(() => {
            analyticsTrack({
              objectName: 'team member update',
              actionName: 'status',
              screen: 'my account',
              properties: {
                location: 'manage team',
                status: 'success',
                role: member.role,
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            return Promise.resolve();
          })
          .catch((error) => {
            analyticsTrack({
              objectName: 'team member update',
              actionName: 'status',
              screen: 'my account',
              properties: {
                location: 'manage team',
                status: 'failure',
                failureReason: error.errors[0],
                role: member.role,
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            return Promise.reject();
          });
      }
      return this.props
        .updateMember(...e)
        .then(() => {
          analyticsTrack({
            objectName: 'team member update',
            actionName: 'status',
            screen: 'my account',
            properties: {
              location: 'manage team',
              status: 'success',
              role: member.role,
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          return Promise.resolve();
        })
        .catch((error) => {
          analyticsTrack({
            objectName: 'team member update',
            actionName: 'status',
            screen: 'my account',
            properties: {
              location: 'manage team',
              status: 'failure',
              failureReason: error.errors[0],
              role: member.role,
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          return Promise.reject();
        });
    };
    return this.props.openModal({
      size: 'small',
      component: (
        <>
          <ModalHeader title="Update Member" onCloseClick={this.props.closeModal} />
          <div class="modal-body">
            <NewInvitation
              visibleFields={visibleFields}
              defaults={{ ...defaults }}
              ctaText="Update Member Details"
              successMsg="Member updated successfully"
              closeModal={this.props.closeModal}
              onFormSubmit={onFormSubmit}
            />
          </div>
        </>
      ),
    });
  };

  remove = () => {
    const { member, removeMember, showNotification, items } = this.props;

    analyticsTrack({
      objectName: 'team member remove',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        location: 'manage team',
        noOfTeamMembers: items.length,
        role: member.role,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    this.context.confirm({
      header: 'Remove User?',
      message: (
        <>
          Are you sure you want to remove <strong>{member.name || member.email}</strong> as a member
          of your team
        </>
      ),

      affirmativeLabel: 'Yes, Remove',
      affirmativePendingLabel: 'Removing...',

      abortLabel: "No, Don't Remove",

      abort: () => {
        analyticsTrack({
          objectName: 'team member remove popup',
          actionName: 'clicked',
          screen: 'my account',
          properties: {
            location: 'manage team',
            action: 'No',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      },
      action: () => {
        analyticsTrack({
          objectName: 'team member remove popup',
          actionName: 'clicked',
          screen: 'my account',
          properties: {
            location: 'manage team',
            action: 'Yes',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        return removeMember(member.id)
          .then((response) => {
            if (response) {
              analyticsTrack({
                objectName: 'team member remove',
                actionName: 'status',
                screen: 'my account',
                properties: {
                  location: 'manage team',
                  status: 'success',
                  role: member.role,
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
              showNotification({
                type: 'success',
                message: 'Member remove successfully from the team',
              });
            }
          })
          .catch(({ errors }) => {
            analyticsTrack({
              objectName: 'team member remove',
              actionName: 'status',
              screen: 'my account',
              properties: {
                location: 'manage team',
                status: 'failure',
                failureReason: errors[0],
                role: member.role,
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            showNotification({
              type: 'error',
              message: errors,
            });
          });
      },
    });
  };

  change = (context) => {
    const items = this.props.items;
    return context.criticalFlow({
      modes: ['test', 'live'],
      onUserTwoFaVerified: () => {
        this.props.openModal({
          size: 'medium',
          component: <ChangeOwner items={items} />,
        });
      },
    });
  };

  render() {
    const { member } = this.props;

    // change action is no longer permitted/needed
    if (isOwner(member)) {
      return null;
    }

    return (
      <>
        <button class="btn btn-primary m-r" onClick={this.update}>
          Update
        </button>

        <ShowWhen additionalCondition={(userCurrent) => userCurrent.isAllowedEdit('team')}>
          <AsyncButton
            class="btn btn-default"
            text="Remove"
            pendingText="Removing..."
            onClick={this.remove}
          />
        </ShowWhen>
      </>
    );
  }
}

function isOwner(member) {
  return member.role === rolesList.OWNER;
}

function getToBePickedUpFields(visibleFields, alwaysPickedUpFields) {
  const toBePickedFields = [...alwaysPickedUpFields];
  if (visibleFields.role) {
    toBePickedFields.push('role');
  }

  if (visibleFields.contactMobile) {
    toBePickedFields.push('contact_mobile');
  }

  return toBePickedFields;
}

const mapStateToProps = (state) => {
  return { user: state.session.user };
};

export default connect(mapStateToProps, {
  removeMember: removeMemberReducer,
  updateMember: updateMemberReducer,
  updateOwner: updateOwnerReducer,
  openModal,
  closeModal,
  showNotification: showNotificationReducer,
})(MembersActions);
