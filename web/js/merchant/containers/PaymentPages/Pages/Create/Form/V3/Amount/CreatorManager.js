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
              validateSameTitleExists={validateSameTitleExists}
              onSubmitAmountField={onSubmitAmountField}
              onDeleteAmountField={onDeleteAmountField}
              closeFormModal={_ => this.toggleBaseForm(false)}
              isFieldRemovable={isFieldRemovable}
            />
          )}

          {this.state.isAdvancedFormOpened && (
            <CreatorModal>
              <AdvancedForm
                field={field}
                closeFormModal={_ => this.toggleAdvancedForm(false)}
                onSubmit={this.onSubmitAdvancedForm}
              />
            </CreatorModal>
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
      field_schema,
      index,
      validateSameTitleExists,
      closeFormModal,
      isFieldRemovable,
    } = this.props;

    return (
      <CreatorModal class="CreatorModal-BaseForm" overElement>
        <BaseForm
          field={field || field_schema}
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
  onSubmitAmountField = formData => {
    this.props.onSubmit(formData);
    this.props.closeFormModal();
  };

  render() {
    const { field, index, closeFormModal } = this.props;

    return (
      <CreatorModal class="CreatorModal-AdvancedForm">
        <AdvancedForm
          field={field}
          selfIndex={index}
          onCloseForm={closeFormModal}
          onSaveField={this.onSubmitAmountField}
        />
      </CreatorModal>
    );
  }
}
