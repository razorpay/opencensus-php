import React from 'react';

import Button from 'common/new-ui/Button';
import { getCurrencySymbol } from 'common/ui/Amount';
import CreatorManager from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/CreatorManager';
import { LATE_FEE_FIELD_TYPES } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/fieldTypes';
import FieldsDropdownWrapper from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/FieldsDropdown';

class AddLateFeeButton extends React.PureComponent {
  onSelectFieldType = (fieldType) => {
    const { openBaseForm } = this.props;
    const { key, type } = fieldType;

    const initWithField = {
      item: { name: 'Late Payment Charges' },
      mandatory: false, // always optional
      settings: {
        late_fee_config: {
          late_fee_order: 1,
          late_fee_type: type,
        },
      },
    };

    openBaseForm(key, initWithField);
  };

  render() {
    const { currency, label = 'Enable Late Payment Charge' } = this.props;

    return (
      <FieldsDropdownWrapper
        beforeOptionsTxt="Select Fee Type"
        type="amount"
        options={[LATE_FEE_FIELD_TYPES.flat_fee, LATE_FEE_FIELD_TYPES.per_day_fee]}
        trigger={
          <Button.Transparent className="btn-dotted">
            <span className="enclose-circle">
              <b>{getCurrencySymbol(currency)}</b>
            </span>
            <span>
              <b>{label}</b>
            </span>
          </Button.Transparent>
        }
        onSelect={this.onSelectFieldType}
      />
    );
  }
}

// eslint-disable-next-line babel/new-cap
export default CreatorManager(AddLateFeeButton);
