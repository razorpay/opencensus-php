import xlsx from 'xlsx';

import store from 'merchant/store';
import { getDefaultUserObj } from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/User';
import {
  showNoExpiryPL,
  showPayerNamePL,
  showDynamicFields,
  convertExcelToObj,
} from 'merchant/views/PaymentLinks/utils';

const stateSpy = jest.spyOn(store, 'getState');

describe('Payment Page Helper', () => {
  afterEach(() => {
    stateSpy.mockClear();
  });

  test('should return true when the org feature flag "hide_no_expiry_for_pl" is disabled', () => {
    stateSpy.mockReturnValue({
      session: {
        user: getDefaultUserObj({
          features: [],
        }),
        org: {
          features: [],
        },
      },
    });
    expect(showNoExpiryPL()).toBe(true);
  });

  test('should return false when the org feature flag "hide_no_expiry_for_pl" is enabled & the merchant feature flag "enable_merchant_expiry_pp" is disabled', () => {
    stateSpy.mockReturnValue({
      session: {
        user: getDefaultUserObj({
          features: [],
        }),
        org: {
          features: ['hide_no_expiry_for_pl'],
        },
      },
    });
    expect(showNoExpiryPL()).toBe(false);
  });

  test('should return true when the org feature flag "hide_no_expiry_for_pl" & the merchant feature flag "enable_merchant_expiry_pl" are enabled', () => {
    stateSpy.mockReturnValue({
      session: {
        user: getDefaultUserObj({
          features: [
            {
              feature: 'enable_merchant_expiry_pl',
              value: true,
              display_name: 'Enables merchant to select no expiry while creating payment link.',
            },
          ],
        }),
        org: {
          features: ['hide_no_expiry_for_pl'],
        },
      },
    });
    expect(showNoExpiryPL()).toBe(true);
  });

  test('should return true when the org feature flag "enable_payer_name_for_pl" is enabled', () => {
    stateSpy.mockReturnValue({
      session: {
        user: {},
        org: {
          features: ['enable_payer_name_for_pl'],
        },
      },
    });
    expect(showPayerNamePL()).toBe(true);
  });

  test('should return false when the org feature flag "enable_payer_name_for_pl" is disabled', () => {
    stateSpy.mockReturnValue({
      session: {
        user: getDefaultUserObj({
          features: [],
        }),
        org: {},
      },
    });
    expect(showPayerNamePL()).toBe(false);
  });

  test('should return true when org feature flag "enable_payer_name_for_pl" and mid feature flag "dynamic_pl_offset" is enabled', () => {
    stateSpy.mockReturnValue({
      session: {
        user: getDefaultUserObj({
          features: [
            {
              display_name: 'Dynamic PL Offset',
              feature: 'dynamic_pl_offset',
              value: true,
            },
          ],
        }),
      },
    });

    expect(showDynamicFields()).toBe(true);
  });

  test('should return false when org feature flag "enable_payer_name_for_pl" is not enabled', () => {
    stateSpy.mockReturnValue({ session: { user: getDefaultUserObj() } });

    expect(showDynamicFields()).toBe(false);
  });
});

describe('convertExcelToObj', () => {
  test('should convert an Excel file to an array of objects', async () => {
    const sampleJson = [{ Name: 'Sample Name', Email: 'sample_email@gmail.com' }];

    // Mock the fetch request.
    const mockResponse = new Response(new ArrayBuffer(8));
    jest.spyOn(global, 'fetch').mockResolvedValue(mockResponse);

    // Mock the xlsx read function.
    const worksheet = xlsx.utils.json_to_sheet(sampleJson);

    const workbook = { SheetNames: ['Sheet1'], Sheets: { Sheet1: worksheet } };
    jest.spyOn(xlsx, 'read').mockReturnValue(workbook);

    const result = await convertExcelToObj('/files/sample_file.xlsx');

    expect(result).toEqual(sampleJson);
  });
});
