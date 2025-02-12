import React from 'react';
import Amount from 'common/ui/Amount';
import { isAmount } from 'common/utils/validators';
import Input from 'common/new-ui/Input';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Popover, { PopoverBody } from 'common/ui/Popover';
import {
  titleCase,
  i18CurrencyConversionFromMinorUnitToCommonUnit,
  i18CurrencyConversionFromCommonUnitToMinorUnit,
  getCurrencyConfig,
} from 'common/utils/rzp-utils';

export const MIN_AMOUNT_TEXT = 'Minimum due amount';

export const PopoverBodyText = (
  <PopoverBody>
    <div>You can set a minimum due amount for the first payment made by your customer</div>
  </PopoverBody>
);

export function validateMinAmount(val, maxAmount, currency = 'INR') {
  if (!val) {
    return '';
  }

  if (!isAmount(val)) {
    const { decimals } = getCurrencyConfig(currency);
    const decimal = val && val.split('.');

    if (decimal.length == decimals && decimal[1].length > decimals) {
      return `Enter upto ${decimals} decimals`;
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
  return '';
}

export default class EditMinimumAmount extends React.Component {
  state = this.resetState();

  resetState() {
    return {
      isEditableMode: false,
      first_payment_min_amount: this.props.value
        ? i18CurrencyConversionFromMinorUnitToCommonUnit(this.props.value, this.props.currency)
        : '',
    };
  }

  makeEditable = () => {
    this.setState({
      isEditableMode: true,
    });
    setTimeout(() => document.getElementsByName('first_payment_min_amount')[0].focus(), 10);
    this.props.trackerFn('Edit Minimum Payable Amount');
  };

  handleSubmit = () => {
    let first_payment_min_amount = null;
    if (this.state.first_payment_min_amount !== '0' && this.state.first_payment_min_amount) {
      first_payment_min_amount = i18CurrencyConversionFromCommonUnitToMinorUnit(
        this.state.first_payment_min_amount,
        this.props.currency,
      );
    }

    return this.props
      .editFn({
        first_payment_min_amount,
      })
      .then((resp) => {
        if (resp && resp.data) {
          this.setState(this.resetState());

          this.props.trackerFn('Edit Minimum Payable Amount (Saved)');
        }
      });
  };

  render() {
    const { isRoleAllowedEdit, isIssued, currency } = this.props;

    let content = (
      <div style={{ marginTop: 4 }}>
        <span style={{ marginRight: 12 }}>
          <Amount
            value={i18CurrencyConversionFromCommonUnitToMinorUnit(
              this.state.first_payment_min_amount,
              currency,
            )}
            currency={currency}
          />{' '}
          {titleCase(MIN_AMOUNT_TEXT)}
          <small className="help-content">
            <i className="i i-info-outline" style={{ verticalAlign: 'middle', marginLeft: 4 }} />
            <Popover align="top">{PopoverBodyText}</Popover>
          </small>
        </span>
        {isRoleAllowedEdit && isIssued && (
          <Button.Transparent onClick={this.makeEditable} className="Button--Link">
            Change
          </Button.Transparent>
        )}
      </div>
    );

    if (this.state.isEditableMode) {
      content = (
        <div style={{ marginTop: 4 }}>
          {MIN_AMOUNT_TEXT}
          <Input.Group className="InputGroup--inline">
            <div className="Input-content">
              <Input.CurrencySelect name="currency" defaultValue="INR" disabled />

              <Input
                name="first_payment_min_amount"
                placeholder={titleCase(MIN_AMOUNT_TEXT)}
                className="Input--small"
                value={this.state.first_payment_min_amount}
                validator={(val) =>
                  validateMinAmount(
                    val,
                    i18CurrencyConversionFromMinorUnitToCommonUnit(this.props.maximum, currency),
                    currency,
                  )
                }
                onChange={(e) => {
                  this.setState({
                    first_payment_min_amount: e.target.value,
                  });
                }}
              />
            </div>
          </Input.Group>

          <div style={{ textAlign: 'right', margin: '8px 0 12px 0', width: 260 }}>
            <Button.Transparent
              className="Button--Link"
              onClick={() => {
                this.setState(this.resetState());
                this.props.trackerFn(this.props.entityId, 'Cancel Minimum Payable Amount');
              }}
            >
              Cancel
            </Button.Transparent>

            <AsyncBtn.Primary
              className="Button--small"
              style={{ marginRight: 0, marginLeft: 16 }}
              disabled={
                !!validateMinAmount(
                  this.state.first_payment_min_amount,
                  i18CurrencyConversionFromMinorUnitToCommonUnit(this.props.maximum, currency),
                  currency,
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
