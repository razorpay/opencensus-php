import CreatorModal from '../CreatorModal';
import BaseForm from './BaseForm';
import AdvancedForm from './AdvancedForm';

export default function CreatorManager(_WrappedDisplayFieldComponent) {
  class HOC extends React.PureComponent {
    state = {
      isBaseFormOpened: false,
      isAdvancedFormOpened: false,
      fieldType: null,
    };

    closeBaseForm = _ => {
      this.setState({
        isBaseFormOpened: false,
        fieldType: null,
      });
    };

    openBaseForm = intentTFieldype => {
      const newState = {
        isBaseFormOpened: true,
      };

      if (intentTFieldype) {
        newState.fieldType = intentTFieldype;
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

      // Combine data from advanced form
      //this.props.onSubmitAmountField(formData, this.props.index);
    };

    onSaveAdvancedForm = formData => {
      console.log('ADVANCED FORM...', formData);
    };

    render() {
      const {
        field,
        index,
        validateSameTitleExists,
        onDeleteAmountField,
      } = this.props;

      let isFieldRemovable = true; // TODO: Handle condition to check atleast 1 price field is present.

      return (
        <div class="CreatorManager">
          <_WrappedDisplayFieldComponent
            field={field}
            openBaseForm={this.openBaseForm}
          />
          {this.state.isBaseFormOpened && (
            <BaseFormModal
              index={index}
              field={field}
              fieldType={this.state.fieldType}
              validateSameTitleExists={validateSameTitleExists}
              onSaveForm={this.onSaveBaseForm}
              onDeleteAmountField={onDeleteAmountField}
              closeFormModal={this.closeBaseForm}
              isFieldRemovable={isFieldRemovable}
              openAdvancedForm={_ => this.toggleAdvancedForm(true)}
            />
          )}

          {this.state.isAdvancedFormOpened && (
            <AdvancedFormModal
              field={field}
              fieldType={this.state.fieldType}
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

    return (
      <CreatorModal class="CreatorModal-AdvancedForm">
        <AdvancedForm
          field={field}
          fieldType={fieldType}
          onCloseForm={closeFormModal}
          onSaveForm={this.onSaveForm}
        />
      </CreatorModal>
    );
  }
}
