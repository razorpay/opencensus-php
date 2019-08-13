import CreatorModal from '../CreatorModal';
import BaseForm from './BaseForm';

export default function CreatorManager(_WrappedDisplayFieldComponent) {
  class HOC extends React.PureComponent {
    state = { isBaseFormOpened: false };

    onSubmitUDFField = formData => {
      this.props.onDeleteUDFField(formData, this.props.index);

      this.toggleBaseForm();
    };

    onDeleteUDFField = () => {
      this.props.onDeleteUDFField(this.props.index);
      this.toggleBaseForm();
    };

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
      const { field, index, validateSameTitleExists } = this.props;
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
            <CreatorModal
              class="CreatorModal-BaseForm"
              overWhatElement={this.displayFieldEl}
            >
              <BaseForm
                field={field}
                selfIndex={index}
                validateSameTitleExists={validateSameTitleExists}
                onCloseForm={_ => this.toggleBaseForm()}
                onSaveField={this.onSubmitUDFField}
                onDeleteField={
                  isFieldRemovable ? this.onDeleteUDFField : undefined
                }
              />
            </CreatorModal>
          )}
        </div>
      );
    }
  }

  return HOC;
}
