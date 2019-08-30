import { classList } from 'common/util';
import CreatorManager from './CreatorManager';
import EditLayer from '../../../EditLayer';

import { mapFieldToAmountFieldType } from '../../Amount_Fields/V3';
import FIELD_TYPES from '../../Amount_Fields/fieldTypes';
import { getCurrency } from 'rzp/ui/Amount';

import { sortableHandle } from 'react-sortable-hoc';
const DragHandle = sortableHandle(() => <span class="dragHandle">::</span>);

const displayField = ({
  field,
  currency,
  openBaseForm,
  setRef,
  isListSorting,
}) => {
  const fieldType = mapFieldToAmountFieldType(field);
  let addOnAfter;

  const amountDisplay =
    field.item.amount && Number(field.item.amount).toFixed(2);
  let fieldEl = amountDisplay && (
    <div class="Field-el">
      <label>
        <b>{amountDisplay.split('.')[0]}</b>.{amountDisplay.split('.')[1]}
      </label>
    </div>
  );

  switch (fieldType) {
    case FIELD_TYPES.fixed_price_optional.key: {
      addOnAfter = (
        <div class="Field Field--CheckBox">
          <div class="Field-content">
            <div class="Field-wrapper">
              <label class="Field-el">
                <span class="CheckBox-mark" />
              </label>
            </div>
          </div>
        </div>
      );
      break;
    }

    case FIELD_TYPES.dynamic_price.key: {
      fieldEl = (
        <input
          class="Field-el"
          type="number"
          placeholder="To be filled by customer"
          disabled
        />
      );
      break;
    }

    case FIELD_TYPES.multiple_purchase.key: {
      addOnAfter = (
        <div class="Field Field--counter">
          <div class="Field-content">
            <div class="Field-wrapper">
              <button type="button">-</button>
              <input
                class="Field-el counter-value"
                type="number"
                defaultValue={field.min_purchase}
                disabled
              />
              <button type="button">+</button>
            </div>
          </div>
        </div>
      );
      break;
    }
  }

  return (
    <EditLayer
      class={classList(
        'Field Field--amount Field--disabled',
        field.mandatory && 'Field--required',
        isListSorting && 'disable-hover'
      )}
      onClick={openBaseForm}
      setRef={setRef}
    >
      <DragHandle />

      <div class="Field-label">
        {field.item.title}
        {!field.mandatory && <div class="text-optional">(Optional)</div>}
        {/*{field.mandatory && <span class="symbol--red">*</span>}*/}
      </div>
      <div class="Field-content">
        <div
          class={classList(
            'Field-wrapper',
            field._type && 'Field-wrapper--' + field_type
          )}
        >
          <span class="Field-addon Field-addon--before">
            <b>{getCurrency(currency).symbol}</b>
          </span>

          {fieldEl}

          <span class="Field-addon Field-addon--after">{addOnAfter}</span>
        </div>
        {field.description && (
          <div class="Field-description">{field.description}</div>
        )}
      </div>
      {openBaseForm && <i class="i i-edit" />}
    </EditLayer>
  );
};

export default CreatorManager(displayField);
