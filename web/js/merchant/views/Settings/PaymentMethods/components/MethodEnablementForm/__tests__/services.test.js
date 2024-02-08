import * as Ajax from 'merchant/utils/ajax';
import {
  fetchAdditionalDocumentFormData,
  saveAdditionalDocumentFormData,
  submitAdditionalDocumentFormData,
} from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/services';
import * as utils from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/utils';

describe('Test fetchAdditionalFormData', () => {
  const merchantFetchSpyOn = jest.spyOn(Ajax, 'merchantFetch');

  test('should make a get call to fetch form details', async () => {
    merchantFetchSpyOn.mockReturnValue(Promise.resolve({ success: true }));
    jest.spyOn(utils, 'formatApiResponse').mockReturnValue({ data: 'Dummy data' });

    const response = await fetchAdditionalDocumentFormData();

    expect(response).toEqual({ data: 'Dummy data' });

    expect(merchantFetchSpyOn).toHaveBeenCalledWith('international_enablement');
  });

  test('should return empty object in case of error', async () => {
    merchantFetchSpyOn.mockReturnValue(
      Promise.reject({ errors: ['Bad request', 'http 400 status'] }),
    );

    const response = await fetchAdditionalDocumentFormData();
    expect(response).toStrictEqual({});
  });
});

describe('Test saveAdditionalDocumentFormData', () => {
  const merchantFetchSpyOn = jest.spyOn(Ajax, 'merchantFetch');

  test('should make a get call to fetch form details', async () => {
    merchantFetchSpyOn.mockReturnValue(Promise.resolve({ success: true }));
    jest.spyOn(utils, 'generateApiData').mockReturnValue({ data: 'Dummy data' });

    await saveAdditionalDocumentFormData();

    expect(merchantFetchSpyOn).toHaveBeenCalledWith({
      url: 'international_enablement/draft',
      method: 'post',
      data: { data: 'Dummy data' },
    });
  });

  test('should return proper error message in case of error', async () => {
    merchantFetchSpyOn.mockReturnValue(
      Promise.reject({ errors: ['Bad request', 'http 400 status'] }),
    );

    try {
      await saveAdditionalDocumentFormData();
    } catch (errors) {
      expect([errors]).toEqual([
        new Error("Data couldn't be saved because of some intermittent issue! Please try again."),
      ]);
    }
  });
});

describe('Test submitAdditionalDocumentFormData', () => {
  const merchantFetchSpyOn = jest.spyOn(Ajax, 'merchantFetch');

  test('should make a get call to fetch form details', async () => {
    merchantFetchSpyOn.mockReturnValue(Promise.resolve({ success: true }));
    jest.spyOn(utils, 'generateApiData').mockReturnValue({ data: 'Dummy data' });

    await submitAdditionalDocumentFormData();

    expect(merchantFetchSpyOn).toHaveBeenCalledWith({
      url: 'international_enablement/submit',
      method: 'post',
      data: { data: 'Dummy data' },
    });
  });

  test('should return proper error message in case of error', async () => {
    merchantFetchSpyOn.mockReturnValue(
      Promise.reject({ errors: ['Bad request', 'http 400 status'] }),
    );

    try {
      await submitAdditionalDocumentFormData();
    } catch (errors) {
      expect([errors]).toEqual([
        new Error("Data wasn't submitted because of some intermittent issue! Please try again."),
      ]);
    }
  });
});
