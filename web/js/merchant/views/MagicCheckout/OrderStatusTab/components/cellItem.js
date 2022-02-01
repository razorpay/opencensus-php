import Button from 'common/new-ui/Button';
import { BATCH_STATUS } from 'merchant/views/MagicCheckout/OrderStatusTab/constants';
import { getTime } from 'common/ui/item';
import { batchAddressStatusLabel } from 'merchant/components/StatusLabel';

export const totalCount = { title: 'Total Rows', value: (item) => item.total_count || '-' };

export const processedCount = { title: 'Processed', value: (item) => item.success_count || '-' };

export const uploadedOn = { title: 'Uploaded On', value: getTime('created_at', 'DD MMM YYYY') };

export const actions = ({ onClick }) => ({
  title: 'Actions',
  value: (item) => {
    if (item.status === BATCH_STATUS.PROCESSED) {
      const createdAt = new Date(item.created_at * 1000);
      const expireAt = new Date();

      expireAt.setMonth(createdAt.getMonth() + 1);
      expireAt.setDate(createdAt.getDate());

      const disabled = new Date() > expireAt;

      return (
        <Button.Transparent
          className="font-normal"
          type="button"
          disabled={disabled}
          onClick={onClick(item.id)}
        >
          Rejected Statuses <i className="i i-download-blue" />
        </Button.Transparent>
      );
    }

    return null;
  },
});

export const status = {
  title: 'Status',
  value: (item) =>
    batchAddressStatusLabel({
      ...item,
      status:
        item.status === 'partially_processed'
          ? BATCH_STATUS.PROCESSING
          : item.status === 'failure'
          ? BATCH_STATUS.FAILED
          : item.status,
    }),
};

export const fileName = { title: 'File Name', value: (item) => item.name };
