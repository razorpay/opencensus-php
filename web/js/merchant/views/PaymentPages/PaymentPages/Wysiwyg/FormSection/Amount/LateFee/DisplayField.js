import { getCurrency } from 'common/ui/Amount';
import { classList } from 'common/utils/rzp-utils';
import CreatorManager from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/CreatorManager';
import DragHandle from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/LateFee/DragHandle';
import { LATE_FEE_TYPES_MAP } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/fieldTypes';
import EditLayer from 'merchant/views/PaymentPages/PaymentPages/components/EditLayer';
import { BATCH_UPLOAD_MSG } from 'merchant/views/PaymentPages/PaymentPages/constants';

const displayField = ({ field, currency, openBaseForm, setRef, isListSorting }) => {
  const currencySymbol = getCurrency(currency).symbol;

  const { item, settings } = field;

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
        {item.name}
        {!field.mandatory && <div className="text-optional">(Optional)</div>}
        <div className="text-optional">{`(${
          LATE_FEE_TYPES_MAP[settings?.late_fee_config?.late_fee_type]
        })`}</div>
      </div>

      <div className="Field-content">
        <div className={classList('Field-wrapper', field._type && `Field-wrapper--${field._type}`)}>
          <span className="Field-addon Field-addon--before">
            <span>
              <b className="currency-symbol">{currencySymbol}</b>
            </span>
          </span>

          <input className="Field-el" type="number" placeholder={BATCH_UPLOAD_MSG} disabled />
        </div>

        {field?.item?.description && (
          <div className="Field-description">{field.item.description}</div>
        )}

        <div className="Field-description note">
          The due date post which the Late Payment Charge will be levied has to be updated in the
          Batch Upload File.
        </div>
      </div>

      {openBaseForm && <i className="i i-edit" />}
    </EditLayer>
  );
};

// eslint-disable-next-line babel/new-cap
export default CreatorManager(displayField);
