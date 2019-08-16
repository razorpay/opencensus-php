import Button from 'component/Button';
import FieldsDropdown from '../../FieldsDropdown';
import { getAmountFieldTypes } from '../../Amount_Fields/V3';
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
      newState.fieldType = null;
    }

    this.setState(newState);
  };

  onSelectFieldType = field => {
    this.setState({
      fieldType: field.key,
    });

    this.toggleBaseForm(true);
  };

  render() {
    const {
      validateSameTitleExists,
      onDeleteAmountField,
      onSubmitAmountField,
    } = this.props;

    const { fieldType, isBaseFormOpened } = this.state;

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
            fieldType={fieldType}
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
    options={getAmountFieldTypes()}
    trigger={children}
    onSelect={onSelect}
    selectedOption={selectedOption && selectedOption.label}
  />
);
