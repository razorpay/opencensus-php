import Button from 'component/Button';
import FieldsDropdown from '../../FieldsDropdown';
import { getAmountFieldTypes } from '../../Amount_Fields/V3';
import CreatorManager from './CreatorManager';

class AddAmountButton extends React.PureComponent {
  onSelectFieldType = fieldType => {
    this.props.openBaseForm(fieldType.key, this.props.field);
  };

  render() {
    return (
      <AmountDropdown
        onSelect={this.onSelectFieldType}
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
  }
}

export default CreatorManager(AddAmountButton);

export const AmountDropdown = ({
  children,
  onSelect,
  selectedOption,
  beforeOptionsTxt,
}) => (
  <FieldsDropdown
    beforeOptionsTxt={beforeOptionsTxt}
    type="amount"
    options={getAmountFieldTypes()}
    trigger={children}
    onSelect={onSelect}
    selectedOption={selectedOption && selectedOption.label}
  />
);
