import CreatorModal from '../CreatorModal';
import BaseForm from './BaseForm';

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

    sefRef = el => (this.displayFieldEl = el);

    render() {
      const {
        field,
        index,
        validateSameTitleExists,
        onDeleteUDFField,
        onSubmitUDFField,
      } = this.props;

      let tooltipTxt,
        isFieldRemovable = true;

      // TODO: In V3, title of Email and Phone field can be modified. But don't allow name to get modified for those 2 fields
      if (field.name === 'email' || field.name === 'phone') {
        tooltipTxt = 'This field cannot be removed';
        isFieldRemovable = false;
      }

      return (
        <div>
          <_WrappedDisplayFieldComponent
            field={field}
            openBaseForm={this.toggleBaseForm}
            tooltipTxt={tooltipTxt}
            setRef={this.sefRef}
          />
          {this.state.isBaseFormOpened && (
            <BaseFormModal
              index={index}
              field={field}
              validateSameTitleExists={validateSameTitleExists}
              onSubmitUDFField={onSubmitUDFField}
              onDeleteUDFField={onDeleteUDFField}
              overWhatElement={this.displayFieldEl}
              closeBaseFormModal={_ => this.toggleBaseForm(false)}
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
  onSubmitUDFField = formData => {
    this.props.onSubmitUDFField(formData, this.props.index);
    this.props.closeBaseFormModal();
  };

  onDeleteUDFField = () => {
    this.props.onDeleteUDFField(this.props.index);
    this.props.closeBaseFormModal();
  };

  render() {
    const {
      field,
      field_schema,
      selfIndex,
      validateSameTitleExists,
      overWhatElement,
      closeBaseFormModal,
      isFieldRemovable,
    } = this.props;

    return (
      <CreatorModal
        class="CreatorModal-BaseForm"
        overWhatElement={overWhatElement}
      >
        <BaseForm
          field={field || field_schema}
          selfIndex={selfIndex}
          validateSameTitleExists={validateSameTitleExists}
          onCloseForm={closeBaseFormModal}
          onSaveField={this.onSubmitUDFField}
          onDeleteField={isFieldRemovable ? this.onDeleteUDFField : undefined}
        />
      </CreatorModal>
    );
  }
}
