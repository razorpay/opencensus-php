import Button from 'component/Button';
import FieldsDropdown from '../Fields/FieldsDropdown';
import { getFieldTypes } from '../Fields/V3';

export default ({ onSelectField, selectedOption }) => (
  <UDFDropdown onSelectField={onSelectField} selectedOption={selectedOption}>
    <Button.Transparent class="btn-dotted">
      <span class="enclose-circle icon i-alphabet i-fix-alphabet" />{' '}
      <span>
        <b>Input field</b>
      </span>
    </Button.Transparent>
  </UDFDropdown>
);

export const UDFDropdown = ({ children, selectedOption }) => (
  <FieldsDropdown
    type="udf"
    options={getFieldTypes()}
    selectedOption={selectedOption && selectedOption.label}
  >
    {children}
  </FieldsDropdown>
);
