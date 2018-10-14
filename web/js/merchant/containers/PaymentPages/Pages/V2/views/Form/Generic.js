import EditLayer from '../EditLayer';
import { classList } from 'common/util';

export const GenericField = ({ field, handleClick, infoTxt }) => {
  return (
    <EditLayer
      class={classList(
        'Field Field--disabled',
        field.required && 'Field--required',
        !handleClick && 'disable-hover'
      )}
      onClick={handleClick}
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

export const GenericCreator = ({}) => {
  return <div />;
};
