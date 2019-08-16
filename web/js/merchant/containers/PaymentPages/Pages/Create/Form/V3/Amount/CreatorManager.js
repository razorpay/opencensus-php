import CreatorModal from '../CreatorModal';
import BaseForm from './BaseForm';
import AdvancedForm from './AdvancedForm';

export default function CreatorManager(_WrappedDisplayFieldComponent) {
  class HOC extends React.PureComponent {
    state = { isBaseFormOpened: false };

    toggleBaseForm = forcedState => {
      this.setState({
        isBaseFormOpened:
          typeof forcedState !== 'undefined'
            ? forcedState
            : !this.state.isBaseFormOpened,
      });
    };

    render() {
      const {
        field,
        fieldType,
        index,
        validateSameTitleExists,
        onDeleteAmountField,
        onSubmitAmountField,
      } = this.props;

      let isFieldRemovable = true; // TODO: Handle condition to check atleast 1 price field is present.

      return (
        <div style={{ position: 'relative' }}>
          <_WrappedDisplayFieldComponent
            field={field}
            openBaseForm={this.toggleBaseForm}
          />
          {this.state.isBaseFormOpened && (
            <BaseFormModal
              index={index}
              field={field}
              fieldType={fieldType}
              validateSameTitleExists={validateSameTitleExists}
              onSubmitAmountField={onSubmitAmountField}
              onDeleteAmountField={onDeleteAmountField}
              closeFormModal={_ => this.toggleBaseForm(false)}
              isFieldRemovable={isFieldRemovable}
            />
          )}
        </div>
      );
    }
  }

  return HOC;
}

export class BaseFormModal extends React.PureComponent {
  onSubmitAmountField = formData => {
    this.props.onSubmitAmountField(formData, this.props.index);
    this.props.closeFormModal();
  };

  onDeleteAmountField = () => {
    this.props.onDeleteAmountField(this.props.index);
    this.props.closeFormModal();
  };

  render() {
    const {
      field,
      fieldType,
      index,
      validateSameTitleExists,
      closeFormModal,
      isFieldRemovable,
    } = this.props;

    return (
      <CreatorModal class="CreatorModal-BaseForm" overElement>
        <BaseForm
          field={field}
          fieldType={fieldType}
          selfIndex={index}
          validateSameTitleExists={validateSameTitleExists}
          onCloseForm={closeFormModal}
          onSaveField={this.onSubmitAmountField}
          onDeleteField={
            isFieldRemovable ? this.onDeleteAmountField : undefined
          }
        />
      </CreatorModal>
    );
  }
}

export class AdvancedFormModal extends React.PureComponent {
  onSave = formData => {
    this.props.onSave(formData);
    this.props.closeFormModal();
  };

  render() {
    const { field, fieldType, onSave, closeFormModal } = this.props;

    return (
      <CreatorModal class="CreatorModal-AdvancedForm">
        <AdvancedForm
          field={field}
          fieldType={fieldType}
          onCloseForm={closeFormModal}
          onSave={this.onSave}
        />
      </CreatorModal>
    );
  }
}
