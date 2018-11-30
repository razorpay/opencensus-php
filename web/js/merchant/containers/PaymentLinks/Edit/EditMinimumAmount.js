import Amount from 'rzp/ui/Amount';
import { isAmount } from 'rzp/utils/validators';
import Input from 'component/Input';
import Button, { AsyncBtn } from 'component/Button';
import Popover, { PopoverBody } from 'rzp/ui/Popover';

export default class EditMinimumAmount extends React.Component {
  state = this.resetState();

  resetState() {
    return {
      isEditableMode: false,
      first_payment_min_amount: this.props.value ? this.props.value / 100 : '',
    };
  }

  makeEditable = () => {
    this.setState({
      isEditableMode: true,
    });
    setTimeout(
      () => document.getElementsByName('first_payment_min_amount')[0].focus(),
      10
    );
    this.props.trackerFn('Edit Minimum Payable Amount');
  };

  validate = val => {
    if (!val) {
      return;
    }

    if (!isAmount(val)) {
      const decimal = val && val.split('.');

      if (decimal.length == 2 && decimal[1].length > 2) {
        return 'Enter upto 2 decimals';
      } else {
        return 'Invalid Amount';
      }
    }

    if (Number(val) > 0 && Number(val) < 1) {
      return 'Minimum Payable amount should be atleast ₹1';
    }
    if (Number(val) >= this.props.maximum / 100) {
      return 'Minimum Payable amount must be less than Amount';
    }
  };

  handleSubmit = () => {
    let first_payment_min_amount = null;
    if (
      this.state.first_payment_min_amount !== '0' &&
      this.state.first_payment_min_amount
    ) {
      first_payment_min_amount = this.state.first_payment_min_amount * 100;
    }

    return this.props
      .editFn({
        first_payment_min_amount,
      })
      .then(resp => {
        if (resp && resp.data) {
          this.setState(this.resetState());

          this.props.trackerFn('Edit Minimum Payable Amount (Saved)');
        }
      });
  };

  render() {
    const { isRoleAllowedEdit, currency } = this.props;

    let content = (
      <div style={{ marginTop: 4 }}>
        <span style={{ marginRight: 12 }}>
          <Amount
            value={this.state.first_payment_min_amount * 100}
            currency={currency}
          />{' '}
          Minimum Payable Amount
          <small className="help-content">
            <i
              class="i i-info-outline"
              style={{ verticalAlign: 'middle', marginLeft: 4 }}
            />
            <Popover align="top">
              <PopoverBody>
                <div>
                  You can set a minimum payable amount for the first payment
                  made by your customer
                </div>
              </PopoverBody>
            </Popover>
          </small>
        </span>
        {isRoleAllowedEdit && (
          <Button.Transparent onClick={this.makeEditable} class="Button--Link">
            Change
          </Button.Transparent>
        )}
      </div>
    );

    if (this.state.isEditableMode) {
      content = (
        <div style={{ marginTop: 4 }}>
          Minimum Payable Amount
          <Input
            name="first_payment_min_amount"
            placeholder="Minimum Payable Amount"
            addonBefore="₹"
            class="Input--small"
            value={this.state.first_payment_min_amount}
            validator={this.validate}
            onChange={e => {
              this.setState({
                first_payment_min_amount: e.target.value,
              });
            }}
          />
          <div style={{ textAlign: 'right', marginBottom: 12, width: 260 }}>
            <Button.Transparent
              class="Button--Link"
              onClick={() => {
                this.setState(this.resetState());
                this.props.trackerFn(
                  this.props.entityId,
                  'Cancel Minimum Payable Amount'
                );
              }}
            >
              Cancel
            </Button.Transparent>

            <AsyncBtn.Primary
              class="Button--small"
              style={{ marginRight: 0, marginLeft: 16 }}
              disabled={!!this.validate(this.state.first_payment_min_amount)}
              onClick={this.handleSubmit}
              showLoader={false}
              pendingState="Saving..."
            >
              Save
            </AsyncBtn.Primary>
          </div>
        </div>
      );
    }

    return content;
  }
}
