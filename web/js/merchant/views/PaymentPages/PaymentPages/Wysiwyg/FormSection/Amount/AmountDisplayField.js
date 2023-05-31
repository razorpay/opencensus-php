import { sortableHandle } from 'react-sortable-hoc';
import { classList } from 'common/utils/rzp-utils';

import CreatorManager from './CreatorManager';
import EditLayer from 'merchant/views/PaymentPages/PaymentPages/components/EditLayer';

import {
  mapFieldToAmountFieldType,
  isMandatoryToBool,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';
import FIELD_TYPES from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/fieldTypes';
import { getCurrency } from 'common/ui/Amount';
import {
  BATCH_UPLOAD_MSG,
  FILLED_BY_CUSTOMER,
} from 'merchant/views/PaymentPages/PaymentPages/constants';

const DragHandle = sortableHandle(() => (
  <span class="dragHandle">
    <i class="i i-dotter" />
  </span>
));

const displayField = ({
  field,
  currency,
  openBaseForm,
  setRef,
  isListSorting,
  isBatchPaymentPages,
}) => {
  const placeHolder = isBatchPaymentPages ? BATCH_UPLOAD_MSG : FILLED_BY_CUSTOMER;
  const fieldType = mapFieldToAmountFieldType(field);
  let addOnAfter;

  const amountDisplay = field.item.amount && Number(field.item.amount).toFixed(2);
  let fieldEl = amountDisplay && (
    <div class="Field-el">
      <label>
        <b>{amountDisplay.split('.')[0]}</b>.{amountDisplay.split('.')[1]}
      </label>
    </div>
  );

  let hasCheckBox = false;

  switch (fieldType) {
    case FIELD_TYPES.fixed_price.key: {
      if (!isMandatoryToBool(field.mandatory)) {
        hasCheckBox = true;

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
      }

      break;
    }

    case FIELD_TYPES.dynamic_price.key: {
      fieldEl = <input class="Field-el" type="number" placeholder={placeHolder} disabled />;

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
                value={field.min_purchase}
                readOnly
                disabled
              />
              <button type="button">+</button>
            </div>
          </div>
        </div>
      );

      break;
    }

    default:
      break;
  }

  const currencySymbol = getCurrency(currency).symbol;

  return (
    <EditLayer
      class={classList(
        'Field Field--amount Field--disabled',
        field.mandatory && 'Field--required',
        field.image_url && 'Field--has-image',
        isListSorting && 'disable-hover',
        `Field--currency-${currencySymbol.length > 4 ? 'long' : currencySymbol.length}`,
      )}
      onClick={openBaseForm}
      setRef={setRef}
    >
      <DragHandle />

      <div class="Field-label">
        {field.item.name}
        {!field.mandatory && <div class="text-optional">(Optional)</div>}
        {/*{field.mandatory && <span class="symbol--red">*</span>}*/}
      </div>
      <div class="Field-content">
        <div class={classList('Field-wrapper', field._type && `Field-wrapper--${field._type}`)}>
          <span class="Field-addon Field-addon--before">
            <span>
              {field.image_url && <img src={field.image_url} />}
              <b class="currency-symbol">{currencySymbol}</b>
            </span>
          </span>

          {fieldEl}

          <span
            class={classList(
              `Field-addon Field-addon--after`,
              hasCheckBox && 'Field-addon--after--CheckBox',
            )}
          >
            {addOnAfter}
          </span>
        </div>
        {field?.item?.description && <div class="Field-description">{field.item.description}</div>}
      </div>
      {openBaseForm && <i class="i i-edit" />}
    </EditLayer>
  );
};
// eslint-disable-next-line babel/new-cap
export default CreatorManager(displayField);
