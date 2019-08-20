import CreatorModal from '../CreatorModal';
import BaseForm from './BaseForm';

export default function CreatorManager(_WrappedDisplayFieldComponent) {
  class HOC extends React.PureComponent {
    state = { isBaseFormOpened: false, fieldSchema: null };

    closeBaseForm = _ => {
      this.setState({
        isBaseFormOpened: false,
        fieldSchema: null,
      });
    };

    openBaseForm = intentSchema => {
      const newState = {
        isBaseFormOpened: true,
      };

      if (intentSchema) {
        newState.fieldSchema = intentSchema;
      }

      this.setState(newState);
    };

    render() {
      const {
        field,
        index,
        validateSameTitleExists,
        onDeleteUDFField,
        onSubmitUDFField,
        ...restProps
      } = this.props;

      let tooltipTxt,
        isFieldRemovable = true;

      if (field) {
        // TODO: In V3, title of Email and Phone field can be modified. But don't allow name to get modified for those 2 fields
        if (field.name === 'email' || field.name === 'phone') {
          tooltipTxt = 'This field cannot be removed';
          isFieldRemovable = false;
        }
      }

      return (
        <div class="CreatorManager">
          <_WrappedDisplayFieldComponent
            field={field}
            openBaseForm={this.openBaseForm}
            tooltipTxt={tooltipTxt}
            {...restProps}
          />
          {this.state.isBaseFormOpened && (
            <BaseFormModal
              index={index}
              field={field}
              fieldSchema={this.state.fieldSchema}
              validateSameTitleExists={validateSameTitleExists}
              onSubmitUDFField={onSubmitUDFField}
              onDeleteUDFField={onDeleteUDFField}
              closeFormModal={this.closeBaseForm}
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
  onSaveForm = formData => {
    this.props.onSubmitUDFField(formData, this.props.index);
    this.props.closeFormModal();
  };

  onDeleteUDFField = () => {
    this.props.onDeleteUDFField(this.props.index);
    this.props.closeFormModal();
  };

  render() {
    const {
      field,
      fieldSchema,
      index,
      validateSameTitleExists,
      closeFormModal,
      isFieldRemovable,
    } = this.props;

    return (
      <CreatorModal class="CreatorModal-BaseForm" overElement>
        <BaseForm
          field={field || fieldSchema}
          selfIndex={index}
          validateSameTitleExists={validateSameTitleExists}
          onCloseForm={closeFormModal}
          onSaveForm={this.onSaveForm}
          onDeleteField={isFieldRemovable ? this.onDeleteUDFField : undefined}
        />
      </CreatorModal>
    );
  }
}
