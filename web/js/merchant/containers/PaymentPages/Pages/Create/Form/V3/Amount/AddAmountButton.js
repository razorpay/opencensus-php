import Button from 'component/Button';
import FieldsDropdown from '../../UDF_Fields/FieldsDropdown';

export default ({ onSelectField }) => (
  <AmountDropdown
    onSelectField={onSelectField}
    beforeOptionsTxt="New Amount Field"
  >
    <Button.Transparent class="btn-dotted">
      <span class="enclose-circle">
        <b>₹</b>
      </span>{' '}
      <span>
        <b>Price field</b>
      </span>
    </Button.Transparent>
  </AmountDropdown>
);

export const AmountDropdown = ({
  children,
  selectedOption,
  beforeOptionsTxt,
}) => (
  <FieldsDropdown
    beforeOptionsTxt={beforeOptionsTxt}
    type="price"
    options={[]}
    trigger={children}
    selectedOption={selectedOption && selectedOption.label}
  />
);
