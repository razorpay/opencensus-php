import React from 'react';

import PreviewFormShell from 'merchant/views/PaymentButton/PaymentButton/Create/components/Preview/PreviewFormShell';
import {
  DynamicAmount,
  FixedAmount,
  FixedAmountWithQuantity,
} from 'merchant/views/PaymentButton/PaymentButton/Create/components/Form/AmountDetails/FieldTypesRepresentations';

import { getCurrency } from 'common/ui/Amount';
import FIELD_TYPES_MAP from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/fieldTypes';
import { mapFieldToAmountFieldType } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';
import { templateTypes } from 'merchant/views/PaymentButton/PaymentButton/Create/components/Templates/meta';
import { getCurrencyConfig } from 'common/utils/rzp-utils';

export default class AmountDetailsPreview extends React.Component {
  getDummyAmountInputField(field) {
    const { paymentButtonEntity } = this.props;
    const currency = paymentButtonEntity.currency;
    const currencySymbol = getCurrency(currency).symbol;
    const { decimals } = getCurrencyConfig(currency);

    const amount = field.item.amount;
    let amountToDisplay;

    if (amount) {
      const _amount = Number(amount).toFixed(decimals);
      const _amountToDisplay = _amount.split('.');

      amountToDisplay = (
        <React.Fragment>
          {_amountToDisplay[0]}
          <span className="currency-decimal">.{_amountToDisplay[1]}</span>
        </React.Fragment>
      );
    }

    return (
      <React.Fragment>
        <div className="Field-label">{field.item.name}</div>
        {/* TODO: add dropdown icon for dropdown field */}
        <div className="Field-el">
          <span className="Field-el-addon">{currencySymbol}</span> {amountToDisplay}
        </div>
        <div className="Field-description">{field.item.description}</div>
      </React.Fragment>
    );
  }

  getAmountField(field) {
    const { paymentButtonEntity, user } = this.props;
    const currency = paymentButtonEntity.currency;
    const fieldType = mapFieldToAmountFieldType(field);
    const countryCode = user.merchant.country_code;
    const FIELD_TYPES = FIELD_TYPES_MAP[countryCode];

    switch (fieldType) {
      case FIELD_TYPES.fixed_price.key:
        return (
          <FixedAmount isMandatory={field.mandatory} key={field.item.name}>
            {this.getDummyAmountInputField(field)}
          </FixedAmount>
        );

      case FIELD_TYPES.dynamic_price.key:
        return (
          <DynamicAmount
            field={field}
            currency={currency}
            key={field.item.name}
            isDonationsTemplate={this.isDonationsTemplate}
          >
            {this.getDummyAmountInputField(field, true)}
          </DynamicAmount>
        );

      case FIELD_TYPES.multiple_purchase.key:
        return (
          <FixedAmountWithQuantity field={field} key={field.item.name}>
            {this.getDummyAmountInputField(field)}
          </FixedAmountWithQuantity>
        );

      default:
        return '';
    }
  }

  get isDonationsTemplate() {
    const { paymentButtonEntity } = this.props;
    const templateType = paymentButtonEntity.settings.payment_button_template_type;

    return templateType === templateTypes.donation.key;
  }

  render() {
    const { amountFields } = this.props;

    let previewContent;

    if (amountFields && amountFields.length) {
      previewContent = amountFields.map((field, index) => (
        <div className="Field--dummy Field--dummy--amount" key={index}>
          {this.getAmountField(field)}
        </div>
      ));
    } else {
      previewContent = (
        <span className="empty-msg page-center">Add amount fields to see their preview</span>
      );
    }

    return (
      <PreviewFormShell type="amount-details" buttonTitle="Next" {...this.props}>
        <div>
          {/* TODO: Check for asterisk/optional RazorX experiment */}
          {previewContent}
        </div>
      </PreviewFormShell>
    );
  }
}
