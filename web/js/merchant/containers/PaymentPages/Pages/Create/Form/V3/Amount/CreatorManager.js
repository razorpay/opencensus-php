import CreatorModal from '../CreatorModal';
import BaseForm from './BaseForm';
import AdvancedForm from './AdvancedForm';

export default function CreatorManager(_WrappedDisplayFieldComponent) {
  class HOC extends React.PureComponent {
    state = { isBaseFormOpened: false };

    onSubmitAmountField = formData => {
      this.props.onDeleteAmountField(formData, this.props.index);

      this.toggleBaseForm();
    };

    onDeleteAmountField = () => {
      this.props.onDeleteAmountField(this.props.index);
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
        isFieldRemovable = true; // // TODO: Handle condition to check atleast 1 price field is present.

      return (
        <div>
          <_WrappedDisplayFieldComponent
            field={field}
            openBaseForm={this.toggleBaseForm}
            tooltipTxt={tooltipTxt}
            setRef={this.sefRef}
          />
          {this.state.isBaseFormOpened && (
            <CreatorModal overWhatElement={this.displayFieldEl}>
              <BaseForm
                field={field}
                selfIndex={index}
                validateSameTitleExists={validateSameTitleExists}
                onClose={this.toggleBaseForm}
                onSubmit={this.onSubmitAmountField}
                onFieldDelete={
                  isFieldRemovable ? this.onDeleteAmountField : undefined
                }
              />
            </CreatorModal>
          )}
          {this.state.isAdvancedFormOpened && (
            <CreatorModal>
              <AdvancedForm
                field={field}
                onClose={this.toggleBaseForm}
                onSubmit={this.onSubmitAmountField}
              />
            </CreatorModal>
          )}
        </div>
      );
    }
  }

  return HOC;
}
