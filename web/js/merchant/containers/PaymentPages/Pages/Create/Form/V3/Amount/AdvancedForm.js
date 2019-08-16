import FIELD_TYPES from '../../Amount_Fields/fieldTypes';
import { mapFieldToAmountFieldType } from '../../Amount_Fields/V3';

export default class AdvancedForm extends React.PureComponent {
  constructor(props) {
    super(props);
    const field = props.field;

    this.fieldType = props.fieldType || mapFieldToAmountFieldType(field);
  }

  onSave = formData => {
    // console.log('formData...', formData);
    this.props.onSave(formData);
  };

  render() {
    return <div />;
  }
}
