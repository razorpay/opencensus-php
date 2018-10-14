import { connect } from 'react-redux';
import { classList } from 'common/util';
import EditLayer from '../EditLayer';
import {
  deleteInSchema,
  updateInSchema,
  addInSchema,
} from 'merchant/modules/wysiwyg';

@connect(state => ({ FORM_SCHEMA: state.wysiwyg.FORM_SCHEMA }), {
  deleteInSchema,
  updateInSchema,
  addInSchema,
})
export default class View extends React.PureComponent {
  render() {
    const FORM_SCHEMA = this.props.FORM_SCHEMA;

    return (
      <div class="UI-form">
        {FORM_SCHEMA.map((field, idx) => {
          return <PlaceholderField key={idx} field={field} />;
        })}
      </div>
    );
  }
}

const PlaceholderField = ({ field }) => {
  return (
    <EditLayer
      customClass={classList(
        'Field Field--disabled',
        field.required && 'Field--required '
      )}
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
