import { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import PropTypes from 'prop-types';

import ShowWhen, { showWhenUtil } from 'merchant/components/ShowWhen';
import { removeMember, updateMember } from 'merchant/reducers/team';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { pickProps, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import rolesList from 'merchant/helpers/permissions/roles-list';

import ModalHeader from 'common/ui/ModalHeader';
import NewInvitation from '../components/NewInvitation';
import { analyticsTrack } from 'common/utils/analytics';

@connect(null, {
  removeMember,
  updateMember,
  openModal,
  closeModal,
  showNotification,
})
export default class MembersActions extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  update = () => {
    analyticsTrack({
      objectName: 'team member update',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        location: 'manage team',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    const member = this.props.member;

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
              onFormSubmit={(...e) => {
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
                        ...getCommonAnalyticsProperties(window.rzp_user),
                      },
                    });
                    return Promise.resolve();
                  })
                  .catch((e) => {
                    analyticsTrack({
                      objectName: 'team member update',
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

  remove = () => {
    analyticsTrack({
      objectName: 'team member remove',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    const { member, removeMember, showNotification } = this.props;
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
      action: () => {
        analyticsTrack({
          objectName: 'team member remove popup',
          actionName: 'clicked',
          screen: 'my account',
          properties: {
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
                  status: 'success',
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
                status: 'failure',
                failureReason: errors[0],
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            showNotification({
              type: error,
              message: errors,
            });
          });
      },
    });
  };

  render() {
    const member = this.props.member;

    return (
      !isOwner(member) && (
        <>
          <button class="btn btn-primary m-r" onClick={this.update}>
            Update
          </button>

          <ShowWhen additionalCondition={(user) => user.isAllowedEdit('team')}>
            <AsyncButton
              class="btn btn-default"
              text="Remove"
              pendingText="Removing..."
              onClick={this.remove}
            />
          </ShowWhen>
        </>
      )
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
