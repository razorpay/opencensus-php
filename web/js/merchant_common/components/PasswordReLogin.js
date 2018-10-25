import { Component } from 'react';
import Button from 'component/Button';

import ajax from 'merchant/utils/ajax';
import Form from 'component/Form';
import Input from 'component/Input';

import { ModalMask, Modal, ModalContent } from 'component/Modal';

export default class PasswordReLogin extends Component {
  state = {};

  refreshPage() {
    location.hash = '/access/signin';
    location.reload();
  }

  onSubmit = formData => {
    this.setState({ isPending: true });

    ajax({
      url: '/user/signin',
      method: 'post',
      appendModeInURL: false,
      data: formData,
    })
      .then(resp => {
        if (resp.success) {
          this.props.removeLockScreen();
          this.props.resumeLockActionCB();
        } else {
          this.setState({ isPending: false });
          throw {
            errors:
              'Some network issue. Please re-enter password or Reload the page.',
          };
        }
      })
      .catch(({ errors }) => {
        this.setState({ isPending: false });

        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    return (
      <ModalMask maskClosable={false} class={'password-relogin'} isBlur={true}>
        <Modal showCloseBtn={false}>
          <ModalContent>
            <div class="heading">
              Your account is locked
              <div clas="underline" />
            </div>

            <Form class="Share-section" onSubmit={this.onSubmit}>
              <input
                name="email"
                value={this.props.userEmail}
                hidden
                readOnly
              />
              <Input
                name="password"
                type="password"
                placeholder="Enter Password"
                class="Input--inline is-focused Input-addons--transparent"
                addonBefore={<i class="i i-outline-lock" />}
                autoFocus
              />

              <Button.Primary
                type="submit"
                class="Btn--Link Button--input--right"
                disabled={this.state.isPending}
              >
                {this.state.isPending ? 'Unlocking...' : 'Unlock'}
              </Button.Primary>
            </Form>

            <p>
              Logged in as <b>{this.props.userEmail}</b>
              <br />
              Not you?
              <Button.Transparent
                type="button"
                class="Btn--Link"
                onClick={this.refreshPage}
              >
                Log in as a different user
              </Button.Transparent>
            </p>
          </ModalContent>
        </Modal>
      </ModalMask>
    );
  }
}
