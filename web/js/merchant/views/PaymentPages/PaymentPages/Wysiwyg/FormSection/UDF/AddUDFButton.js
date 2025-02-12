import React from 'react';
import rTracking from 'react-tracking';

import Button from 'common/new-ui/Button';
import FieldsDropdownWrapper from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/FieldsDropdown';
import CreatorManager from './CreatorManager';

import track from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/track';
import {
  getFieldTypes,
  checkIsMagicCheckoutField,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers';
import fieldUnits from './helpers/field-units';
import { compose } from 'redux';

class AddUDFButton extends React.PureComponent {
  onSelectFieldType = (field) => {
    track.wysiwyg.chosenInputField();

    const { openBaseForm, isMagicCheckoutEnabled, updateMagicData } = this.props;
    if (isMagicCheckoutEnabled && checkIsMagicCheckoutField(field.label)) {
      updateMagicData({ formModalOpen: true });
    } else {
      openBaseForm(field.schema);
    }
  };

  getOptions = () => {
    const { isBatchPaymentPages, countryCode } = this.props;
    let filteredOptions = getFieldTypes(false, countryCode);

    if (isBatchPaymentPages) {
      // Remove dropdown from the options.
      filteredOptions = filteredOptions.filter((option) => {
        return option.label !== fieldUnits.dropdown.label;
      });
    }

    return filteredOptions;
  };

  trackInputField = () => {
    track.wysiwyg.addInputField();
  };

  render() {
    return (
      <UDFDropdown
        onSelect={this.onSelectFieldType}
        beforeOptionsTxt="Select Input Type"
        options={this.getOptions()}
      >
        <Button.Transparent className="btn-dotted" onClick={this.trackInputField}>
          <span className="enclose-circle icon i-alphabet i-fix-alphabet" />{' '}
          <span>
            <b>Input field</b>
          </span>
        </Button.Transparent>
      </UDFDropdown>
    );
  }
}

export default compose(
  rTracking(() => window.rzpQ.component('AddUDFButton')),
  CreatorManager,
)(AddUDFButton);

export const UDFDropdown = ({ children, onSelect, selectedOption, beforeOptionsTxt, options }) => (
  <FieldsDropdownWrapper
    beforeOptionsTxt={beforeOptionsTxt}
    type="udf"
    options={options}
    trigger={children}
    onSelect={onSelect}
    selectedOption={selectedOption && selectedOption.label}
  />
);
