import Form from 'component/Form';
import Input from 'component/Input';
import Button from 'component/Button';
import { classList } from 'common/util';
import { mapFieldToIndex } from '../../Fields/V3';
import FieldOptionsDropdown, { OptionsItem } from './FieldOptionsDropdown';

export default class BaseForm extends React.PureComponent {
  constructor(props) {
    super(props);

    const fieldSchema = props.field;

    this.state = {
      disableSubmit: !fieldSchema.title, // Any required field is valid to do init, like 'name', 'title', 'type'
      hasDescription: !!fieldSchema.description,
      fakeDisplayTitle: fieldSchema.title,
      isRequired: !!fieldSchema.required,
      isFieldEnum: !!fieldSchema.enum,
      enum: fieldSchema.hasOwnProperty('enum') ? fieldSchema.enum : undefined,
    };

    this.field_index_in_options = mapFieldToIndex(fieldSchema);
  }

  onChange = ({ target }) => {
    setTimeout(this.toggleSubmit); // Validate form for input errors via class change in DOM, hence delayed.
  };

  toggleSubmit = () => {
    const form = this.formEl;
    let disableSubmit = !!form.querySelectorAll('.is-invalid').length;

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

  onDeleteField = _ => {
    this.props.onDeleteField();
  };

  onInputTitle = ({ target }) => {
    this.setState({
      fakeDisplayTitle: target.value,
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
      isRequired,
      hasDescription,
      disableSubmit,
      fakeDisplayTitle,
    } = this.state;

    console.log('FIELD...', field);

    let _RepresentationEl = 'input',
      _RepresentationClass = '';

    if (field.options && field.options.cmp === 'textarea') {
      _RepresentationEl = 'textarea';
      _RepresentationClass = 'Field--textarea';
    } else if (field.enum) {
      _RepresentationEl = 'select';
      _RepresentationClass = 'Field--select';
    }

    return (
      <Form
        setRef={this.setRefForm}
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
            {fakeDisplayTitle && <span className="symbol--red">*</span>}
          </div>
        </Input.TextareaAutoResize>

        <input
          name="field_type"
          value={this.field_index_in_options}
          hidden
          readOnly
        />
        <input name="required" value={isRequired | 0} hidden readOnly />

        <div class={classList('Field--representation', _RepresentationClass)}>
          <div class="Field-wrapper placeholder-field">
            <_RepresentationEl
              class="Field-el"
              placeholder="To be filled by customer"
              disabled
            />
          </div>

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
    );
  }
}
