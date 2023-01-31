import React from 'react';

import Button from 'common/new-ui/Button';
import FieldsDropdownWrapper from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/FieldsDropdown';
import CreatorManager from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/CreatorManager';

import {
  getAmountFieldTypes,
  getBaseFieldForAmountFieldType,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';
import track from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/track';

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
    track.wysiwyg.addPriceField();
  };

  render() {
    const { hideDynamicPriceField } = this.props;
    return (
      <AmountDropdown
        onSelect={this.onSelectFieldType}
        beforeOptionsTxt="Select Amount Type"
        hideDynamicPriceField={hideDynamicPriceField}
      >
        <Button.Transparent className="btn-dotted" onClick={this.onClickPriceField}>
          <span className="enclose-circle">
            <b>₹</b>
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
}) => (
  <FieldsDropdownWrapper
    beforeOptionsTxt={beforeOptionsTxt}
    type="amount"
    options={getAmountFieldTypes(hideDynamicPriceField)}
    trigger={children}
    onSelect={onSelect}
    selectedOption={selectedOption && selectedOption.label}
    showInfo
  />
);
