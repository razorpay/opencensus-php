import Button from 'component/Button';
import FieldsDropdown from '../../Fields/FieldsDropdown';
import { getFieldTypes, mapFieldToIndex } from '../../Fields/V3';
import { BaseFormModal } from './CreatorManager';

export default class AddUDFButton extends React.PureComponent {
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
      newState.field_type = null;
    }

    this.setState(newState);
  };

  onSelectFieldType = field => {
    this.setState({
      field_type: mapFieldToIndex(field.schema),
    });

    this.toggleBaseForm(true);
  };

  setRef = el => (this.addUDFButtonEl = el);

  render() {
    const {
      validateSameTitleExists,
      onDeleteUDFField,
      onSubmitUDFField,
    } = this.props;

    const { field_type, isBaseFormOpened } = this.state;

    return (
      <React.Fragment>
        <UDFDropdown
          onSelect={this.onSelectFieldType}
          beforeOptionsTxt="New Input Field"
        >
          <Button.Transparent class="btn-dotted" setRef={this.setRef}>
            <span class="enclose-circle icon i-alphabet i-fix-alphabet" />{' '}
            <span>
              <b>Input field</b>
            </span>
          </Button.Transparent>
        </UDFDropdown>

        {isBaseFormOpened && (
          <BaseFormModal
            field_type={field_type}
            validateSameTitleExists={validateSameTitleExists}
            onSubmitUDFField={onSubmitUDFField}
            onDeleteUDFField={onDeleteUDFField}
            overWhatElement={this.addUDFButtonEl}
            closeBaseFormModal={_ => this.toggleBaseForm(false)}
            isFieldRemovable
          />
        )}
      </React.Fragment>
    );
  }
}

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
