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
              <button type="button" disabled>
                -
              </button>
              <input
                class="Field-el counter-value"
                type="number"
                defaultValue={field.min_purchase}
                disabled
              />
              <button type="button" disabled>
                +
              </button>
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
        'Field Field--amount',
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

          {/*<input class="Field-el" disabled />*/}
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

/*TODO: Merge this and above component */
export const AmountField = ({ paymentPageEntity = {}, onAddAmount }) => {
  // console.log('PAYMENTPAGE ENTITY..', paymentPageEntity);
  let cls = 'Field Field--disabled Field--required';

  const isAmountEntitySet = paymentPageEntity.hasOwnProperty('amount');

  if (isAmountEntitySet) {
    if (!paymentPageEntity.amount) {
      cls += ' Field--small';
    }
  }

  const content = (
    <React.Fragment>
      <div class="Field-label">
        Amount
        <span class="symbol--red">*</span>
      </div>
      <div class="Field-content">
        <div class="Field-wrapper">
          {do {
            if (isAmountEntitySet) {
              if (paymentPageEntity.amount) {
                <React.Fragment>
                  <span>
                    <Amount
                      currency={paymentPageEntity.currency}
                      value={Number(paymentPageEntity.amount || 0) * 100}
                    />
                  </span>
                  {paymentPageEntity.settings &&
                    paymentPageEntity.settings.allow_multiple_units && (
                      <React.Fragment>
                        <span style={{ margin: '0 12px' }}>×</span>
                        <div class="Field Field--counter Field--small">
                          <div class="Field-content">
                            <div
                              class="Field-wrapper Field-wrapper--counter"
                              style={{
                                display: 'inline-block',
                                pointerEvents: 'none',
                              }}
                            >
                              <button type="button" disabled>
                                -
                              </button>
                              <input
                                class="Field-el counter-value"
                                defaultValue="1"
                                disabled
                              />
                              <button type="button" disabled>
                                +
                              </button>
                            </div>
                          </div>
                        </div>
                      </React.Fragment>
                    )}
                </React.Fragment>;
              } else {
                <React.Fragment>
                  <span class="Field-addon--before">
                    <AmountTooltip currency={paymentPageEntity.currency} />
                  </span>
                  <input class="Field-el" placeholder="Enter Amount" disabled />
                </React.Fragment>;
              }
            } else {
              <Button.Transparent
                onClick={onAddAmount}
                style={{ display: 'inline-block' }}
              >
                <span class="btn-link">+ Add Amount</span>
              </Button.Transparent>;
            }
          }}
        </div>
      </div>
    </React.Fragment>
  );

  return isAmountEntitySet ? (
    <EditLayer class={cls} onClick={onAddAmount}>
      {content}
      <i class="i i-edit" />
    </EditLayer>
  ) : (
    <div class={cls}>{content}</div>
  );
};
