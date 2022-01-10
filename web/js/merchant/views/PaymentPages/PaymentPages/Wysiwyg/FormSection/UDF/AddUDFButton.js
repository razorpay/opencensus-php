import React from 'react';
import RTracking from 'react-tracking';

import Button from 'common/new-ui/Button';
import FieldsDropdownWrapper from '../FieldsDropdown';
import CreatorManager from './CreatorManager';

import track from '../../track';
import { getFieldTypes } from '../UDF/helpers';

@RTracking(() => window.rzpQ.component('AddUDFButton'))
class AddUDFButton extends React.PureComponent {
  onSelectFieldType = (field) => {
    track.wysiwyg.chosenInputField();

    this.props.openBaseForm(field.schema);
  };

  trackInputField = () => {
    track.wysiwyg.addInputField();
  };

  render() {
    return (
      <UDFDropdown onSelect={this.onSelectFieldType} beforeOptionsTxt="Select Input Type">
        <Button.Transparent class="btn-dotted" onClick={this.trackInputField}>
          <span class="enclose-circle icon i-alphabet i-fix-alphabet" />{' '}
          <span>
            <b>Input field</b>
          </span>
        </Button.Transparent>
      </UDFDropdown>
    );
  }
}

// eslint-disable-next-line babel/new-cap
export default CreatorManager(AddUDFButton);

export const UDFDropdown = ({ children, onSelect, selectedOption, beforeOptionsTxt }) => (
  <FieldsDropdownWrapper
    beforeOptionsTxt={beforeOptionsTxt}
    type="udf"
    options={getFieldTypes()}
    trigger={children}
    onSelect={onSelect}
    selectedOption={selectedOption && selectedOption.label}
  />
);
