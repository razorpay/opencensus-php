import CreatorModal from '../CreatorModal';
import BaseForm from './BaseForm';
import AdvancedForm from './AdvancedForm';

export default function CreatorManager(_WrappedDisplayFieldComponent) {
  class HOC extends React.PureComponent {
    defaultField = { item: {} };

    state = this.initState;

    get initState() {
      return {
        isBaseFormOpened: false,
        isAdvancedFormOpened: false,
        fieldType: null,
        field: this.props.field || this.defaultField,
      };
    }

    closeBaseForm = _ => {
      this.setState(this.initState);
    };

    openBaseForm = (intentFieldType, field) => {
      const newState = {
        isBaseFormOpened: true,
      };

      console.log('THIS....', field);

      if (intentFieldType) {
        newState.fieldType = intentFieldType;

        if (field) {
          newState.field = field;
        }
      }

      this.setState(newState);
    };

    toggleAdvancedForm = forcedState => {
      this.setState({
        isAdvancedFormOpened:
          typeof forcedState !== 'undefined'
            ? forcedState
            : !this.state.isAdvancedFormOpened,
      });
    };

    onSaveBaseForm = formData => {
      console.log('BASE FORM...', formData);

      const combinedFormData = {
        ...this.state.field,
        ...formData,
      };

      // Combine data from advanced form
      this.props.onSubmitAmountField(formData, this.props.index);
    };

    onSaveAdvancedForm = formData => {
      this.setState({
        field: {
          ...this.state.field,
          ...formData,
        },
      });
      console.log('ADVANCED FORM...', formData);
    };

    render() {
      const {
        index,
        validateSameTitleExists,
        onDeleteAmountField,
      } = this.props;

      const {
        field,
        fieldType,
        isBaseFormOpened,
        isAdvancedFormOpened,
      } = this.state;

      let isFieldRemovable = true; // TODO: Handle condition to check atleast 1 price field is present.

      return (
        <div class="CreatorManager">
          <_WrappedDisplayFieldComponent
            field={field}
            openBaseForm={this.openBaseForm}
          />
          {isBaseFormOpened && (
            <BaseFormModal
              index={index}
              field={field}
              fieldType={fieldType}
              validateSameTitleExists={validateSameTitleExists}
              onSaveForm={this.onSaveBaseForm}
              onDeleteAmountField={onDeleteAmountField}
              closeFormModal={this.closeBaseForm}
              isFieldRemovable={isFieldRemovable}
              openAdvancedForm={_ => this.toggleAdvancedForm(true)}
            />
          )}

          {isAdvancedFormOpened && (
            <AdvancedFormModal
              field={field}
              fieldType={fieldType}
              onSaveForm={this.onSaveAdvancedForm}
              closeFormModal={_ => this.toggleAdvancedForm(false)}
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
    this.props.onSaveForm(formData);
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
      openAdvancedForm,
    } = this.props;

    return (
      <CreatorModal class="CreatorModal-BaseForm" overElement>
        <BaseForm
          field={field}
          fieldType={fieldType}
          selfIndex={index}
          validateSameTitleExists={validateSameTitleExists}
          onCloseForm={closeFormModal}
          onSaveForm={this.onSaveForm}
          onDeleteField={
            isFieldRemovable ? this.onDeleteAmountField : undefined
          }
          openAdvancedForm={openAdvancedForm}
          currency={'INR'}
        />
      </CreatorModal>
    );
  }
}

export class AdvancedFormModal extends React.PureComponent {
  onSaveForm = formData => {
    this.props.onSaveForm(formData);
    this.props.closeFormModal();
  };

  render() {
    const { field, fieldType, onSave, closeFormModal } = this.props;

    // TODO: Handle currency
    return (
      <CreatorModal class="CreatorModal-AdvancedForm" onClose={closeFormModal}>
        <AdvancedForm
          field={field}
          fieldType={fieldType}
          onCloseForm={closeFormModal}
          onSaveForm={this.onSaveForm}
          currency={'INR'}
        />
      </CreatorModal>
    );
  }
}
