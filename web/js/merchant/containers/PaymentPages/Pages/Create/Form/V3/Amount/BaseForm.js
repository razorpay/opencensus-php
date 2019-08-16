import Form from 'component/Form';
import Input from 'component/Input';
import Button from 'component/Button';
import { classList } from 'common/util';
import { mapFieldToAmountFieldType } from '../../Amount_Fields/V3';
import FIELD_TYPES from '../../Amount_Fields/fieldTypes';
import FieldOptionsDropdown, { OptionsItem } from '../../FieldOptionsDropdown';
import { AdvancedFormModal } from './CreatorManager';

export default class BaseForm extends React.PureComponent {
  constructor(props) {
    super(props);
    const field = props.field;

    const title = field && field.item.title,
      disableSubmit = !title,
      hasDescription = field && !!field.item.description,
      isMandatory = field && !!field.mandatory,
      imageUrl = (field && field.image_url) || '';

    this.state = {
      isAdvancedFormOpened: false,
      disableSubmit,
      hasDescription,
      mirrorDisplayTitle: title || '',
      isMandatory,
      imageUrl,
    };

    this.fieldType = props.fieldType || mapFieldToAmountFieldType(field);
  }

  onChange = ({ target }) => {
    setTimeout(this.toggleSubmitBtn); // Validate form for input errors via class change in DOM, hence delayed.
  };

  toggleSubmitBtn = () => {
    const form = this.formEl;
    let disableSubmit = !!form.querySelectorAll('.is-invalid').length;

    this.setState({ disableSubmit });
  };

  onSaveAdvancedForm = formData => {
    console.log('ADVANCED FORM...', formDta);
  };

  onSaveField = formData => {
    this.props.onSaveField(formData);
  };

  toggleAdvancedForm = forcedState => {
    this.setState({
      isAdvancedFormOpened:
        typeof forcedState !== 'undefined'
          ? forcedState
          : !this.state.isAdvancedFormOpened,
    });
  };

  toggleDescriptionField = _ => {
    this.setState({
      hasDescription: !this.state.hasDescription,
    });
  };

  toggleImage = _ => {
    const toAddImage = !this.state.imageUrl;

    if (toAddImage) {
      // 1. Open modal to add image
      // 2. After success imageUrl in state
    } else {
      this.setState({
        imageUrl: '',
      });
    }
  };

  onDeleteField = _ => {
    this.props.onDeleteField();
  };

  onInputTitle = ({ target }) => {
    this.setState({
      mirrorDisplayTitle: target.value,
    });
  };

  setRefForm = el => (this.formEl = el);

  render() {
    const {
      field,
      selfIndex,
      validateSameTitleExists,
      onCloseForm,
      onDeleteField,
    } = this.props;

    const {
      imageUrl,
      isMandatory,
      hasDescription,
      disableSubmit,
      mirrorDisplayTitle,
      isAdvancedFormOpened,
    } = this.state;

    console.log('FIELD...', field);

    return (
      <React.Fragment>
        <Form
          setRef={this.setRefForm}
          onChange={this.onChange}
          onSubmit={this.onSaveField}
        >
          <Input.TextareaAutoResize
            class="Input--title"
            name="title"
            defaultValue={field ? field.item.title : ''}
            placeholder="Enter field title"
            pattern="^[0-9a-zA-Z ]+"
            onInput={this.onInputTitle}
            validator={function(val) {
              if (!val) {
                return 'Field title is required';
              }

              if (!isNaN(val)) {
                return 'Field title must have atleast 1 character';
              }

              if (validateSameTitleExists(val, selfIndex)) {
                return 'Field title cannot be same as other field';
              }
            }}
            autoFocus
          >
            <div
              class={classList(
                'Field Field--mirrorDisplay',
                isMandatory && 'Field--required'
              )}
            >
              <span class="mirror-title">{mirrorDisplayTitle}</span>
              {mirrorDisplayTitle && <span className="symbol--red">*</span>}
            </div>
          </Input.TextareaAutoResize>

          <input name="mandatory" value={isMandatory | 0} hidden readOnly />
          <input name="image_url" value={imageUrl} hidden readOnly />

          <div class="Field--representation">
            <div class="Field-wrapper placeholder-field">
              <input
                className="Field-el"
                placeholder="To be filled by customer"
                disabled
              />
            </div>

            {hasDescription && (
              <Input.TextareaAutoResize
                class="Input--description"
                name="description"
                placeholder="Enter description"
                defaultValue={field ? field.item.description : ''}
                validator={val => {
                  if (val && val.length > 128) {
                    return 'Field description cannot be more than 128 characters';
                  }
                }}
                autoFocus
              />
            )}
          </div>

          <FieldOptionsDropdown
            trigger={
              <Button.Transparent>
                <i class="i i-ellipsis-v" />
              </Button.Transparent>
            }
          >
            <OptionsItem isSelected={!!this.state.isMandatory}>
              <div onClick={this.toggleImage}>
                <i class="i i-info-circle" />
                {this.state.hasDescription ? 'Remove' : 'Add'} Image
              </div>
            </OptionsItem>

            <OptionsItem isSelected={!!this.state.hasDescription}>
              <div onClick={this.toggleDescriptionField}>
                <i class="i i-info-circle" />
                {this.state.hasDescription ? 'Remove' : 'Add'} Description
              </div>
            </OptionsItem>

            <OptionsItem isSelected={!!this.state.hasDescription}>
              <div onClick={this.toggleAdvancedForm}>
                <i class="i i-info-circle" />
                Advanced Options
              </div>
            </OptionsItem>

            {!!selfIndex &&
              onDeleteField && (
                <OptionsItem>
                  <div onClick={this.onDeleteField}>
                    <i class="i i-delete" />
                    Delete Field
                  </div>
                </OptionsItem>
              )}
          </FieldOptionsDropdown>

          <Button.Transparent
            class="base-form-side-btn base-form-close"
            type="button"
            onClick={onCloseForm}
          >
            <span>&times;</span>
            Close
          </Button.Transparent>

          <Button.Transparent
            class="base-form-side-btn base-form-save"
            type="submit"
            disabled={disableSubmit}
          >
            <span class="icon i-check" />
            Save
          </Button.Transparent>
        </Form>

        {isAdvancedFormOpened && (
          <AdvancedFormModal
            field={field}
            fieldType={this.fieldType}
            onSave={this.onSaveAdvancedForm}
            onCloseForm={_ => this.toggleAdvancedForm(false)}
          />
        )}
      </React.Fragment>
    );
  }
}
