import { merchantFetch } from 'merchant/utils/ajax';

export const saveInvoice = async (file: File, purpose: string): Promise<void> => {
  const formData = new FormData();
  formData.append('purpose', purpose);
  formData.append('file', file);
  await merchantFetch({
    url: 'payment/merchant_documents',
    method: 'post',
    data: formData,
  });
};
