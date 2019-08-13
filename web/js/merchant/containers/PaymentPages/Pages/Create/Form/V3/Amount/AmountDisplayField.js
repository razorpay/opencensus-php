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
                                name="field_1"
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
                  <input
                    className="Field-el"
                    placeholder="Enter Amount"
                    disabled
                  />
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
