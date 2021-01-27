import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import Button from 'common/new-ui/Button';

import ajax from 'merchant/utils/ajax';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';

import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import logoutGoogleAccount from '../../common/utils/logoutGoogle';

const captchaKey = '6LdsmwETAAAAADmNGCLvbrjL09O_Fv7WOVTngbO4';

@withRouter
export default class PasswordReLogin extends Component {
  googleAuthInstance;
  service = this.props.isPartner ? 'Partner' : 'PG';
  state = {
    gResponse: null,
    isProd: window.location.hostname.endsWith('razorpay.com'),
  };

  componentDidMount() {
    const self = this;

    window.gResponse = (token) => {
      self.setState({
        gResponse: token,
      });
    };

    if (window.grecaptcha && !this.props.isGoogleLogin) {
      window.grecaptcha.ready(() => {
        const gCaptchaParent = document.getElementsByClassName('g-recaptcha')[0];

        window.grecaptcha.render(gCaptchaParent, {
          sitekey: captchaKey,
          callback: window.gResponse,
        });
      });
    }

    window.rzpQ &&
      window.rzpQ.push(
        window.rzpQ.now().onbr().success('dash.display_unlock', {
          mode: 'live',
          sessionId: window.session_id,
          version: 1.1,
          service: this.service,
        }),
      );

    if (this.props.isGoogleLogin) {
      this.initializeGoogleAuth();
    }
  }

  eventObj = {
    mode: 'live',
    sessionId: window.session_id,
    version: 1.1,
  };

  resetCaptcha() {
    window.grecaptcha.reset();
  }

  refreshPage() {
    location.hash = '/access/signin';
    location.reload();
  }

  onSubmit = (formData) => {
    if (!formData.password) {
      this.props.showNotification({
        type: 'error',
        message: 'Please enter password',
      });
      return;
    }

    window.rzpQ &&
      window.rzpQ.push(
        window.rzpQ
          .now()
          .onbr()
          .initiated('dash.unlock', { ...this.eventObj, method: 'email', service: this.service }),
      );

    this.setState({ isPending: true });

    let captcha = this.state.gResponse;

    if (!this.state.isProd) {
      captcha = 'Faked';
    }

    const reqPayload = {
      ...formData,
      captcha,
    };

    ajax({
      url: '/user/unlock',
      method: 'post',
      appendModeInURL: false,
      data: reqPayload,
    })
      .then((resp) => {
        if (resp.success) {
          //redirect the user to signin if the account is logged out
          this.props.removeLockScreen();
          this.props.resumeLockActionCB();

          window.rzpQ &&
            window.rzpQ.push(
              window.rzpQ
                .now()
                .onbr()
                .success('dash.unlock', {
                  ...this.eventObj,
                  method: 'email',
                  service: this.service,
                  email: formData.email,
                }),
            );
        } else {
          this.setState({ isPending: false });
          window.rzpQ &&
            window.rzpQ.push(
              window.rzpQ
                .now()
                .onbr()
                .failed('dash.unlock', {
                  ...this.eventObj,
                  method: 'email',
                  service: this.service,
                }),
            );

          throw {
            errors: 'Some network issue. Please re-enter password or Reload the page.',
          };
        }
      })
      .catch(({ errors }) => {
        this.setState({ isPending: false });

        window.rzpQ &&
          window.rzpQ.push(
            window.rzpQ
              .now()
              .onbr()
              .failed('dash.unlock', {
                ...this.eventObj,
                method: 'email',
                service: this.service,
                error: errors[0],
              }),
          );

        this.props.showNotification({
          type: 'error',
          message: ['Some network issue. Please reload the page.'],
        });

        this.resetCaptcha();
      });
  };

  initializeGoogleAuth = () => {
    const button = document.getElementById('gauth');
    if (!this.googleAuthInstance && window.gapi && button) {
      window.gapi.load('auth2', () => {
        // Retrieve the singleton for the GoogleAuth library and set up the client.
        this.googleAuthInstance = window.gapi.auth2.init({
          client_id: window.OAUTH_CLIENT_ID,
        });
        this.googleAuthInstance.attachClickHandler(
          button,
          {},
          (googleUser) => {
            const idToken = googleUser.getAuthResponse().id_token;
            const email = googleUser.getBasicProfile().getEmail();
            if (email !== this.props.userEmail) {
              this.props.showNotification({
                type: 'error',
                message: 'You have selected a wrong email id. Please try again.',
              });
            } else {
              this.unlockAccount(email, idToken);
            }
          },
          (errors) => {
            window.rzpQ &&
              window.rzpQ.push(
                window.rzpQ
                  .now()
                  .onbr()
                  .failed('dash.unlock', {
                    ...this.eventObj,
                    method: 'google_oauth',
                    error: errors.error,
                  }),
              );
          },
        );
      });
    }
  };

  unlockAccount = (email, token) => {
    const reqPayload = {
      id_token: token,
      oauth_provider: 'google',
    };

    ajax({
      url: '/user/oauth-unlock',
      method: 'post',
      appendModeInURL: false,
      data: reqPayload,
    })
      .then((resp) => {
        if (resp.success) {
          //redirect the user to signin if the account is logged out
          this.props.removeLockScreen();
          this.props.resumeLockActionCB();

          window.rzpQ &&
            window.rzpQ.push(
              window.rzpQ
                .now()
                .onbr()
                .success('dash.unlock', {
                  ...this.eventObj,
                  method: 'google_oauth',
                  service: this.service,
                  email: email,
                }),
            );
        } else {
          window.rzpQ &&
            window.rzpQ.push(
              window.rzpQ
                .now()
                .onbr()
                .failed('dash.unlock', {
                  ...this.eventObj,
                  method: 'google_oauth',
                  service: this.service,
                }),
            );
          this.props.showNotification({
            type: 'error',
            message: 'Some network issue. Please re-enter password or Reload the page.',
          });
        }
      })
      .catch(({ errors }) => {
        this.setState({ isPending: false });
        window.rzpQ &&
          window.rzpQ.push(
            window.rzpQ
              .now()
              .onbr()
              .failed('dash.unlock', {
                ...this.eventObj,
                method: 'google_oauth',
                service: this.service,
              }),
          );

        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  handleUnlockClick = () => {
    window.rzpQ &&
      window.rzpQ.push(
        window.rzpQ
          .now()
          .onbr()
          .initiated('dash.unlock', {
            ...this.eventObj,
            method: 'google_oauth',
            service: this.service,
          }),
      );

    this.initializeGoogleAuth();
  };

  loginWithAnother = () => {
    logoutGoogleAccount();
    this.refreshPage();
  };

  render() {
    let isSubmitDisabled = !this.state.gResponse || this.state.isPending;
    if (!this.state.isProd) isSubmitDisabled = false;
    let View;

    let UnlockWithPassword = (
      <Form class="Share-section" onSubmit={this.onSubmit}>
        <input name="email" value={this.props.userEmail} hidden readOnly />
        <Input
          name="password"
          type="password"
          placeholder="Enter Password"
          class="Input--inline is-focused Input-addons--transparent"
          addonBefore={<i className="i i-outline-lock" />}
          autoFocus
        />

        <Button.Primary
          type="submit"
          class="Btn--Link Button--input--right"
          disabled={isSubmitDisabled}
        >
          {this.state.isPending ? 'Unlocking...' : 'Unlock'}
        </Button.Primary>
      </Form>
    );

    let UnlockWithGoogle = (
      <Button.Primary class="unlock-btn" id="gauth" onClick={this.handleUnlockClick}>
        <img src="/dist/css/assets/google-icon.svg" alt="Google Icon" />
        Unlock with Google
      </Button.Primary>
    );

    if (this.props.isGoogleLogin === undefined) {
      View = (
        <>
          {UnlockWithPassword}
          {/* recaptcha */}
          <div className="g-recaptcha" />

          <div className="separator-container">
            <div className="separator left" />
            <div className="text">or</div>
            <div className="separator right" />
          </div>

          {UnlockWithGoogle}
          <p>
            Logged in as <b>{this.props.userEmail}</b>
            <br />
            Not you?
            <Button.Transparent type="button" class="Btn--Link" onClick={this.refreshPage}>
              Log in as a different user
            </Button.Transparent>
          </p>
        </>
      );
    } else if (!this.props.isGoogleLogin) {
      View = (
        <>
          {UnlockWithPassword}
          {/* recaptcha */}
          <div className="g-recaptcha" />
          <p>
            Logged in as <b>{this.props.userEmail}</b>
            <br />
            Not you?
            <Button.Transparent type="button" class="Btn--Link" onClick={this.refreshPage}>
              Log in as a different user
            </Button.Transparent>
          </p>
        </>
      );
    } else if (this.props.isGoogleLogin) {
      View = (
        <>
          {UnlockWithGoogle}
          <p>
            Logged in as <b>{this.props.userEmail}</b>
            <br />
            Not you?
            <Button.Transparent type="button" class="Btn--Link" onClick={this.loginWithAnother}>
              Log in as a different user
            </Button.Transparent>
          </p>
        </>
      );
    }

    return (
      <ModalMask maskClosable={false} class="password-relogin" isBlur={true}>
        <Modal showCloseBtn={false}>
          <ModalContent>
            <div className="heading">
              Your account is locked
              <div class="underline" />
            </div>
            {View}
          </ModalContent>
        </Modal>
      </ModalMask>
    );
  }
}
