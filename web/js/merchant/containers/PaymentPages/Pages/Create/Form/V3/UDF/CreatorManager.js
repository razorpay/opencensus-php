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

      // Checking if intent is not event
      if (intentSchema && !intentSchema.hasOwnProperty('target')) {
        newState.fieldSchema = intentSchema;
      }

      this.setState(newState);
    };

    render() {
      const {
        field,
        index,
        validateSameTitleExists,
        onDeleteFormItem,
        onSubmitUDFField,
        ...restProps
      } = this.props;

      let isFieldDeletable = true,
        isFieldForcedRequired = false; // If so, then no option in dropdown to set the field optional.

      if (field) {
        // TODO: In V3, title of Email and Phone field can be modified. But don't allow name to get modified for those 2 fields
        // TODO: Improve this logic, if the phone/email label is changed, then name is also is changed, so condition will have to change
        if (field.name === 'email' || field.name === 'phone') {
          isFieldDeletable = false;
          isFieldForcedRequired = true; // Email and Phone cannot be made as Optional field
        }
      }

      return (
        <div class="CreatorManager">
          <_WrappedDisplayFieldComponent
            field={field}
            openBaseForm={this.openBaseForm}
            {...restProps}
          />
          {this.state.isBaseFormOpened && (
            <BaseFormModal
              index={index}
              field={field || this.state.fieldSchema}
              validateSameTitleExists={validateSameTitleExists}
              onSubmitUDFField={onSubmitUDFField}
              onDeleteFormItem={onDeleteFormItem}
              closeFormModal={this.closeBaseForm}
              isFieldDeletable={isFieldDeletable}
              isFieldForcedRequired={isFieldForcedRequired}
            />
          )}
        </div>
      );
    }
  }

  return HOC;
}

class BaseFormModal extends React.PureComponent {
  onSaveForm = formData => {
    this.props.onSubmitUDFField(formData, this.props.index);
    this.props.closeFormModal();
  };

  onDeleteFormItem = () => {
    this.props.onDeleteFormItem(this.props.index);
    this.props.closeFormModal();
  };

  render() {
    const {
      field,
      index,
      validateSameTitleExists,
      closeFormModal,
      isFieldDeletable,
      isFieldForcedRequired,
    } = this.props;

    return (
      <CreatorModal class="CreatorModal-BaseForm" overElement>
        <BaseForm
          field={field}
          selfIndex={index}
          validateSameTitleExists={validateSameTitleExists}
          onCloseForm={closeFormModal}
          onSaveForm={this.onSaveForm}
          onDeleteField={isFieldDeletable ? this.onDeleteFormItem : undefined}
          isFieldForcedRequired={isFieldForcedRequired}
        />
      </CreatorModal>
    );
  }
}
