import React, { useState } from 'react';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { updateOwner } from 'merchant/reducers/team';
import { showNotification } from 'merchant_common/reducers/notifications';

import Input from 'common/new-ui/Input';
import Form from 'common/new-ui/Form';
import Button from 'common/new-ui/Button';
import { roles } from 'merchant/helpers/data';

import OwnerUpdated from './OwnerUpdatedModal';

// eslint-disable-next-line no-shadow
const ChangeOwner = ({ openModal, closeModal, user, items, showNotification, updateOwner }) => {
  const [disabled, setDisabled] = useState(false);
  const filteredItems = items.filter((item) => item.role !== 'owner');

  const onSubmit = (e) => {
    setDisabled(true);
    const { newOwner } = e;
    const { email } = filteredItems[parseInt(newOwner, 10)];
    return updateOwner(email)
      .then(() => {
        setDisabled(false);
        openModal({
          size: 'small',
          component: <OwnerUpdated newEmail={email} />,
        });
      })
      .catch((err) =>
        showNotification({
          type: 'error',
          message: err.errors[0] || 'some error occured',
        }),
      );
  };

  return (
    <div className="e-self-serve">
      <div className="e-self-serve-heading">
        Change Owner
        <button type="button" className="close" onClick={closeModal}>
          <i className="i i-close" />
        </button>
      </div>
      <p className="verification-msg">
        Choose the team member whom you want to upgrade to the owner role
      </p>

      <div className="dialogue-container">
        <p className="dialogue-msg">
          Your exisitng email - id {user.user.email} will be changed to manager role
        </p>
      </div>
      <Form onSubmit={onSubmit}>
        <Input.Radio
          name="newOwner"
          options={filteredItems.map((item, idx) => {
            return {
              label: (
                <div>
                  <p className="new-owner-email">{item.email}</p>
                  <p className="new-owner-role">{roles[item.role].label}</p>
                </div>
              ),
              value: idx,
            };
          })}
        />
        <Button.Primary type="submit" className="btn-block" disabled={disabled}>
          Change Owner
        </Button.Primary>
      </Form>
    </div>
  );
};

export default connect(
  (state) => {
    return {
      user: state.session.user,
    };
  },
  { openModal, closeModal, showNotification, updateOwner },
)(ChangeOwner);
