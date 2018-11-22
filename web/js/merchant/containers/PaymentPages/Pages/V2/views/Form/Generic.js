import Form from 'component/Form';
import Input from 'component/Input';
import Button from 'component/Button';
import EditLayer from '../EditLayer';
import { classList, getValueOfKeyAtLevel } from 'common/util';
import {
  FIELD_TYPES,
  mapFieldToIndex,
  getFieldFromIndices,
} from './Fields/helpers';

const CustomTypeOption = ({ option }) => (
  <React.Fragment>
    <i class={classList('i', option.icon && 'i-' + option.icon)} />
    <span class="display-label">{option.label}</span>
  </React.Fragment>
);

export const GenericField = ({ field, onEditField, infoTxt }) => {
  return (
    <EditLayer
      class={classList(
        'Field Field--disabled',
        field.required && 'Field--required',
        !onEditField && 'disable-hover'
      )}
      onClick={onEditField}
      infoTxt={infoTxt}
    >
      <div class="Field-label">
        {field.title}
        {field.required && <span class="symbol--red">*</span>}
      </div>
      <div class="Field-content">
        <div
          class={classList(
            'Field-wrapper',
            field._type && 'Field-wrapper--' + field_type
          )}
        >
          <input class="Field-el" disabled />
        </div>
        {field.description && (
          <div class="Field-description">{field.description}</div>
        )}
      </div>
      {onEditField && <i class="i i-edit" />}
    </EditLayer>
  );
};

export class GenericCreator extends React.PureComponent {
  state = {
    isDynamicAmount: false,
    hasStock: false,
    disableSubmit: !this.props.field.title, // Any required field is valid to do init, like 'name', 'title', 'type'
    hasDescription: !!this.props.field.description,
    isFieldEnum: !!this.props.field.enum,
  };

  defaultFieldIndex = this.props.field.title
    ? mapFieldToIndex(this.props.field)
    : '';

  typeOptions = [{ label: '--Select--', value: '' }].concat(
    FIELD_TYPES.map((FIELD_OPTION, idx) => {
      return {
        value: idx,
        options: !FIELD_OPTION.options
          ? undefined
          : FIELD_OPTION.options.map((SUB_OPTION, jnx) => {
              return {
                value: jnx,
                label: SUB_OPTION.label,
                icon: SUB_OPTION.icon,
              };
            }),
        label: FIELD_OPTION.label,
        icon: FIELD_OPTION.icon,
      };
    })
  );

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
      form.querySelectorAll('.is-invalid').length || !title || !fieldType;

    if (
      this.state.isFieldEnum &&
      (!this.state.enum || !this.state.enum.length)
    ) {
      disableSubmit = true;
    }

    this.setState({ disableSubmit });
  };

  onSelection = val => {
    let indices = val.index.split('');
    indices = Number(indices[0]) - 1 + indices.splice(1).join(''); // Because 0th is --Select--

    const selectedFieldSchema = getFieldFromIndices(indices);
    const isNewFieldEnum = selectedFieldSchema.hasOwnProperty('enum');
    if (this.state.isFieldEnum !== isNewFieldEnum) {
      this.setState({ enum: [] });
    }

    this.setState({ isFieldEnum: isNewFieldEnum });
  };

  onSubmit = formData => {
    let indices = formData.field_type.split('');
    indices = Number(indices[0]) - 1 + indices.splice(1).join(''); // Because 0th is --Select--
    formData.field_type = indices;
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
      allFieldsLabelList,
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
            pattern="^[0-9a-zA-Z]+(?: [0-9a-zA-Z]+)*$"
            validator={function(val) {
              if (!val) {
                return;
              }

              if (!isNaN(val)) {
                return 'Label must have atleast 1 character';
              }

              const sameTitleFieldIndex = allFieldsLabelList.indexOf(val);

              if (
                sameTitleFieldIndex > -1 &&
                sameTitleFieldIndex !== selfIndex
              ) {
                return 'Label cannot be same as other field';
              }
            }}
            autoFocus
          />
          {/*<input name="name" hidden value={} />*/}

          <Input.PowerDropdown
            name="field_type"
            label="What type of field is this?"
            placeholder="Select Type"
            defaultValue={this.defaultFieldIndex}
            options={this.typeOptions}
            onChange={this.onSelection}
            customOptionComponent={CustomTypeOption}
            customSelectedOptionComponent={CustomTypeOption}
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
            />
          )}
        </div>
        <footer>
          {!!selfIndex && (
            <Button.Transparent
              class="Button--danger"
              type="button"
              onClick={e => {
                onFieldDelete(selfIndex);
                e.stopPropagation();
              }}
            >
              Delete
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
