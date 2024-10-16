import { merchantFetch } from 'merchant/utils/ajax';

export const uploadInvoice = async (id: string, file: File): Promise<string> => {
  const data = new FormData();
  data.append('file', file);
  data.append('purpose', 'b2b_export_invoice');

  const response = await merchantFetch({
    url: 'documents',
    method: 'post',
    data,
  });
  if (response.success && response.data?.id) {
    return merchantFetch({
      url: `payment/${id}/update_b2b_invoice_details`,
      method: 'patch',
      data: {
        document_id: response.data?.id,
      },
    });
  }
  return Promise.reject(response);
};

/**
 *
 * @param irn - Input from user
 * @returns base64 string
 */
export const fetchInvoice = async (irn: string): Promise<string> => {
  const response = await merchantFetch({
    url: 'payments_cross_border_live/v1/merchant/documents',
    method: 'get',
    data: {
      type: 'irn',
      irn,
    },
  });
  if (response.success) {
    return response.data?.file;
  }
  return Promise.reject(response);
};
