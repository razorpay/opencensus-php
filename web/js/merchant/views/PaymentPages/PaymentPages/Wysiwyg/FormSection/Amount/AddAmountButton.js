import React from 'react';

import Button from 'common/new-ui/Button';
import FieldsDropdownWrapper from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/FieldsDropdown';
import CreatorManager from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/CreatorManager';

import {
  getAmountFieldTypes,
  getBaseFieldForAmountFieldType,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';
import track from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/track';
import { getCurrencySymbol } from 'common/ui/Amount';
import FIELD_TYPES_MAP from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/fieldTypes';

class AddAmountButton extends React.PureComponent {
  onSelectFieldType = (fieldType) => {
    track.wysiwyg.chosenPriceField();

    const initWithField = {
      ...getBaseFieldForAmountFieldType(fieldType.key),
      ...this.props.field,
    };

    this.props.openBaseForm(fieldType.key, initWithField);
  };

  onClickPriceField = () => {
    const { isBatchPaymentPages, countryCode } = this.props;
    track.wysiwyg.addPriceField();

    const FIELD_TYPES = FIELD_TYPES_MAP[countryCode];
    // If "Batch Payment Pages flow then don't allow to select input fileds. By defalut dynamic price filed should be selected"
    if (isBatchPaymentPages) {
      const option = FIELD_TYPES.dynamic_price;
      this.onSelectFieldType(option);
    }
  };

  render() {
    const { hideDynamicPriceField, currency, isBatchPaymentPages, countryCode } = this.props;
    if (isBatchPaymentPages) {
      return (
        <Button.Transparent className="btn-dotted" onClick={this.onClickPriceField}>
          <span className="enclose-circle">
            <b>{getCurrencySymbol(currency)}</b>
          </span>{' '}
          <span>
            <b>Price field</b>
          </span>
        </Button.Transparent>
      );
    }
    return (
      <AmountDropdown
        onSelect={this.onSelectFieldType}
        beforeOptionsTxt="Select Amount Type"
        hideDynamicPriceField={hideDynamicPriceField}
        countryCode={countryCode}
      >
        <Button.Transparent className="btn-dotted" onClick={this.onClickPriceField}>
          <span className="enclose-circle">
            <b>{getCurrencySymbol(currency)}</b>
          </span>{' '}
          <span>
            <b>Price field</b>
          </span>
        </Button.Transparent>
      </AmountDropdown>
    );
  }
}

// eslint-disable-next-line babel/new-cap
export default CreatorManager(AddAmountButton);

export const AmountDropdown = ({
  children,
  onSelect,
  selectedOption,
  beforeOptionsTxt,
  hideDynamicPriceField,
  countryCode,
}) => (
  <FieldsDropdownWrapper
    beforeOptionsTxt={beforeOptionsTxt}
    type="amount"
    options={getAmountFieldTypes(hideDynamicPriceField, countryCode)}
    trigger={children}
    onSelect={onSelect}
    selectedOption={selectedOption && selectedOption.label}
    showInfo
  />
);
