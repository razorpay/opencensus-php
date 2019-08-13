import { classList } from 'common/util';
import CreatorManager from './CreatorManager';
import EditLayer from '../../../EditLayer';

/* TODO: This component contains all the variations of displaying Amount
*   1. Fixed Amount
*   2. Fixed Amount with checkbox(mandatory)
*   3. Dynamic Amount Field
*   3. Amount with Counter
* */
const AmountDisplayField = ({
  field,
  openBaseForm,
  openAdvancedForm,
  tooltipTxt,
  setRef,
}) => {
  return (
    <EditLayer
      class={classList(
        'Field Field--disabled',
        field.hasOwnProperty('enum') && 'Field--select',
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
          <input class="Field-el" disabled />
        </div>
        {field.description && (
          <div class="Field-description">{field.description}</div>
        )}
      </div>
      {openBaseForm && <i class="i i-edit" />}
    </EditLayer>
  );
};

export default CreatorManager(AmountDisplayField);
