import CreatorModal from '../CreatorModal';
import BaseForm from './BaseForm';
import Alert from 'common/new-ui/Alert';

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
        indexInRenderOrder,
        validateSameTitleExists,
        onDeleteFormItem,
        onSubmitUDFField,
        checkoutOptions,
        ...restProps
      } = this.props;

      let isFieldDeletable = true,
        isFieldForcedRequired = false, // If so, then no option in dropdown to set the field optional.
        isCheckoutOption = false;

      if (field) {
        if (
          [checkoutOptions.email, checkoutOptions.phone].indexOf(field.name) >
          -1
        ) {
          isCheckoutOption = true;
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
              indexInRenderOrder={indexInRenderOrder}
              field={field || this.state.fieldSchema}
              validateSameTitleExists={validateSameTitleExists}
              onSubmitUDFField={onSubmitUDFField}
              onDeleteFormItem={onDeleteFormItem}
              closeFormModal={this.closeBaseForm}
              isFieldDeletable={isFieldDeletable}
              isFieldForcedRequired={isFieldForcedRequired}
              isCheckoutOption={isCheckoutOption}
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
    this.props.onSubmitUDFField(
      formData,
      this.props.indexInRenderOrder,
      this.props.isCheckoutOption
    );
    this.props.closeFormModal();
  };

  onDeleteFormItem = () => {
    this.props.onDeleteFormItem(this.props.indexInRenderOrder);
    this.props.closeFormModal();
  };

  render() {
    const {
      field,
      indexInRenderOrder,
      validateSameTitleExists,
      closeFormModal,
      isFieldDeletable,
      isFieldForcedRequired,
    } = this.props;

    return (
      <CreatorModal class="CreatorModal-BaseForm" overElement allowScroll>
        <BaseForm
          field={field}
          selfIndex={indexInRenderOrder}
          validateSameTitleExists={validateSameTitleExists}
          onCloseForm={closeFormModal}
          onSaveForm={this.onSaveForm}
          onDeleteField={isFieldDeletable ? this.onDeleteFormItem : undefined}
          isFieldForcedRequired={isFieldForcedRequired}
        />
        {!isFieldDeletable && (
          <Alert.Warning>
            <b>Mandatory</b> {field.name} field to be filled by customers. This
            field cannot be deleted.
          </Alert.Warning>
        )}
      </CreatorModal>
    );
  }
}
