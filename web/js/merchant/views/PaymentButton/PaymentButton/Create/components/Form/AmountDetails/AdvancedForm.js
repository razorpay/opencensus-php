import React from 'react';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import EditorModal from 'merchant/views/PaymentButton/PaymentButton/Create/components/Form/components/EditorModal';

import { getCurrency } from 'common/ui/Amount';
import { i18CurrencyConversionFromMinorUnitToCommonUnit } from 'common/utils/rzp-utils';
import FIELD_TYPES from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/fieldTypes';

export default class AdvancedForm extends React.PureComponent {
  state = {
    disableSubmit: false,
  };

  handleSubmit = (formData) => {
    this.props.onSubmit(formData);

    this.props.handleClose();
  };

  handleChange = () => {
    setTimeout(this.toggleSubmitBtn); // Validate form for input errors via class change in DOM, hence delayed.
  };

  toggleSubmitBtn = () => {
    const disableSubmit = !!this.formEl.querySelectorAll('.is-invalid').length;

    this.setState({ disableSubmit });
  };

  get formFooter() {
    const { disableSubmit } = this.state;

    return (
      <div class="CreatorModal-AdvancedForm-footer">
        <Button type="button" class="Button--primary--invert" onClick={this.props.handleClose}>
          Cancel
        </Button>

        <Button.Primary type="submit" disabled={disableSubmit}>
          Save
        </Button.Primary>
      </div>
    );
  }

  get fieldsForFieldType() {
    const { field, fieldType, currency } = this.props;

    switch (fieldType) {
      // Same Advanced Form for both fixed_price
      case FIELD_TYPES.fixed_price.key:
        return <FieldWithStockLimit field={field} currency={currency} />;

      case FIELD_TYPES.dynamic_price.key:
        return (
          <React.Fragment>
            <FieldWithStockLimit field={field} currency={currency} />
            <FieldWithAmountLimits field={field} currency={currency} />
          </React.Fragment>
        );
      case FIELD_TYPES.multiple_purchase.key:
        return (
          <React.Fragment>
            <FieldWithStockLimit field={field} currency={currency} />
            <FieldWithPurchaseLimits field={field} currency={currency} />
          </React.Fragment>
        );
      default:
        return <></>;
    }
  }

  setRefForm = (el) => (this.formEl = el);

  render() {
    return (
      <EditorModal class="CreatorModal-AdvancedForm" overElement>
        <div class="CreatorModal-AdvancedForm-title">ADVANCED OPTIONS</div>

        <Form onSubmit={this.handleSubmit} onChange={this.handleChange} setRef={this.setRefForm}>
          {this.fieldsForFieldType}

          {this.formFooter}
        </Form>
      </EditorModal>
    );
  }
}

/*
 *
 *
 * */
class FieldWithPurchaseLimits extends React.Component {
  state = {
    hasPurchaseLimits:
      // eslint-disable-next-line no-unneeded-ternary
      this.props.field.min_purchase || this.props.field.max_purchase ? true : false, // Assuming that 0 a value is not allowed
  };

  get minPurchaseAllowed() {
    const isFieldMandatory = this.props.field.mandatory;
    const minPurchaseAllowed = isFieldMandatory ? 1 : 0;

    return minPurchaseAllowed;
  }

  toggleAddPurchaseLimit = () => {
    this.setState((prevState) => ({
      hasPurchaseLimits: !prevState.hasPurchaseLimits,
    }));
  };

  validateMinPurchaseLimit = (minVal) => {
    const maxVal = this.maxPurchaseLimit && this.maxPurchaseLimit.value;
    const stockLimit = this.stockLimit && this.stockLimit.value;
    const isFieldMandatory = this.props.field.mandatory;

    // min_purchase is allowed to be '' or 0 only when item is not mandatory
    if (minVal === '' && !isFieldMandatory) {
      return '';
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
    return '';
  };

  validateMaxPurchaseLimit = (maxVal) => {
    const minVal = this.minPurchaseLimit && this.minPurchaseLimit.value;
    const stockLimit = this.stockLimit && this.stockLimit.value;

    if (maxVal === '') {
      return '';
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
    return '';
  };

  setRefMinPurchaseLimit = (el) => (this.minPurchaseLimit = el);
  setRefMaxPurchaseLimit = (el) => (this.maxPurchaseLimit = el);

  render() {
    const { field } = this.props;

    const minPurchase = field.min_purchase || '';
    const maxPurchase = field.max_purchase || '';

    const { hasPurchaseLimits } = this.state;

    return (
      <Input.Group>
        <Input.Check
          fieldLabel="Limit quantity per order"
          onChange={this.toggleAddPurchaseLimit}
          defaultChecked={hasPurchaseLimits}
        />

        {hasPurchaseLimits && (
          <Input.Group class="InputGroup--inline Input--limits">
            <div class="Input--limits-content">
              <Input
                setRef={this.setRefMinPurchaseLimit}
                name="min_purchase"
                defaultValue={minPurchase}
                pattern="\d+"
                placeholder={this.minPurchaseAllowed}
                validator={this.validateMinPurchaseLimit}
                addonAfter="Min"
                autoFocus
              />
            </div>

            <span class="separator">-</span>

            <div class="Input--limits-content">
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
        )}
      </Input.Group>
    );
  }
}

/*
 *
 *
 * */
export class FieldWithAmountLimits extends React.Component {
  state = {
    hasAmountLimits: this.props.field.minimum || this.props.field.maximum, // Assuming that 0 as minimum is not allowed
  };

  get minAmountAllowed() {
    const { currency } = this.props;
    return i18CurrencyConversionFromMinorUnitToCommonUnit(
      getCurrency(currency).min_value,
      currency,
    );
  }

  toggleAddAmountLimits = () => {
    this.setState((prevState) => ({
      hasAmountLimits: !prevState.hasAmountLimits,
    }));
  };

  validateMinAmountLimit = (minVal) => {
    const maxVal = this.maxAmountLimit && this.maxAmountLimit.value;
    const isFieldMandatory = this.props.field.mandatory;

    // min_amount is allowed to be '' or 0 only when item is not mandatory
    if (minVal === '' && !isFieldMandatory) {
      return '';
    }

    if (Number(minVal) < Number(this.minAmountAllowed)) {
      return `Min amount must be at least ${this.minAmountAllowed}`;
    }

    if (maxVal && Number(minVal) > Number(maxVal)) {
      return 'Min amount must be less than Max amount';
    }
    return '';
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

  setRefMinAmountLimit = (el) => (this.minAmountLimit = el);
  setRefMaxAmountLimit = (el) => (this.maxAmountLimit = el);

  render() {
    const { field, currency, toggleButtonText } = this.props;

    const minAmount = field.min_amount || '';
    const maxAmount = field.max_amount || '';

    const { hasAmountLimits } = this.state;

    let togglerContent;

    if (toggleButtonText) {
      if (!hasAmountLimits) {
        togglerContent = (
          <Button.Transparent type="button" onClick={this.toggleAddAmountLimits}>
            <b>{toggleButtonText}</b>
          </Button.Transparent>
        );
      }
    } else {
      togglerContent = (
        <Input.Check
          fieldLabel="Limit the amount per order"
          defaultChecked={hasAmountLimits}
          onChange={this.toggleAddAmountLimits}
        />
      );
    }

    return (
      <Input.Group>
        {togglerContent}
        {hasAmountLimits && (
          <Input.Group class="InputGroup--inline Input--limits">
            <div class="Input--limits-content">
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
                autoFocus
              />
            </div>

            <span class="separator">-</span>

            <div class="Input--limits-content">
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

          // TODO: Add delete button
        )}
      </Input.Group>
    );
  }
}

/*
 *
 *
 * */
class FieldWithStockLimit extends React.Component {
  state = {
    hasStockLimit: this.props.field.stock != null,
  };

  toggleAddStock = () => {
    this.setState((prevState) => ({
      hasStockLimit: !prevState.hasStockLimit,
    }));
  };

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
    return '';
  };

  setRefStockLimit = (el) => (this.stockLimit = el);

  render() {
    const { field } = this.props;

    const { hasStockLimit } = this.state;

    return (
      <Input.Group>
        <Input.Check
          class="Input--vTop"
          fieldLabel="Item has Limited stock"
          onChange={this.toggleAddStock}
          defaultChecked={hasStockLimit}
        />
        {hasStockLimit && (
          <Input
            name="stock"
            setRef={this.setRefStockLimit}
            placeholder="Add stock availability here"
            step="1"
            pattern="\d+"
            validator={this.validateStockLimit}
            defaultValue={field.stock || ''}
            autoFocus
          />
        )}
      </Input.Group>
    );
  }
}
