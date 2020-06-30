import { connect } from 'react-redux';

import PreviewFormShell from '../PreviewFormShell';
import {
  DynamicAmount,
  FixedAmount,
  FixedAmountWithQuantity,
} from '../../Form/AmountDetails/FieldTypesRepresentations';

import { getCurrency } from 'common/ui/Amount';
import FIELD_TYPES from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/fieldTypes';
import { mapFieldToAmountFieldType } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';

@connect(state => ({
  payment_button: state.payment_button_create,
}))
export default class AmountDetailsPreview extends React.Component {
  getDummyAmountInputField(field) {
    const { payment_button } = this.props;
    const currency = payment_button.paymentButtonEntity.currency,
      currencySymbol = getCurrency(currency).symbol;

    const amount = field.item.amount;
    let amountToDisplay;

    if (amount) {
      const _amount = Number(amount).toFixed(2);
      const _amountToDisplay = _amount.split('.');

      amountToDisplay = (
        <React.Fragment>
          {_amountToDisplay[0]}
          <span class="currency-decimal">.{_amountToDisplay[1]}</span>
        </React.Fragment>
      );
    }

    return (
      <React.Fragment>
        <div class="Field-label">{field.item.name}</div>
        {/* TODO: add dropdown icon for dropdown field */}
        <div class="Field-el">
          <span class="Field-el-addon">{currencySymbol}</span> {amountToDisplay}
        </div>
        <div class="Field-description">{field.item.description}</div>
      </React.Fragment>
    );
  }

  getAmountField(field) {
    const { payment_button } = this.props;
    const currency = payment_button.paymentButtonEntity.currency,
      fieldType = mapFieldToAmountFieldType(field);

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
    }
  }

  render() {
    const { amountFields } = this.props;

    let previewContent;

    if (amountFields && amountFields.length) {
      previewContent = amountFields.map(field => (
        <div class="Field--dummy Field--dummy--amount" key={field.item.name}>
          {this.getAmountField(field)}
        </div>
      ));
    } else {
      previewContent = (
        <span class="empty-msg page-center">
          Add amount fields to see their preview
        </span>
      );
    }

    return (
      <PreviewFormShell
        type="amount-details"
        buttonTitle="NEXT"
        {...this.props}
      >
        <div>
          {/* TODO: Check for asterisk/optional RazorX experiment */}
          {previewContent}
        </div>
      </PreviewFormShell>
    );
  }
}
