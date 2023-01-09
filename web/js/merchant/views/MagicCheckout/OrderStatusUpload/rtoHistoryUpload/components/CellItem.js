import { SHIPPING_PROVIDERS_MAPPING } from 'merchant/views/MagicCheckout/OrderStatusUpload/rtoHistoryUpload/constants';

export const fileId = { title: 'File Id', value: (item) => item.file_id || '-' };

export const shippingProvider = {
  title: 'Shipping Provider',
  value: (item) => SHIPPING_PROVIDERS_MAPPING[item?.shipping_provider?.toLowerCase()] || '-',
};

export const createdAt = {
  title: 'Uploaded on',
  value: (item) => {
    if (!item.created_at) return '-';

    const date = new Date(item.created_at * 1000);
    const fullDate = `${date.getDate()} ${date.toLocaleString('default', {
      month: 'short',
    })} ${date.getFullYear()}`;
    return fullDate;
  },
};

export const status = {
  title: 'Status',
  value: () => <span className="status-label">Uploaded</span>,
};
