import Form from 'component/Form';
import Input from 'component/Input';
import Button from 'component/Button';
import { classList } from 'common/util';
import {
  getFieldTypes,
  mapFieldToIndex,
  getFieldFromIndices,
} from '../../Fields/V3';
import FieldOptionsDropdown, { OptionsItem } from './FieldOptionsDropdown';

export default class BaseForm extends React.PureComponent {
  state = {
    disableSubmit: !this.props.field.title, // Any required field is valid to do init, like 'name', 'title', 'type'
    hasDescription: !!this.props.field.description,
    fakeDisplayTitle: this.props.field.title,
    isRequired: !!this.props.field.required,
    isFieldEnum: !!this.props.field.enum,
    enum: this.props.field.hasOwnProperty('enum')
      ? this.props.field.enum
      : undefined,
  };

  onChange = ({ target }) => {
    setTimeout(this.toggleSubmit); // Validate form for input errors via class change in DOM, hence delayed.
  };

  toggleSubmit = () => {
    const form = document.getElementsByName('udf-base-form')[0];
    const title = document.getElementsByName('title')[0].value;

    let disableSubmit = !!form.querySelectorAll('.is-invalid').length || !title;

    if (
      this.state.isFieldEnum &&
      (!this.state.enum || !this.state.enum.length)
    ) {
      disableSubmit = true;
    }

    this.setState({ disableSubmit });
  };

  onFieldTypeSelection = field => {
    let indexString = field.option.value;
    let selectedFieldSchema;

    if (indexString) {
      let indexTree = indexString.split(' ');
      indexTree = [Number(indexTree[0]) - 1].concat(indexTree.splice(1));
      selectedFieldSchema = getFieldFromIndices(indexTree.join(' '));
    }

    const isNewFieldEnum =
      selectedFieldSchema && selectedFieldSchema.hasOwnProperty('enum');
    if (this.state.isFieldEnum !== isNewFieldEnum) {
      this.setState({ enum: [] });
    }

    this.setState({ isFieldEnum: isNewFieldEnum });
  };

  onSaveField = formData => {
    /*
    let fieldType = formData.field_type.split(' ');

    fieldType = [Number(fieldType[0]) - 1].concat(fieldType.splice(1));
    formData.field_type = fieldType.join(' ');

    formData.enum =
      this.state.enum && this.state.enum.length ? this.state.enum : undefined;
*/

    this.props.onSaveField(formData);
  };

  onChangeEnumList = (enumList = []) => {
    let trimmedEnums = enumList.concat();

    trimmedEnums = trimmedEnums.reduce((r, o) => {
      if (o) {
        r.push(o);
      }

      return r;
    }, []);

    this.setState({ enum: trimmedEnums });

    setTimeout(this.toggleSubmit);
  };

  toggleDescriptionField = _ => {
    this.setState({
      hasDescription: !this.state.hasDescription,
    });
  };

  toggleOptional = _ => {
    this.setState({
      isRequired: !this.state.isRequired,
    });
  };

  deletefield = _ => {
    this.props.onDeleteField();
  };

  onInputTitle = ({ target }) => {
    this.setState({
      fakeDisplayTitle: target.value,
    });
  };

  render() {
    const {
      field,
      selfIndex,
      validateSameTitleExists,
      onCloseForm,
    } = this.props;

    const {
      isRequired,
      hasDescription,
      disableSubmit,
      fakeDisplayTitle,
    } = this.state;

    return (
      <Form
        name="udf-base-form"
        onChange={this.onChange}
        onSubmit={this.onSaveField}
      >
        <Input.TextareaAutoResize
          class="Input--title"
          name="title"
          defaultValue={field.title}
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
              'Field Field--fakeDisplay',
              isRequired && 'Field--required'
            )}
          >
            {fakeDisplayTitle}
            <i class="fake-caret" />
            <span className="symbol--red">*</span>
          </div>
        </Input.TextareaAutoResize>

        <div class="Input--representation">
          <Input
            class="placeholder-field"
            placeholder="To be filled by customer"
            disabled
          />

          {hasDescription && (
            <Input.TextareaAutoResize
              class="Input--description"
              name="description"
              placeholder="Enter description"
              defaultValue={field.description}
              validator={val => {
                if (val && val.length > 128) {
                  return 'Field description cannot be more than 128 characters';
                }
              }}
              autoFocus
            />
          )}
        </div>

        {this.state.isFieldEnum && (
          <Input.EnumList
            class="dropdown-options"
            onChange={this.onChangeEnumList}
            defaultValue={
              field.enum || ['']
            } /* TODO: Init enum list for edit exising entries */
          />
        )}

        <FieldOptionsDropdown
          trigger={
            <Button.Transparent>
              <i class="i i-ellipsis-v" />
            </Button.Transparent>
          }
        >
          <OptionsItem isSelected={!!this.state.isRequired}>
            <div onClick={this.toggleOptional}>
              <i class="i i-info-circle" />
              Optional
            </div>
          </OptionsItem>

          <OptionsItem isSelected={!!this.state.hasDescription}>
            <div onClick={this.toggleDescriptionField}>
              <i class="i i-info-circle" />
              {this.state.hasDescription ? 'Remove' : 'Add'} Description
            </div>
          </OptionsItem>

          {!!selfIndex && (
            <OptionsItem>
              <div onClick={this.deleteField}>
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
          onClick={this.onSaveField}
          disabled={disableSubmit}
        >
          <span class="icon i-check" />
          Save
        </Button.Transparent>
      </Form>
    );
  }
}
