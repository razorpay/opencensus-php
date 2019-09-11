import { connect } from 'react-redux';

import Form from 'component/Form';
import Button from 'component/Button';
import Input from 'component/Input';

import { mapFieldToAmountFieldType } from '../../Amount_Fields/V3';
import FIELD_TYPES from '../../Amount_Fields/fieldTypes';

@connect(state => ({
  user: state.session.user,
}))
export default class AdvancedForm extends React.PureComponent {
  constructor(props) {
    super(props);

    const field = props.field;

    this.state = {
      disableSubmit: false,
      hasQuantity: typeof field.quantity_available !== 'undefined',
    };

    this.fieldType = props.fieldType || mapFieldToAmountFieldType(field);
  }

  onChange = ({ target }) => {
    setTimeout(this.toggleSubmitBtn); // Validate form for input errors via class change in DOM, hence delayed.
  };

  toggleSubmitBtn = () => {
    const form = this.formEl;
    let disableSubmit = !!form.querySelectorAll('.is-invalid').length;

    this.setState({ disableSubmit });
  };

  onSaveForm = formData => {
    // console.log('formData...', formData);
    this.props.onSaveForm(formData, this.fieldType);
  };

  toggleAddQuantity = data => {
    this.setState({
      hasQuantity: !this.state.hasQuantity,
    });
  };

  get FIELD_availableQuantity() {
    const { hasQuantity } = this.state;
    const quantityAvailable = this.props.field.quantity_available || '';

    return (
      <Input.Radio
        class="Input--hasQuantity Input--vTop"
        label={() => (
          <React.Fragment>
            Units Available
            <div class="modal-description">in stock</div>
          </React.Fragment>
        )}
        onChange={this.toggleAddQuantity}
        options={[
          'Unlimited',
          {
            label: (
              <div class="Input--quantity">
                <span>Limited</span>
                {hasQuantity && (
                  <Input
                    name="quantity_available"
                    defaultValue={quantityAvailable}
                    autoFocus
                    step="1"
                    pattern="\d+"
                  />
                )}
              </div>
            ),
          },
        ]}
        defaultValue={hasQuantity}
      />
    );
  }

  validateMinAmountLimit = minVal => {
    const maxVal = this.maxAmountLimit && this.maxAmountLimit.value;
    const { currency } = this.props;

    // TODO: This should be communicated properly to the merchant that if set empty then field becomes optional
    // Letting min amount to be ''. If so, then field will automatically become non-optional
    if (minVal === '') {
      return;
    }

    const minAmountAllowed =
      this.props.user.getCurrencyList[currency].min_value / 100; // In Paisa(lower unit of currency)

    if (Number(minVal) < Number(minAmountAllowed)) {
      return `Min amount cannot be less than ${minAmountAllowed}`;
    }

    if (maxVal && Number(minVal) > Number(maxVal)) {
      return 'Min amount must be less than Max amount';
    }
  };

  validateMaxAmountLimit = maxVal => {
    const minVal = this.minAmountLimit && this.minAmountLimit.value;

    if (maxVal === '') {
      return;
    }

    if (Number(maxVal) <= 0) {
      return 'Max amount cannot be 0';
    }

    if (minVal && Number(maxVal) < Number(minVal)) {
      return 'Max amount must be more than Min amount';
    }
  };

  get FIELD_amountLimits() {
    const { field, currency } = this.props;
    const minAmount = field.min_amount || '';
    const maxAmount = field.max_amount || '';

    return (
      <Input.Group
        class="InputGroup--inline Input--vTop Input--limits"
        label="Input Price Limits"
      >
        <div class="Input-content Input-content--limits">
          <Input.CurrencySelect
            defaultValue={currency}
            disabled
            parentQuerySelector=".Modal-mask--payment-pages-v3-creator .Modal-container"
          />

          <Input
            setRef={this.setRefMinAmountLimit}
            name="min_amount"
            defaultValue={minAmount}
            type="number"
            placeholder="0.00"
            validator={this.validateMinAmountLimit}
          >
            <span class="Input-after">Min</span>
          </Input>
        </div>

        <span class="separator">-</span>

        <div class="Input-content Input-content--limits">
          <Input.CurrencySelect
            defaultValue={currency}
            disabled
            parentQuerySelector=".Modal-mask--payment-pages-v3-creator .Modal-container"
          />

          <Input
            setRef={this.setRefMaxAmountLimit}
            name="max_amount"
            defaultValue={maxAmount}
            type="number"
            placeholder="No Limit"
            validator={this.validateMaxAmountLimit}
          >
            <span class="Input-after">Max</span>
          </Input>
        </div>
      </Input.Group>
    );
  }

  validateMinPurchaseLimit = minVal => {
    const maxVal = this.maxPurchaseLimit && this.maxPurchaseLimit.value;

    if (minVal === '') {
      return;
    }

    if (minVal < 0) {
      return 'Min purchase cannot be 0';
    }

    if (maxVal && Number(minVal) > Number(maxVal)) {
      return 'Min purchase is more than Max limit';
    }
  };

  validateMaxPurchaseLimit = maxVal => {
    const minVal = this.minPurchaseLimit && this.minPurchaseLimit.value;

    if (maxVal === '') {
      return;
    }

    if (Number(maxVal) <= 0) {
      return 'Max purchase cannot be 0';
    }

    if (minVal && Number(maxVal) < Number(minVal)) {
      return 'Max purchase is less than Min limit';
    }
  };

  get FIELD_purchaseLimits() {
    const { field } = this.props;
    const minPurchase = field.min_purchase || '';
    const maxPurchase = field.max_purchase || '';

    return (
      <Input.Group class="InputGroup--inline  Input--vTop Input--limits">
        <div class="Input-label">
          Quantity Limit
          <div class="modal-description">per order</div>
        </div>

        <div class="Input-content Input-content--limits">
          <Input
            setRef={this.setRefMinPurchaseLimit}
            name="min_purchase"
            defaultValue={minPurchase}
            pattern="\d+"
            placeholder="0"
            validator={this.validateMinPurchaseLimit}
          >
            <span class="Input-after">Min</span>
          </Input>
        </div>

        <span class="separator">-</span>

        <div class="Input-content Input-content--limits">
          <Input
            setRef={this.setRefMaxPurchaseLimit}
            name="max_purchase"
            defaultValue={maxPurchase}
            max="500000"
            pattern="\d+"
            placeholder="No Limit"
            validator={this.validateMaxPurchaseLimit}
          >
            <span class="Input-after">Max</span>
          </Input>
        </div>
      </Input.Group>
    );
  }

  get fieldsForFieldType() {
    const fieldType = this.fieldType;

    switch (fieldType) {
      // Same Advanced Form for both fixed_price and fixed_price_optional
      case FIELD_TYPES.fixed_price.key:
      case FIELD_TYPES.fixed_price_optional.key:
        return this.FIELD_availableQuantity;

      case FIELD_TYPES.dynamic_price.key:
        return (
          <React.Fragment>
            {this.FIELD_availableQuantity}
            {this.FIELD_amountLimits}
          </React.Fragment>
        );
      case FIELD_TYPES.multiple_purchase.key:
        return (
          <React.Fragment>
            {this.FIELD_availableQuantity}
            {this.FIELD_purchaseLimits}
          </React.Fragment>
        );
    }
  }

  setRefForm = el => (this.formEl = el);

  setRefMinAmountLimit = el => (this.minAmountLimit = el);
  setRefMaxAmountLimit = el => (this.maxAmountLimit = el);

  setRefMinPurchaseLimit = el => (this.minPurchaseLimit = el);
  setRefMaxPurchaseLimit = el => (this.maxPurchaseLimit = el);

  render() {
    const { field, onCloseForm } = this.props;
    const { disableSubmit } = this.state;

    return (
      <div>
        <div class="modal-title">Advanced Options</div>
        <div class="modal-description">
          Add quantity, define rules around quantity and price, etc.
        </div>

        <hr />

        <Form
          setRef={this.setRefForm}
          onChange={this.onChange}
          onSubmit={this.onSaveForm}
        >
          {this.fieldsForFieldType}

          <footer>
            <Button.Transparent class="" type="button" onClick={onCloseForm}>
              Cancel
            </Button.Transparent>

            <Button.Primary type="submit" disabled={disableSubmit}>
              Add
            </Button.Primary>
          </footer>
        </Form>
      </div>
    );
  }
}
