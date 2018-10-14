import Form from 'component/Form';
import Button from 'component/Button';
import EditLayer from '../EditLayer';
import { classList } from 'common/util';

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
  onChange = ({ target }) => {
    const { name, value } = target;
    console.log(name, value);
  };

  render() {
    const { onClose, onSubmit } = this.props;

    return (
      <Form onChange={this.onChange}>
        <footer>
          <button type="button" class="btn-link" onClick={onClose}>
            Cancel
          </button>
          <Button.Primary onClick={onSubmit}>Add</Button.Primary>
        </footer>
      </Form>
    );
  }
}
