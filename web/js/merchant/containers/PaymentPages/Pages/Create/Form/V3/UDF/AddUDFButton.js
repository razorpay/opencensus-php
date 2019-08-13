import Button from 'component/Button';
import FieldsDropdown from '../../Fields/FieldsDropdown';
import { getFieldTypes } from '../../Fields/V3';

export default ({ onSelectField }) => (
  <UDFDropdown onSelectField={onSelectField} beforeOptionsTxt="New Input Field">
    <Button.Transparent class="btn-dotted">
      <span class="enclose-circle icon i-alphabet i-fix-alphabet" />{' '}
      <span>
        <b>Input field</b>
      </span>
    </Button.Transparent>
  </UDFDropdown>
);

export const UDFDropdown = ({ children, selectedOption, beforeOptionsTxt }) => (
  <FieldsDropdown
    beforeOptionsTxt={beforeOptionsTxt}
    type="udf"
    options={getFieldTypes()}
    selectedOption={selectedOption && selectedOption.label}
  >
    {children}
  </FieldsDropdown>
);
