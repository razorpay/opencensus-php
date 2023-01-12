import React, { useState } from 'react';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';

import { showNotification } from 'merchant_common/reducers/notifications';
import { getEmailStatus } from 'merchant/reducers/team';
import Input from 'common/new-ui/Input';
import Form from 'common/new-ui/Form';
import Button from 'common/new-ui/Button';
import { required, isEmail } from 'common/utils/validators';

import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

import NewID from './components/NewId/NewID';
import SameTeam from './components/SameTeam/SameTeam';
import DifferentTeam from './components/DifferentTeam/DifferentTeam';
import { Modules } from 'common/constant/enums';

// eslint-disable-next-line no-shadow
const EmailInputForm = ({ user, closeModal, openModal, showNotification, getEmailStatus }) => {
  const [disabled, setDisabled] = useState(false);
  let enteredEmail = '';

  const onEmailInputBlur = (e) => {
    enteredEmail = e.target.value;
    analyticsTrackWithUserInfo({
      objectName: 'new email id',
      actionName: 'filled',
      screen: Modules.AccountAndSettings,
      properties: {
        location: 'profile',
        newEmailId: e.target.value,
      },
    });
  };

  const onProceed = (e) => {
    const { email, setContactEmail } = e;

    setDisabled(true);
    analyticsTrackWithUserInfo({
      objectName: 'new email id',
      actionName: 'filled',
      screen: Modules.AccountAndSettings,
      properties: {
        location: 'profile',
        newEmailId: email,
        oldEmailId: user.email,
        updateContactEmail: setContactEmail,
      },
    });

    return getEmailStatus(email, setContactEmail)
      .then((res) => {
        setDisabled(false);
        if (res.data && !res.data.is_user_exist) {
          analyticsTrackWithUserInfo({
            objectName: 'email update invitation',
            actionName: 'sent',
            screen: Modules.AccountAndSettings,
            properties: {
              location: 'profile',
              newEmailId: email,
              oldEmailId: user.email,
            },
          });
          openModal({
            size: 'small',
            component: <NewID newEmail={email} />,
          });
        } else if (res.data && res.data.is_team_member) {
          openModal({
            size: 'small',
            component: <SameTeam newEmail={email} />,
          });
        } else {
          openModal({
            size: 'small',
            component: <DifferentTeam newEmail={email} setContactEmail={setContactEmail} />,
          });
        }
      })
      .catch((err) => {
        setDisabled(false);
        showNotification({
          type: 'error',
          message: err.errors[0] || 'some error occurred',
        });
      });
  };

  return (
    <div className="e-self-serve">
      <div className="e-self-serve-heading">
        Enter the New Email ID
        <button type="button" className="close" onClick={closeModal}>
          <i className="i i-close" />
        </button>
      </div>
      <div className="subtitle-msg">Enter the email to which you wish to update your login id</div>
      <Form onSubmit={onProceed}>
        <Input
          name="email"
          type="email"
          placeholder="Enter Email"
          validator={(val) => {
            required();
            return !isEmail(val) && 'Invalid Email';
          }}
          onBlur={onEmailInputBlur}
          requiredError="This Field is required"
        />
        <Input
          name="reEmail"
          type="email"
          onPaste={(e) => {
            e.preventDefault();
            return null;
          }}
          placeholder="Re-Enter Email"
          validator={(value) => {
            if (value !== enteredEmail) return "Email doesn't match";
            return null;
          }}
          required
        />

        <Input.Check
          name="setContactEmail"
          fieldLabel={
            <div className="set-contact-email">
              Use this ID as my contact email and receive all Razorpay related communication to this
              ID
              <div className="current-email-msg">
                Currently your contact email has been set to {user.user.email}
              </div>
            </div>
          }
          defaultValue="1"
        />
        <Button.Primary type="submit" className="btn-block" disabled={disabled}>
          Proceed
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
  {
    openModal,
    closeModal,
    showNotification,
    getEmailStatus,
  },
)(EmailInputForm);
