import { merchantFetch } from 'merchant/utils/ajax';

export const saveInvoice = async (file: File): Promise<void> => {
  const formData = new FormData();
  formData.append('purpose', 'opgsp_invoice');
  formData.append('file', file);
  await merchantFetch({
    url: 'payment/merchant_documents',
    method: 'post',
    data: formData,
  });
};
