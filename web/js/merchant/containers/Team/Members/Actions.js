import { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import PropTypes from 'prop-types';

import ShowWhen, { showWhenUtil } from 'merchant/components/ShowWhen';
import { removeMember, updateMember } from 'merchant/reducers/team';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { pickProps } from 'rzp/utils/rzp-utils';

import ModalHeader from 'rzp/ui/ModalHeader';

import NewInvitation from '../NewInvitation';

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
    const member = this.props.member;

    const visibleFields = {
      role: showWhenUtil({
        additionalCondition: user => user.isAllowedEdit('team'),
      }),
      contactMobile: showWhenUtil({
        additionalCondition: user => user.isMerchantRestricted,
      }),
    };

    const toBePickedFields = getToBePickedUpFields(visibleFields, ['id']);

    const defaults = pickProps(member, toBePickedFields);

    return this.props.openModal({
      size: 'small',
      component: (
        <>
          <ModalHeader
            title="Update Member"
            onCloseClick={this.props.closeModal}
          />
          <div className="modal-body">
            <NewInvitation
              visibleFields={visibleFields}
              defaults={{ ...defaults }}
              ctaText="Update Member Details"
              successMsg="Member updated successfully"
              closeModal={this.props.closeModal}
              onFormSubmit={this.props.updateMember}
            />
          </div>
        </>
      ),
    });
  };

  remove = () => {
    const { member, removeMember, showNotification } = this.props;
    this.context.confirm({
      header: 'Remove User?',
      message: (
        <>
          Are you sure you want to remove{' '}
          <strong>{member.name || member.email}</strong> as a member of your
          team
        </>
      ),

      affirmativeLabel: 'Yes, Remove',
      affirmativePendingLabel: 'Removing...',

      abortLabel: "No, Don't Remove",
      action: () => {
        return removeMember(member.id)
          .then(response => {
            if (response) {
              showNotification({
                type: 'success',
                message: 'Member remove successfully from the team',
              });
            }
          })
          .catch(({ errors }) => {
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

          <ShowWhen additionalCondition={user => user.isAllowedEdit('team')}>
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
  return member.role === 'owner';
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
