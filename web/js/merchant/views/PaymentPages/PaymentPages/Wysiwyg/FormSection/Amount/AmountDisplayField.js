import { formatNumberByParts, getCurrencySymbol } from '@razorpay/i18nify-js/currency';
import { sortableHandle } from 'react-sortable-hoc';

import { classList } from 'common/utils/rzp-utils';
import {
  mapFieldToAmountFieldType,
  isMandatoryToBool,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';
import FIELD_TYPES_MAP from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/fieldTypes';
import EditLayer from 'merchant/views/PaymentPages/PaymentPages/components/EditLayer';
import {
  BATCH_UPLOAD_MSG,
  FILLED_BY_CUSTOMER,
} from 'merchant/views/PaymentPages/PaymentPages/constants';

import CreatorManager from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/CreatorManager';

const DragHandle = sortableHandle(() => (
  <span className="dragHandle">
    <i className="i i-dotter" />
  </span>
));

const displayField = ({
  field,
  currency,
  openBaseForm,
  setRef,
  isListSorting,
  isBatchPaymentPages,
  countryCode,
}) => {
  const placeHolder = isBatchPaymentPages ? BATCH_UPLOAD_MSG : FILLED_BY_CUSTOMER;
  const fieldType = mapFieldToAmountFieldType(field, countryCode);
  let addOnAfter;

  const amountDisplay = field.item.amount && formatNumberByParts(field.item.amount, { currency });

  let fieldEl = amountDisplay && (
    <div className="Field-el">
      <label>
        <b>{amountDisplay.integer}</b>
        {amountDisplay.decimal && amountDisplay.fraction && (
          <span>
            {amountDisplay.decimal}
            {amountDisplay.fraction}
          </span>
        )}
      </label>
    </div>
  );

  let hasCheckBox = false;

  const FIELD_TYPES = FIELD_TYPES_MAP[countryCode];

  switch (fieldType) {
    case FIELD_TYPES.fixed_price.key: {
      if (!isMandatoryToBool(field.mandatory)) {
        hasCheckBox = true;

        addOnAfter = (
          <div className="Field Field--CheckBox">
            <div className="Field-content">
              <div className="Field-wrapper">
                <label className="Field-el">
                  <span className="CheckBox-mark" />
                </label>
              </div>
            </div>
          </div>
        );
      }

      break;
    }

    case FIELD_TYPES.dynamic_price.key: {
      fieldEl = <input className="Field-el" type="number" placeholder={placeHolder} disabled />;

      break;
    }

    case FIELD_TYPES.multiple_purchase.key: {
      addOnAfter = (
        <div className="Field Field--counter">
          <div className="Field-content">
            <div className="Field-wrapper">
              <button type="button">-</button>
              <input
                className="Field-el counter-value"
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

  const currencySymbol = getCurrencySymbol(currency);

  return (
    <EditLayer
      className={classList(
        'Field Field--amount Field--disabled',
        field.mandatory && 'Field--required',
        field.image_url && 'Field--has-image',
        isListSorting && 'disable-hover',
        `Field--currency-${currencySymbol?.length > 4 ? 'long' : currencySymbol?.length}`,
      )}
      onClick={openBaseForm}
      setRef={setRef}
    >
      <DragHandle />

      <div className="Field-label">
        {field.item.name}
        {!field.mandatory && <div className="text-optional">(Optional)</div>}
        {/*{field.mandatory && <span className="symbol--red">*</span>}*/}
      </div>
      <div className="Field-content">
        <div className={classList('Field-wrapper', field._type && `Field-wrapper--${field._type}`)}>
          <span className="Field-addon Field-addon--before">
            <span>
              {field.image_url && <img src={field.image_url} />}
              <b className="currency-symbol">{currencySymbol}</b>
            </span>
          </span>

          {fieldEl}

          <span
            className={classList(
              `Field-addon Field-addon--after`,
              hasCheckBox && 'Field-addon--after--CheckBox',
            )}
          >
            {addOnAfter}
          </span>
        </div>
        {field?.item?.description && <div className="Field-description">{field.item.description}</div>}
      </div>
      {openBaseForm && <i className="i i-edit" />}
    </EditLayer>
  );
};
// eslint-disable-next-line babel/new-cap
export default CreatorManager(displayField);
