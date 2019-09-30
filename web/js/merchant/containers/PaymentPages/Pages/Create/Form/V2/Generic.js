import Form from 'component/Form';
import Input from 'component/Input';
import Button from 'component/Button';
import EditLayer from '../../EditLayer';
import { classList, getValueOfKeyAtLevel } from 'common/util';
import {
  getFieldTypes,
  mapFieldToIndex,
  getFieldFromIndices,
} from '../UDF_Fields/V2';

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
        field.hasOwnProperty('enum') && 'Field--select',
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
    disableSubmit: !this.props.field.title, // Any required field is valid to do init, like 'name', 'title', 'type'
    hasDescription: !!this.props.field.description,
    isFieldEnum: !!this.props.field.enum,
    enum: this.props.field.hasOwnProperty('enum')
      ? this.props.field.enum
      : undefined,
  };

  defaultEnumVal = (() => {
    if (this.props.field.title) {
      let indexTree = mapFieldToIndex(this.props.field);

      indexTree = [Number(indexTree[0]) + 1].concat(indexTree.splice(1));

      return indexTree.join(' ');
    } else {
      return '';
    }
  })();

  typeOptions = (() => {
    const FIELD_TYPES = getFieldTypes();

    return [{ label: '--Select--', value: '' }].concat(
      FIELD_TYPES.map((FIELD_OPTION, idx) => {
        return {
          value: String(idx + 1), // +1 is to adjust --select--
          options: !FIELD_OPTION.options
            ? undefined
            : FIELD_OPTION.options.map((SUB_OPTION, jdx) => {
                return {
                  value: Number(idx + 1) + ' ' + jdx, // Space separate tree.
                  label: SUB_OPTION.label,
                  icon: SUB_OPTION.icon,
                };
              }),
          label: FIELD_OPTION.label,
          icon: FIELD_OPTION.icon,
        };
      })
    );
  })();

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

  onSelection = field => {
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
            pattern="^[0-9a-zA-Z ]+"
            validator={function(val) {
              if (!val) {
                return 'Field title is required';
              }

              if (!isNaN(val)) {
                return 'Field title must have at least 1 character';
              }

              const sameTitleFieldIndex = allFieldsLabelList.indexOf(val);

              if (
                sameTitleFieldIndex > -1 &&
                sameTitleFieldIndex !== selfIndex
              ) {
                return 'Field title cannot be same as other field';
              }
            }}
            autoFocus
          />
          {/*<input name="name" hidden value={} />*/}

          <Input.PowerDropdown
            name="field_type"
            label="What type of field is this?"
            placeholder="Select Type"
            defaultValue={this.defaultEnumVal}
            options={this.typeOptions}
            onChange={this.onSelection}
            customOptionComponent={CustomTypeOption}
            customSelectedOptionComponent={CustomTypeOption}
            validator={val => {
              if (!val) {
                return 'Please select a field type';
              }
            }}
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
