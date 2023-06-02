import React from 'react';
import { connect } from 'react-redux';

import Form from 'common/new-ui/Form';
import Button from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';

import { i18CurrencyConversionFromMinorUnitToCommonUnit } from 'common/utils/rzp-utils';

import FIELD_TYPES from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/fieldTypes';

@connect((state) => ({
  user: state.session.user,
}))
export default class AdvancedForm extends React.PureComponent {
  constructor(props) {
    super(props);

    const field = props.field;

    this.state = {
      disableSubmit: false,
      isStockSet: field.stock != null,
    };
  }

  onChange = () => {
    setTimeout(this.toggleSubmitBtn); // Validate form for input errors via class change in DOM, hence delayed.
  };

  toggleSubmitBtn = () => {
    const form = this.formEl;
    const disableSubmit = !!form.querySelectorAll('.is-invalid').length;

    this.setState({ disableSubmit });
  };

  onSaveForm = (formData) => {
    // console.log('formData...', formData);
    this.props.onSaveForm(formData, this.props.fieldType);
  };

  toggleAddStock = () => {
    this.setState((prevState) => ({
      isStockSet: !prevState.isStockSet,
    }));
  };

  get FIELD_availableStock() {
    const { isStockSet } = this.state;
    const stock = this.props.field.stock || '';

    return (
      <Input.Radio
        class="Input--isStockSet Input--vTop"
        label={() => (
          <React.Fragment>
            Units Available
            <div class="modal-description">in stock</div>
          </React.Fragment>
        )}
        onChange={this.toggleAddStock}
        options={[
          'Unlimited',
          {
            label: (
              <div class="Input--stock">
                <span>Limited</span>
                {isStockSet && (
                  <Input
                    name="stock"
                    setRef={this.setRefStockLimit}
                    defaultValue={stock}
                    autoFocus
                    step="1"
                    pattern="\d+"
                    validator={this.validateStockLimit}
                  />
                )}
              </div>
            ),
          },
        ]}
        defaultValue={isStockSet}
      />
    );
  }

  get minAmountAllowed() {
    const { currency, field } = this.props;
    const isFieldMandatory = field.mandatory;

    const minAmountInCurrency = i18CurrencyConversionFromMinorUnitToCommonUnit(
      this.props.user.getCurrencyList[currency].min_value,
      currency,
    ); // In Paisa(lower unit of currency)

    const minAmountAllowed = isFieldMandatory ? minAmountInCurrency : 0;

    return minAmountAllowed;
  }

  validateMinAmountLimit = (minVal) => {
    const maxVal = this.maxAmountLimit && this.maxAmountLimit.value;
    const isFieldMandatory = this.props.field.mandatory;

    // min_amount is allowed to be '' or 0 only when item is not mandatory
    if (minVal === '' && !isFieldMandatory) {
      return null;
    }

    if (Number(minVal) < Number(this.minAmountAllowed)) {
      return `Min amount must be at least ${this.minAmountAllowed}`;
    }

    if (maxVal && Number(minVal) > Number(maxVal)) {
      return 'Min amount must be less than Max amount';
    }

    return null;
  };

  validateMaxAmountLimit = (maxVal) => {
    const minVal = this.minAmountLimit && this.minAmountLimit.value;

    // max_amount is allowed to be ''
    if (maxVal === '') {
      return '';
    }

    if (Number(maxVal) < Number(this.minAmountAllowed)) {
      return `Max amount must be at least ${this.minAmountAllowed}`;
    }

    if (minVal && Number(maxVal) < Number(minVal)) {
      return 'Max amount must be more than Min amount';
    }

    return '';
  };

  // eslint-disable-next-line consistent-return
  validateStockLimit = (stockVal) => {
    if (stockVal === '' || Number(stockVal) <= 0) {
      return 'Stock must be at least 1';
    }

    if (this.props.fieldType === FIELD_TYPES.multiple_purchase.key) {
      const minVal = this.minPurchaseLimit && this.minPurchaseLimit.value;
      const maxVal = this.maxPurchaseLimit && this.maxPurchaseLimit.value;

      // Written before minVal comparison
      if (maxVal && Number(stockVal) < Number(maxVal)) {
        return 'Stock must be more than Max Limit';
      }

      if (minVal && Number(stockVal) < Number(minVal)) {
        return 'Stock must be more than Min Limit';
      }
    }
  };

  get FIELD_amountLimits() {
    const { field, currency } = this.props;
    const minAmount = field.min_amount || '';
    const maxAmount = field.max_amount || '';

    return (
      <Input.Group class="InputGroup--inline Input--vTop Input--limits" label="Input Price Limits">
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
            placeholder={Number(this.minAmountAllowed).toFixed(2)}
            validator={this.validateMinAmountLimit}
            addonAfter="Min"
          />
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
            addonAfter="Max"
          />
        </div>
      </Input.Group>
    );
  }

  get minPurchaseAllowed() {
    const isFieldMandatory = this.props.field.mandatory;
    const minPurchaseAllowed = isFieldMandatory ? 1 : 0;

    return minPurchaseAllowed;
  }

  validateMinPurchaseLimit = (minVal) => {
    const maxVal = this.maxPurchaseLimit && this.maxPurchaseLimit.value;
    const stockLimit = this.stockLimit && this.stockLimit.value;
    const isFieldMandatory = this.props.field.mandatory;

    // min_purchase is allowed to be '' or 0 only when item is not mandatory
    if (minVal === '' && !isFieldMandatory) {
      return null;
    }

    if (minVal < this.minPurchaseAllowed) {
      return `Min purchase must be at least ${this.minPurchaseAllowed}`;
    }

    if (maxVal && Number(minVal) > Number(maxVal)) {
      return 'Min purchase is more than Max limit';
    }

    if (stockLimit && Number(stockLimit) < Number(minVal)) {
      return 'Min purchase must be less than Units Available';
    }

    return null;
  };

  validateMaxPurchaseLimit = (maxVal) => {
    const minVal = this.minPurchaseLimit && this.minPurchaseLimit.value;
    const stockLimit = this.stockLimit && this.stockLimit.value;

    if (maxVal === '') {
      return null;
    }

    if (Number(maxVal) < this.minPurchaseAllowed) {
      return `Max purchase must be more than ${this.minPurchaseAllowed}`;
    }

    if (minVal && Number(maxVal) < Number(minVal)) {
      return 'Max purchase is less than Min limit';
    }

    if (stockLimit && Number(stockLimit) < Number(maxVal)) {
      return 'Max purchase must be less than Units Available';
    }

    return null;
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
            placeholder={this.minPurchaseAllowed}
            validator={this.validateMinPurchaseLimit}
            addonAfter="Min"
          />
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
            addonAfter="Max"
          />
        </div>
      </Input.Group>
    );
  }

  // eslint-disable-next-line getter-return, consistent-return
  get fieldsForFieldType() {
    const fieldType = this.props.fieldType;

    // eslint-disable-next-line default-case
    switch (fieldType) {
      // Same Advanced Form for both fixed_price
      case FIELD_TYPES.fixed_price.key:
        return this.FIELD_availableStock;

      case FIELD_TYPES.dynamic_price.key:
        return (
          <React.Fragment>
            {this.FIELD_availableStock}
            {this.FIELD_amountLimits}
          </React.Fragment>
        );
      case FIELD_TYPES.multiple_purchase.key:
        return (
          <React.Fragment>
            {this.FIELD_availableStock}
            {this.FIELD_purchaseLimits}
          </React.Fragment>
        );
    }
  }

  setRefForm = (el) => (this.formEl = el);

  setRefStockLimit = (el) => (this.stockLimit = el);

  setRefMinAmountLimit = (el) => (this.minAmountLimit = el);
  setRefMaxAmountLimit = (el) => (this.maxAmountLimit = el);

  setRefMinPurchaseLimit = (el) => (this.minPurchaseLimit = el);
  setRefMaxPurchaseLimit = (el) => (this.maxPurchaseLimit = el);

  render() {
    const { onCloseForm } = this.props;
    const { disableSubmit } = this.state;

    return (
      <div>
        <div class="modal-title">Advanced Options</div>
        <div class="modal-description">
          Add quantity, define rules around quantity and price, etc.
        </div>

        <hr />

        <Form setRef={this.setRefForm} onChange={this.onChange} onSubmit={this.onSaveForm}>
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
