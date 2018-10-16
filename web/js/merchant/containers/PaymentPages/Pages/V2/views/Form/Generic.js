import Form from 'component/Form';
import Input from 'component/Input';
import Button from 'component/Button';
import EditLayer from '../EditLayer';
import { classList } from 'common/util';
import { TYPES } from './Fields/helpers';

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
    </EditLayer>
  );
};

export class GenericCreator extends React.PureComponent {
  state = {
    isDynamicAmount: false,
    hasStock: false,
    disableSubmit: !this.props.label,
  };

  typeOptions = ['--Select--'].concat(
    Object.keys(TYPES).map(i => {
      return {
        name: TYPES[i].label,
        label: TYPES[i].label,
      };
    })
  );

  onChange = ({ target }) => {
    const { name, value } = target;

    setTimeout(() => {
      const form = document.getElementsByName('form_creator_generic')[0];
      const title = document.getElementsByName('title')[0].value;
      const type = document.getElementsByName('type')[0].value;

      if (form.querySelectorAll('.is-invalid').length || !title || !type) {
        this.setState({ disableSubmit: true });
      } else {
        this.setState({ disableSubmit: false });
      }
    });
  };

  render() {
    const { onClose, onSubmit } = this.props;
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
            placeholder="Enter field title"
            pattern="^[a-zA-Z0-9 ]+$"
            autoFocus
          />
          {/*<input name="name" hidden value={} />*/}
          <Input.Select
            name="type"
            label="What type of field is this?"
            placeholder="Select Type"
            options={this.typeOptions}
          />
        </div>
        <div class="section section-2">
          <Input.Check
            name="required"
            fieldLabel="It is mandatory for the customer to fill this field"
          />
          <Input.Check
            onClick={e =>
              this.setState({
                hasDescription: e.target.checked,
              })
            }
            fieldLabel="I want to add a help text for this field"
          />
          {hasDescription && (
            <Input name="description" class="Input--description" />
          )}
        </div>
        <footer>
          <button type="button" class="btn-link" onClick={onClose}>
            Cancel
          </button>
          <Button.Primary type="submit" disabled={disableSubmit}>
            Add
          </Button.Primary>
        </footer>
      </Form>
    );
  }
}
