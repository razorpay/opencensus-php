import RTracking from 'react-tracking';

import Button from 'common/new-ui/Button';
import FieldsDropdown from '../FieldsDropdown';
import { getFieldTypes } from '../UDF/helpers';
import CreatorManager from './CreatorManager';

@RTracking(() => window.rzpQ.component('AddUDFButton'))
class AddUDFButton extends React.PureComponent {
  onSelectFieldType = field => {
    this.props.openBaseForm(field.schema);
  };

  trackInputField = () => {
    this.props.tracking.trackEvent(
      window.rzpQ.paymentPages().interaction('pp.create.field', {
        button_type: 'Input Field',
      })
    );
  };

  render() {
    return (
      <UDFDropdown
        onSelect={this.onSelectFieldType}
        beforeOptionsTxt="Select Input Type"
      >
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

export default CreatorManager(AddUDFButton);

export const UDFDropdown = ({
  children,
  onSelect,
  selectedOption,
  beforeOptionsTxt,
}) => (
  <FieldsDropdown
    beforeOptionsTxt={beforeOptionsTxt}
    type="udf"
    options={getFieldTypes()}
    trigger={children}
    onSelect={onSelect}
    selectedOption={selectedOption && selectedOption.label}
  />
);
