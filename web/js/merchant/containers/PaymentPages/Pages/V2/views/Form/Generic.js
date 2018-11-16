import Form from 'component/Form';
import Input from 'component/Input';
import Button from 'component/Button';
import EditLayer from '../EditLayer';
import { classList } from 'common/util';
import { FIELD_TYPES, mapFieldToIndex } from './Fields/helpers';

const CustomTypeOption = ({ option }) => (
  <React.Fragment>
    <i class={classList('i', option.icon && 'i-' + option.icon)} />
    {option.label}
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
  };

  defaultFieldIndex = this.props.field.title
    ? mapFieldToIndex(this.props.field)
    : '';

  typeOptions = [{ label: '--Select--', value: '' }].concat(
    Object.keys(FIELD_TYPES).map((i, idx) => {
      return {
        value: idx,
        label: FIELD_TYPES[i].label,
        icon: FIELD_TYPES[i].icon,
      };
    })
  );

  onChange = ({ target }) => {
    const { name, value } = target;
    const stateName = target.getAttribute('data-name');

    if (stateName === 'has_description') {
      this.setState({ hasDescription: target.checked });
    }

    setTimeout(() => {
      const form = document.getElementsByName('form_creator_generic')[0];
      const title = document.getElementsByName('title')[0].value;
      const fieldType = document.getElementsByName('field_type')[0].value;

      const disableSubmit =
        form.querySelectorAll('.is-invalid').length || !title || !fieldType;
      this.setState({ disableSubmit });
    });
  };

  render() {
    const {
      onClose,
      onSubmit,
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
        onSubmit={onSubmit}
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
            customOptionComponent={CustomTypeOption}
            customSelectedOptionComponent={CustomTypeOption}
          />
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
