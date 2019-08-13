import Form from 'component/Form';
import Input from 'component/Input';
import Button from 'component/Button';
import {
  getFieldTypes,
  mapFieldToIndex,
  getFieldFromIndices,
} from '../../Fields/V3';

export default class BaseForm extends React.PureComponent {
  state = {
    disableSubmit: !this.props.field.title, // Any required field is valid to do init, like 'name', 'title', 'type'
    hasDescription: !!this.props.field.description,
    isFieldEnum: !!this.props.field.enum,
    enum: this.props.field.hasOwnProperty('enum')
      ? this.props.field.enum
      : undefined,
  };

  onChange = ({ target }) => {
    const { name, value } = target;
    const stateName = target.getAttribute('data-name');

    if (stateName === 'has_description') {
      this.setState({ hasDescription: target.checked });
    }

    setTimeout(this.toggleSubmit);
  };

  toggleSubmit = () => {
    const form = document.getElementsByName('form_creator_generic')[0];
    const title = document.getElementsByName('title')[0].value;
    const fieldType = document.getElementsByName('field_type')[0].value;

    let disableSubmit =
      !!form.querySelectorAll('.is-invalid').length || !title || !fieldType;

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

  onSubmit = formData => {
    let fieldType = formData.field_type.split(' ');

    fieldType = [Number(fieldType[0]) - 1].concat(fieldType.splice(1));
    formData.field_type = fieldType.join(' ');

    formData.enum =
      this.state.enum && this.state.enum.length ? this.state.enum : undefined;

    this.props.onSubmit(formData);
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

  render() {
    const {
      onClose,
      field,
      validateSameTitleExists,
      selfIndex,
      onFieldDelete,
    } = this.props;
    const { hasDescription, disableSubmit } = this.state;

    return (
      <Form
        name="form_creator_generic"
        onChange={this.onChange}
        onSubmit={this.onSubmit}
      >
        <div class="section section-1">
          <Input
            label="What is this field called?"
            name="title"
            defaultValue={field.title}
            placeholder="Enter field title"
            pattern="^[0-9a-zA-Z ]+"
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
          />

          {this.state.isFieldEnum && (
            <Input.EnumList
              class="dropdown-options"
              onChange={this.onChangeEnumList}
              defaultValue={
                field.enum || ['']
              } /* TODO: Init enum list for edit exising entries */
            />
          )}
        </div>
        <div class="section section-2">
          <Input.Check
            name="required"
            fieldLabel="It is mandatory for the customer to fill this field"
            defaultValue={!!field.required | 0}
          />
          <Input.Check
            data-name="has_description"
            defaultValue={!!field.description | 0}
            fieldLabel="I want to add a help text for this field"
          />
          {hasDescription && (
            <Input
              name="description"
              class="Input--description"
              defaultValue={field.description}
              validator={val => {
                if (val && val.length > 128) {
                  return 'Field description cannot be more than 128 characters';
                }
              }}
            />
          )}
        </div>
        <footer>
          {!!selfIndex && (
            <Button.Transparent
              class="Button--muted"
              type="button"
              onClick={e => {
                onFieldDelete(selfIndex);
                e.stopPropagation();
              }}
            >
              Delete Field
            </Button.Transparent>
          )}
          <div class="group-right">
            <button class="btn-link" type="button" onClick={onClose}>
              Cancel
            </button>
            <Button.Primary type="submit" disabled={disableSubmit}>
              Add
            </Button.Primary>
          </div>
        </footer>
      </Form>
    );
  }
}
