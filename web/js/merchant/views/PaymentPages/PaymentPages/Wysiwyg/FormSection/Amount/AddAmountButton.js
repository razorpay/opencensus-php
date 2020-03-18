import RTracking from 'react-tracking';

import Button from 'common/new-ui/Button';
import FieldsDropdown from '../FieldsDropdown';
import {
  getAmountFieldTypes,
  getBaseFieldForAmountFieldType,
} from '../Amount/helpers';
import CreatorManager from './CreatorManager';

@RTracking(() => window.rzpQ.component('AddAmountButton'))
class AddAmountButton extends React.PureComponent {
  onSelectFieldType = fieldType => {
    const initWithField = {
      ...getBaseFieldForAmountFieldType(fieldType.key),
      ...this.props.field,
    };

    this.props.openBaseForm(fieldType.key, initWithField);
  };

  onClickPriceField = () => {
    this.props.tracking.trackEvent(
      window.rzpQ.paymentPages().interaction('pp.create.field', {
        button_type: 'Price Field',
      })
    );
  };

  render() {
    return (
      <AmountDropdown
        onSelect={this.onSelectFieldType}
        beforeOptionsTxt="Select Amount Type"
      >
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
    showInfo
  />
);
