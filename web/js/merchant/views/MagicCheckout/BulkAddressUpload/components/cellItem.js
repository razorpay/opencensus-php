import Button from 'common/new-ui/Button';
import { BATCH_STATUS } from 'merchant/views/MagicCheckout/BulkAddressUpload/constants';
import { getTime } from 'common/ui/item';
import { batchAddressStatusLabel } from 'merchant/components/StatusLabel';

export const totalCount = { title: 'Total Rows', value: (item) => item.total_count || '-' };

export const processedCount = { title: 'Processed', value: (item) => item.success_count || '-' };

export const uploadedOn = { title: 'Uploaded On', value: getTime('created_at', 'DD MMM YYYY') };

export const actions = ({ downloadFailedAddress }) => ({
  title: 'Actions',
  value: (item) => {
    let result;

    // Don't render anything if all rows were processed
    if (item.success_count === item.total_count) {
      return null;
    }

    switch (item.status) {
      case BATCH_STATUS.PROCESSED: {
        const createdAt = new Date(item.created_at * 1000);
        const expireAt = new Date();

        expireAt.setMonth(createdAt.getMonth() + 1);
        expireAt.setDate(createdAt.getDate());

        const disabled = new Date() > expireAt;

        result = (
          <Button.Transparent
            type="button"
            className="font-normal"
            disabled={disabled}
            onClick={downloadFailedAddress(item.id)}
          >
            Failed Addresses <i className="i i-download-blue" />
          </Button.Transparent>
        );
        break;
      }
      default:
        result = null;
    }
    return result;
  },
});

export const fileName = ({ onClick }) => ({
  title: 'File Name',
  value: (item) => {
    const createdAt = new Date(item.created_at * 1000);
    const expireAt = new Date();

    expireAt.setMonth(createdAt.getMonth() + 1);
    expireAt.setDate(createdAt.getDate());

    const disabled = new Date() > expireAt;

    return (
      <Button.Transparent
        className="file-name-button"
        type="button"
        disabled={disabled}
        onClick={onClick(item.id)}
      >
        {item.name}
      </Button.Transparent>
    );
  },
});

export const status = {
  title: 'Status',
  value: (item) =>
    batchAddressStatusLabel({
      ...item,
      status: item.status === 'partially_processed' ? BATCH_STATUS.PROCESSING : item.status,
    }),
};
