import React, { Component } from 'react';

import { ModalContent } from 'component/Modal';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import Field from 'ui/Field';

import { adminPut } from 'common/fetch';
import { closeModal, notifyError, notifySuccess } from 'common/modal';

export default class UserResetPassword extends Component {
  handlePasswordReset = body => {
    const { merchantId, member } = this.props;

    if (body.password !== body.password_confirmation) {
      return notifyError('Password does not match.');
    }

    return adminPut({
      url: `live/merchants/${merchantId}/users/${member.id}/password`,
      data: body,
    }).then(response => {
      if (response) {
        notifySuccess('Password has been changed.');
        closeModal();
      }
    });
  };

  render() {
    const { name } = this.props.member;

    return (
      <ModalContent header={`Reset Password for ${name} `}>
        <Form class="full-span full-elements">
          <Field label="Password" name="password" type="password" required />
          <Field
            label="Confirm Password"
            name="password_confirmation"
            type="password"
            required
          />
          <AsyncButton
            text="Reset"
            class="btn btn-primary"
            pendingClass="small spinner pull-right"
            onSubmit={this.handlePasswordReset}
          />
        </Form>
      </ModalContent>
    );
  }
}
