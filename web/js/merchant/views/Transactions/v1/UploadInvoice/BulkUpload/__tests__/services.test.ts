import * as Ajax from 'merchant/utils/ajax';
import { saveInvoice } from 'merchant/views/Transactions/v1/UploadInvoice/BulkUpload/services';

const merchantFetchSpyOn = jest.spyOn(Ajax, 'merchantFetch');

describe('Test saveInvoice', () => {
  test('should send a request to save the invoice', async () => {
    const file = new File([''], 'invoice.pdf');
    const purpose = 'invoice';

    merchantFetchSpyOn.mockResolvedValue(Promise.resolve({}));

    await saveInvoice(file, purpose);

    expect(merchantFetchSpyOn).toHaveBeenCalledWith({
      url: 'payment/merchant_documents',
      method: 'post',
      data: expect.any(FormData),
    });
  });
});
