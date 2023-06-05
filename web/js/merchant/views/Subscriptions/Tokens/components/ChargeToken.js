import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import { rupeesToPaise } from 'common/utils/rzp-utils';
import { showNotification } from 'merchant_common/reducers/notifications';

import ModalHeader from 'common/ui/ModalHeader';

import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';

import { chargeToken } from 'merchant/reducers/token';
import { AmountTooltip } from 'common/ui/Amount';
import { CARD_AFA_MAX_LIMIT } from 'merchant/views/Subscriptions/constants';

@withRouter
@connect((state) => ({ user: state.session.user }), { chargeToken, showNotification })
export default class ChargeToken extends Component {
  state = {};

  chargeToken = () => {
    const token = this.props.token;
    return this.props
      .chargeToken({
        amount: rupeesToPaise(this.state.amount),
        receipt: this.state.receipt,
        description: this.state.description,
        id: token.id,
        currency: token.subscription_registration && token.subscription_registration.currency,
      })
      .then((response) => {
        if (response) {
          this.props.showNotification({
            type: 'success',
            message: 'Charge for token has been initiated',
          });
        } else {
          this.props.showNotification({
            type: 'error',
            message: 'There was an error while charging token, Please try again later',
          });
        }
        this.props.closeModal();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors[0],
        });
      });
  };

  handleChange = ({ target }) => {
    const value = target.value;
    const name = target.name;

    this.setState({ [name]: value });
  };

  render() {
    const token = this.props.token;
    const isCard = token.method === 'card';
    let isDomesticCard = null;
    let maxAmount = null;
    const defaultCardAFALimit = rupeesToPaise(CARD_AFA_MAX_LIMIT);
    if (isCard) {
      isDomesticCard = !token.card.international;
      maxAmount = token?.subscription_registration?.max_amount || CARD_AFA_MAX_LIMIT;
    }
    const amount = rupeesToPaise(this.state.amount);
    let isTwoFactorNeeded = false;
    // If charge amount is greater than token max_amount or is greater than
    // RBI's default AFA limit (15k) AFA is required
    if (amount >= maxAmount || amount >= defaultCardAFALimit) {
      isTwoFactorNeeded = true;
    }

    // i18n doesn't support two factor auth
    if (this.props.user.isOrgCurlec) {
      isTwoFactorNeeded = false;
    }
    const currency = token.subscription_registration
      ? token.subscription_registration.currency
      : 'INR';
    return (
      <div>
        <ModalHeader title={`Charge  ${token.id}`} onCloseClick={this.props.closeModal} />
        <div class="modal-body">
          <Form onSubmit={this.chargeToken} onChange={this.handleChange}>
            <main class="form-container">
              <Input
                name="amount"
                label="Amount"
                addonBefore={<AmountTooltip currency={currency} parentQuerySelector=".Modal" />}
                required
                class="Input--Amount Input--vTop"
              />
              {amount && isDomesticCard ? (
                <span>
                  {isTwoFactorNeeded
                    ? 'This amount will be debited once the customer completes OTP verification.'
                    : 'This amount will be auto-debited after 24 hours'}
                </span>
              ) : (
                ''
              )}
              <Input name="receipt" label="Receipt No." class="Input--vTop" />

              <Input.Textarea name="description" label="Description" class="Input--vTop" />
            </main>
            <footer class="m-t">
              <AsyncBtn.Primary
                type="submit"
                pendingState="Charging..."
                class="Button--full-width"
                onClick={this.chargeToken}
              >
                Charge Token
              </AsyncBtn.Primary>
            </footer>
          </Form>
        </div>
      </div>
    );
  }
}
