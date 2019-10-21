import Amount from 'rzp/ui/Amount';
import { isAmount } from 'rzp/utils/validators';
import Input from 'component/Input';
import Button, { AsyncBtn } from 'component/Button';
import Popover, { PopoverBody } from 'rzp/ui/Popover';
import { titleCase } from 'rzp/utils/rzp-utils';
import { AmountTooltip } from 'rzp/ui/Amount';

export const MIN_AMOUNT_TEXT = 'Minimum due amount';

export const PopoverBodyText = (
  <PopoverBody>
    <div>
      You can set a minimum due amount for the first payment made by your
      customer
    </div>
  </PopoverBody>
);

export function validateMinAmount(val, maxAmount) {
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
    return `${MIN_AMOUNT_TEXT} must be at least ₹1`;
  }
  if (Number(val) >= maxAmount) {
    return `${MIN_AMOUNT_TEXT} must be less than Amount`;
  }
}

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
          {titleCase(MIN_AMOUNT_TEXT)}
          <small className="help-content">
            <i
              class="i i-info-outline"
              style={{ verticalAlign: 'middle', marginLeft: 4 }}
            />
            <Popover align="top">{PopoverBodyText}</Popover>
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
          {MIN_AMOUNT_TEXT}
          <Input.Group class="InputGroup--inline">
            <div class="Input-content">
              <Input.CurrencySelect
                name="currency"
                defaultValue="INR"
                disabled
              />

              <Input
                name="first_payment_min_amount"
                placeholder={titleCase(MIN_AMOUNT_TEXT)}
                class="Input--small"
                value={this.state.first_payment_min_amount}
                validator={val =>
                  validateMinAmount(val, this.props.maximum / 100)
                }
                onChange={e => {
                  this.setState({
                    first_payment_min_amount: e.target.value,
                  });
                }}
              />
            </div>
          </Input.Group>

          <div
            style={{ textAlign: 'right', margin: '8px 0 12px 0', width: 260 }}
          >
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
              disabled={
                !!validateMinAmount(
                  this.state.first_payment_min_amount,
                  this.props.maximum / 100
                )
              }
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
