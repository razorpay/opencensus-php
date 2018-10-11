import React, { Component } from 'react';
import { connect } from 'react-redux';
import ModalHeader from 'rzp/ui/ModalHeader';
import { closeModal } from 'rzp/modules/modals';
import Button, { AsyncBtn } from 'component/Button';
import { isInteger } from 'rzp/utils/validators';
import ajax from 'merchant/utils/ajax';
import { trackOndemand } from './ga';
import { fetchCurrentBalance } from 'merchant/modules/home';
import Input from 'component/Input';
import Alert from 'rzp/ui/Forms/Alert';
import Popover, { PopoverBody } from 'rzp/ui/Popover';

@connect(
  state => ({ user: state.session.user }),
  {
    closeModal,
    fetchCurrentBalance,
  }
)
export default class OndemandModal extends Component {
  constructor(props) {
    super(props);
    this.state = {
      isSaving: false,
      isSaved: false,
      amount: 0,
      validAmount: true,
      errors: [],
    };
    if (props.currentBalance) {
      this.state.amount = parseInt(props.currentBalance / 100);
    }
    this.validateAmount = this.validateAmount.bind(this);
    this.onSubmit = this.onSubmit.bind(this);
    this.handleChange = this.handleChange.bind(this);
    this.handleCloseModal = this.handleCloseModal.bind(this);
  }

  trackOnSubmit() {
    let eventLabel = `${this.props.fromWhere} | `;
    eventLabel +=
      this.props.currentBalance == this.state.amount ? 'Total' : 'Partial';
    trackOndemand.trackSettleEarly(eventLabel);
  }

  onSubmit() {
    let payload = {
      amount: this.state.amount * 100,
      currency: 'INR',
    };

    this.trackOnSubmit();
    this.setState({
      isSaving: true,
      errors: [],
    });

    return ajax(
      {
        url: '/merchant/payout/demand',
        method: 'POST',
        data: payload,
      },
      {},
      '/merchant/api'
    )
      .then(response => {
        this.setState({
          isSaving: false,
          isSaved: true,
        });
        this.props.fetchCurrentBalance();
      })
      .catch(response => {
        this.setState({
          isSaving: false,
          isSaved: false,
          errors: response.errors,
        });
      });
  }

  handleChange(e) {
    this.setState({
      amount: e.target.value,
      validAmount: !this.validateAmount(e.target.value),
    });
  }

  validateAmount(val) {
    if (isInteger(val) && val > 0) {
      if (val * 100 > this.props.currentBalance) {
        trackOndemand.trackAmounTooHigh(this.props.fromWhere);
        return 'Please ensure that the entered amount is not more than the balance';
      }
    } else {
      return 'Invalid Amount';
    }
  }

  handleCloseModal(eventType) {
    switch (eventType) {
      case 'Close Modal Screen 1':
        trackOndemand.trackCloseModal(this.props.fromWhere);
        break;
      case 'Close Button':
        trackOndemand.trackCloseButton(this.props.fromWhere);
        break;
      case 'Close Modal Screen 2':
        trackOndemand.trackSuccessCloseModal(this.props.fromWhere);
        break;
    }
    this.props.closeModal();
  }

  render() {
    return (
      <React.Fragment>
        {this.state.isSaved ? (
          <div class="onmdemand-modal">
            <ModalHeader
              title="Early Settlement Requested"
              onCloseClick={() => this.handleCloseModal('Close Modal Screen 2')}
            />
            <div class="modal-body">
              <div class="help-block">
                The requested balance will be settled in the next 3 working
                hours
                <i className="i i-info-circle" />
                <Popover
                  align="right"
                  theme="dark"
                  parentQuerySelector=".onmdemand-modal"
                >
                  <PopoverBody>
                    Working hours are 9am - 6pm everyday except on Bank Holidays
                  </PopoverBody>
                </Popover>
              </div>
              <Button.Primary
                class="close-btn"
                onClick={() => this.handleCloseModal('Close Button')}
              >
                Close
              </Button.Primary>
            </div>
          </div>
        ) : (
          <div class="onmdemand-modal">
            <ModalHeader
              title="Early Settlements"
              onCloseClick={() => this.handleCloseModal('Close Modal Screen 1')}
            />
            <div class="modal-body">
              <p>
                Get settlements in 3 working hours for an additional charge.
              </p>
              <p>
                <i className="i i-info-circle" /> No Early Settlements on Bank
                holidays
              </p>
              {this.state.errors && (
                <div>
                  {this.state.errors.map((item, key) => {
                    return <Alert key={key} type="error" message={item} />;
                  })}
                </div>
              )}
              <div>
                <div class="InputGroup Input Input--vTop">
                  <Input
                    label="Enter amount to be settled"
                    required={true}
                    addonBefore="₹"
                    autoFocus={true}
                    name="amount"
                    class="Input"
                    disabled={this.state.isSaving}
                    value={this.state.amount}
                    validator={this.validateAmount}
                    onChange={this.handleChange}
                  />
                </div>
                <AsyncBtn.Primary
                  class="submit-btn"
                  disabled={this.state.isSaving || !this.state.validAmount}
                  pendingState="Requesting"
                  onClick={this.onSubmit}
                >
                  Settle Early
                </AsyncBtn.Primary>
              </div>
            </div>
          </div>
        )}
      </React.Fragment>
    );
  }
}
