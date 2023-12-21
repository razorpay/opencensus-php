import PaymentUploadInvoice from 'merchant/models/PaymentUploadInvoice';
import * as Ajax from 'merchant/utils/ajax';

const merchantFetchSpy = jest.spyOn(Ajax, 'merchantFetch');

describe('Test PaymentUploadInvoice', () => {
  test('should have uploadInvoice and getInvoice methods', () => {
    const instance = new PaymentUploadInvoice();

    expect(instance.uploadInvoice).toBeDefined();
    expect(instance.getInvoice).toBeDefined();
  });

  test('should upload invoice and return response', async () => {
    merchantFetchSpy.mockImplementation(() =>
      Promise.resolve({ success: true, data: { id: 'doc_123' } }),
    );

    const id = 'pay_123';
    const data = new FormData();
    data.append('purpose', 'invoice');
    const instance = new PaymentUploadInvoice();

    const response = await instance.uploadInvoice(id, data);

    expect(merchantFetchSpy).toHaveBeenCalledWith({
      url: 'documents',
      method: 'post',
      data,
    });

    expect(response).toEqual({
      success: true,
      data: { id: 'doc_123' },
    });
  });

  test('should reject promise if upload fails', async () => {
    merchantFetchSpy.mockImplementation(() => Promise.resolve({ success: false }));

    const id = 'pay_123';
    const data = new FormData();
    data.append('purpose', 'invoice');
    const instance = new PaymentUploadInvoice();

    await expect(instance.uploadInvoice(id, data)).rejects.toEqual({
      success: false,
    });
  });
});
