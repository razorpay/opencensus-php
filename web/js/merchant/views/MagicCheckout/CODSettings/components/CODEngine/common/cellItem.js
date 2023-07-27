import { TrashIcon, IconButton, EditComposeIcon } from '@razorpay/blade/components';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';

export const orderRange = {
  title: 'Order Range',
  value: (item) => item?.range || '-',
  columnClass: 'text-left',
};

export const orderRate = { title: 'Rate', value: (item) => item.rate || '-' };

export const zoneName = {
  title: 'Zone',
  value: (item) => item?.name || '',
  columnClass: 'text-left',
};

export const zoneCountry = {
  title: 'Country',
  value: (item) => {
    if (item.countries?.length > 2) {
      return `${item.countries.slice(0, 2).join(', ')} & more`;
    }
    return item?.countries?.join(', ') || '-';
  },
  columnClass: 'text-left',
};

export const zoneStates = {
  title: 'States',
  value: (item) => item?.state_count,
  columnClass: 'text-left',
};

export const slabRange = {
  title: 'Order range',
  columnClass: 'text-left',
  value: (item) =>
    `${getFormattedAmountNew(item?.rule?.order_amount?.gte || 0, true)} - ${getFormattedAmountNew(
      item?.rule?.order_amount?.lte,
      true,
    )}`,
};

export const slatRate = {
  title: 'Fee',
  columnClass: 'text-left',
  value: (item) => getFormattedAmountNew(item?.fee, true),
};

export const categoryName = {
  title: 'Category name',
  columnClass: 'text-left',
  value: (item) => item.name,
};

export const productCount = {
  title: 'Product count',
  columnClass: 'text-left',
  value: (item) => (!item.is_default ? item?.item_count || item?.items?.length : null),
};

export const actions = ({ onDeleteClick, onEditClick }) => ({
  title: 'Action',
  columnClass: 'text-left',
  value: (item) => {
    return item && !item.is_default ? (
      <div className="flex zone-actions">
        {onEditClick && <IconButton onClick={onEditClick(item.id)} icon={EditComposeIcon} />}
        {onDeleteClick && (
          <div className="delete-button">
            <IconButton
              aria-label="edit"
              onClick={onDeleteClick(item.id)}
              variant="primary"
              icon={TrashIcon}
            />
          </div>
        )}
      </div>
    ) : null;
  },
});
