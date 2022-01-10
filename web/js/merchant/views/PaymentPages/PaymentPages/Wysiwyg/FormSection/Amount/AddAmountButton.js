import React from 'react';

import Button from 'common/new-ui/Button';
import FieldsDropdownWrapper from '../FieldsDropdown';
import CreatorManager from './CreatorManager';

import { getAmountFieldTypes, getBaseFieldForAmountFieldType } from '../Amount/helpers';
import track from '../../track';

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
    return (
      <AmountDropdown onSelect={this.onSelectFieldType} beforeOptionsTxt="Select Amount Type">
        <Button.Transparent class="btn-dotted" onClick={this.onClickPriceField}>
          <span class="enclose-circle">
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

export const AmountDropdown = ({ children, onSelect, selectedOption, beforeOptionsTxt }) => (
  <FieldsDropdownWrapper
    beforeOptionsTxt={beforeOptionsTxt}
    type="amount"
    options={getAmountFieldTypes()}
    trigger={children}
    onSelect={onSelect}
    selectedOption={selectedOption && selectedOption.label}
    showInfo
  />
);
