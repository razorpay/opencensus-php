import { merchantFetch } from 'merchant/utils/ajax';

export const viewInvoice = async (documentId: string): Promise<string> => {
  const response = await merchantFetch({
    url: 'payments_cross_border_live/v1/merchant/documents',
    data: {
      type: 'document_details',
      document_id: documentId,
    },
  });
  const docUrl = response.data?.items?.[0]?.signed_url;
  if (docUrl) {
    return docUrl;
  }
  return Promise.reject(response);
};

export const deleteInvoice = async (documentId: string): Promise<boolean> => {
  const response = await merchantFetch({
    url: `payments_cross_border_live/v1/document/${documentId}`,
    method: 'DELETE',
  });
  if (response.success) {
    return true;
  }
  return Promise.reject(response);
};
