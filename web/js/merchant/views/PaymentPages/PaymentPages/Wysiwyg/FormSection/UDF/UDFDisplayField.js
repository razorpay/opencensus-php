import { classList } from 'common/utils/rzp-utils';
import CreatorManager from './CreatorManager';
import EditLayer from 'merchant/views/PaymentPages/PaymentPages/components/EditLayer';

import { sortableHandle } from 'react-sortable-hoc';
const DragHandle = sortableHandle(() => (
  <span className="dragHandle">
    <i className="i i-dotter" />
  </span>
));

const displayField = ({ field, openBaseForm, tooltipTxt, setRef, isListSorting, isPreview }) => {
  let RepresentationEl = 'input';
  let _RepresentationClass = '';

  if (field.hasOwnProperty('options') && field.options.cmp === 'textarea') {
    RepresentationEl = 'textarea';
    _RepresentationClass = 'Field--textarea';
  } else if (field.hasOwnProperty('enum')) {
    RepresentationEl = 'select';
    _RepresentationClass = 'Field--select';
  }

  return (
    <EditLayer
      className={classList(
        'Field Field--disabled',
        _RepresentationClass,
        field.required && 'Field--required',
        isListSorting && 'disable-hover',
        isPreview && 'default-cursor',
      )}
      onClick={!isPreview ? openBaseForm : undefined}
      infoTxt={tooltipTxt}
      setRef={setRef}
    >
      <DragHandle />

      <div data-testid={field.title} className="Field-label">
        {field.title}
        {!field.required && <div className="text-optional">(Optional)</div>}
      </div>
      <div className="Field-content">
        <div className={classList('Field-wrapper', field.type && `Field-wrapper--${field.type}`)}>
          <RepresentationEl className="Field-el" disabled />
        </div>
        {field.description && <div className="Field-description">{field.description}</div>}
      </div>
      {openBaseForm && <i className="i i-edit" />}
    </EditLayer>
  );
};

// eslint-disable-next-line babel/new-cap
const UDFDisplayField = CreatorManager(displayField);
export default UDFDisplayField;
