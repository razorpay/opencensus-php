import Button from 'component/Button';
import FieldsDropdown from '../../FieldsDropdown';
import { getFieldTypes } from '../../Amount_Fields/V3';
import { BaseFormModal } from './CreatorManager';

export default class AddAmountButton extends React.PureComponent {
  state = { isBaseFormOpened: false };

  toggleBaseForm = forcedState => {
    const isBaseFormOpened =
      typeof forcedState !== 'undefined'
        ? forcedState
        : !this.state.isBaseFormOpened;

    const newState = {
      isBaseFormOpened,
    };

    if (!isBaseFormOpened) {
      newState.field_type_key = null;
    }

    this.setState(newState);
  };

  onSelectFieldType = field => {
    this.setState({
      field_type_key: field.label,
    });

    this.toggleBaseForm(true);
  };

  render() {
    const {
      validateSameTitleExists,
      onDeleteAmountField,
      onSubmitAmountField,
    } = this.props;

    const { field_type_key, isBaseFormOpened } = this.state;

    return (
      <React.Fragment>
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

        {isBaseFormOpened && (
          <BaseFormModal
            field_type_key={field_type_key}
            validateSameTitleExists={validateSameTitleExists}
            onSubmitAmountField={onSubmitAmountField}
            onDeleteAmountField={onDeleteAmountField}
            closeFormModal={_ => this.toggleBaseForm(false)}
            isFieldRemovable
          />
        )}
      </React.Fragment>
    );
  }
}

export const AmountDropdown = ({
  children,
  onSelect,
  selectedOption,
  beforeOptionsTxt,
}) => (
  <FieldsDropdown
    beforeOptionsTxt={beforeOptionsTxt}
    type="amount"
    options={getFieldTypes()}
    trigger={children}
    onSelect={onSelect}
    selectedOption={selectedOption && selectedOption.label}
  />
);
