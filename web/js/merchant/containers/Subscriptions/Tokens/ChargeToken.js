import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import { isAmount } from 'rzp/utils/validators';
import { rupeesToPaise } from 'rzp/utils/rzp-utils';
import { showNotification } from 'rzp/modules/notifications';

import ModalHeader from 'rzp/ui/ModalHeader';

import Form from 'component/Form';
import Input from 'component/Input';
import { AsyncBtn } from 'component/Button';

import { chargeToken } from 'merchant/modules/token';

@withRouter
@connect(null, { chargeToken, showNotification })
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
      })
      .then(response => {
        if (response) {
          this.props.showNotification({
            type: 'success',
            message: 'Token is charged successfully',
          });
        } else {
          this.props.showNotification({
            type: 'error',
            message:
              'There was an error while charging token, Please try again later',
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
    return (
      <div>
        <ModalHeader
          title={'Charge ' + token.id}
          onCloseClick={this.props.closeModal}
        />
        <div className="modal-body">
          <Form onSubmit={this.chargeToken} onChange={this.handleChange}>
            <main className="form-container">
              <Input
                name="amount"
                label="Amount"
                addonBefore="₹"
                required
                class="Input--vTop"
              />

              <Input name="receipt" label="Receipt No." class="Input--vTop" />

              <Input.Textarea
                name="description"
                label="Description"
                class="Input--vTop"
              />
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
