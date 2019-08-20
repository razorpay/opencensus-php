import { classList } from 'common/util';
import CreatorManager from './CreatorManager';
import EditLayer from '../../../EditLayer';

const displayField = ({ field, openBaseForm, tooltipTxt, setRef }) => {
  let _RepresentationEl = 'input',
    _RepresentationClass = '';

  if (field.hasOwnProperty('options') && field.options.cmp === 'textarea') {
    _RepresentationEl = 'textarea';
    _RepresentationClass = 'Field--textarea';
  } else if (field.hasOwnProperty('enum')) {
    _RepresentationEl = 'select';
    _RepresentationClass = 'Field--select';
  }

  return (
    <EditLayer
      class={classList(
        'Field Field--disabled',
        _RepresentationClass,
        field.required && 'Field--required'
      )}
      onClick={openBaseForm}
      infoTxt={tooltipTxt}
      setRef={setRef}
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
          <_RepresentationEl class="Field-el" disabled />
        </div>
        {field.description && (
          <div class="Field-description">{field.description}</div>
        )}
      </div>
      {openBaseForm && <i class="i i-edit" />}
    </EditLayer>
  );
};

const UDFDisplayField = CreatorManager(displayField);
export default UDFDisplayField;
