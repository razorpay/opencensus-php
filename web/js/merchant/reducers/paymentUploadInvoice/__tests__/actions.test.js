import * as Ajax from 'merchant/utils/ajax';
import { uploadInvoice } from 'merchant/reducers/paymentUploadInvoice/actions';

const merchantFetchSpy = jest.spyOn(Ajax, 'merchantFetch');

describe('Test PaymentUploadInvoice Actions', () => {
  test('should call PaymentUploadInvoice model uploadInvoice method', async () => {
    merchantFetchSpy.mockImplementation(() =>
      Promise.resolve({ success: true, data: { id: 'doc_123' } }),
    );

    const id = 'pay_123';
    const file = new File([''], 'invoice.pdf', { type: 'application/pdf' });
    const purpose = 'opgsp_invoice';
    const formData = new FormData();
    formData.append('file', file);
    formData.append('purpose', purpose);

    const action = await uploadInvoice(id, file, purpose);

    expect(merchantFetchSpy).toHaveBeenCalledWith({
      url: 'payment/123/update_merchant_doc',
      method: 'patch',
      data: {
        document_id: '123',
        document_type: purpose,
      },
    });
    expect(action).toEqual({ success: true, data: { id: 'doc_123' } });
  });
});
