import * as Ajax from 'merchant/utils/ajax';
import {
  dateObject,
  generateBankFirs,
} from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/__test__/fixtures';
import { FileType } from 'merchant/views/AccountAndSettings/InternationalSettings/constants';
import {
  fetchFirsData,
  requestInternalFirs,
  downloadFirsFile,
} from 'merchant/views/AccountAndSettings/InternationalSettings/services';
import * as utils from 'merchant/views/AccountAndSettings/InternationalSettings/utils';

describe('FIRS API Functions', () => {
  const { year, month } = dateObject;
  const document_id = '123456';

  test('fetchFirsData should return formatted data for a given year', async () => {
    const firsFiles = generateBankFirs([FileType.FIRS_FILE]);
    const mockResponse = {
      success: true,
      data: {
        year,
        months: {
          [month]: firsFiles,
        },
      },
    };
    jest.spyOn(Ajax, 'merchantFetch').mockResolvedValue(mockResponse);
    const formatFirsData = jest.spyOn(utils, 'formatFirsData').mockReturnValue(true);

    // Call the fetchFirsData function
    const result = await fetchFirsData(year);

    // Check if the function returns the expected data
    expect(formatFirsData).toHaveBeenCalledWith(mockResponse.data.months, year);
    expect(result).toEqual({
      [year]: true,
    });
  });

  test('fetchFirsData should throw an error when the request fails', async () => {
    jest.spyOn(Ajax, 'merchantFetch').mockRejectedValue(new Error('Request failed'));

    await expect(fetchFirsData(year)).rejects.toThrow('Data fetch failed! Please try again');
  });

  test('requestInternalFirs should return data for internal FIRS request', async () => {
    const mockResponse = {
      success: true,
      data: {
        document_id,
        month,
        year,
      },
    };
    jest.spyOn(Ajax, 'merchantFetch').mockResolvedValue(mockResponse);

    const result = await requestInternalFirs(month, year);

    expect(result).toEqual(mockResponse.data);
  });

  test('requestInternalFirs should throw an error when the request fails', async () => {
    jest.spyOn(Ajax, 'merchantFetch').mockRejectedValue(new Error('Request failed'));

    // Check if the function throws an error
    await expect(requestInternalFirs(month, year)).rejects.toThrow(
      'Something went wrong! please try again in some time',
    );
  });

  test('downloadFirsFile should open a file in a new window', async () => {
    const mockResponse = {
      success: true,
      data: {
        signed_url: 'http://example.com/sample.pdf',
      },
    };
    jest.spyOn(Ajax, 'merchantFetch').mockResolvedValue(mockResponse);

    // Mock the window.open function
    window.open = jest.fn();
    await downloadFirsFile(month, year, document_id);

    // Check if window.open was called with the correct URL
    expect(window.open).toHaveBeenCalledWith('http://example.com/sample.pdf', '_blank');
  });

  test('downloadFirsFile should throw an error when the request fails', async () => {
    jest.spyOn(Ajax, 'merchantFetch').mockRejectedValue(new Error('Request failed'));

    // Check if the function throws an error
    await expect(downloadFirsFile(month, year, document_id)).rejects.toThrow(
      'There was an error in downloading the file. Please try again',
    );
  });
});
