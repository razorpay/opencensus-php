import { merchantFetch } from 'merchant/utils/ajax';
import GenericEntity from './GenericEntity';

class PaymentUploadInvoice extends GenericEntity {
  resourceUrl = 'payments';

  async uploadInvoice(id: string, data: FormData): Promise<unknown> {
    const response = await merchantFetch({
      url: 'documents',
      method: 'post',
      data,
    });
    if (response.success && response.data?.id) {
      const payload = {
        document_id: response.data.id,
        document_type: data.get('purpose'),
      };

      // remove prefix
      if (payload.document_id.indexOf('doc_') === 0) {
        payload.document_id = payload.document_id.substring('doc_'.length);
      }

      if (id.indexOf('pay_') === 0) {
        id = id.substring('pay_'.length);
      }

      return merchantFetch({
        url: `payment/${id}/update_merchant_doc`,
        method: 'patch',
        data: payload,
      });
    }
    return Promise.reject(response);
  }

  getInvoice(documentId: string): Promise<unknown> {
    if (documentId.indexOf('doc_') === -1) {
      documentId = `doc_${documentId}`;
    }

    return merchantFetch({
      url: `documents/${documentId}`,
    });
  }
}

export default PaymentUploadInvoice;
