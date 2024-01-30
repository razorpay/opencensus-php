import { merchantFetch } from 'merchant/utils/ajax';
import GenericEntity from './GenericEntity';

class B2bExportsPayments extends GenericEntity {
  resourceUrl = 'payments';

  fetchAll(params = {}) {
    return super.fetchAll({ ...params, intl_bank_transfer: 1, 'expand[]': 'sender_address' });
  }

  async uploadInvoice(id, data) {
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
  }

  getInvoice(documentId) {
    return merchantFetch({
      url: `documents/${documentId}`,
    });
  }
}

export default B2bExportsPayments;
