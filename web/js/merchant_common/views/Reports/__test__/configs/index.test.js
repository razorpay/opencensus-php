import { isJKOfflineMerchant } from '@libs/shared-utils';
import { reportConfigType } from 'merchant_common/views/Reports/configs/index';
import { getReportsDashboardConfig } from 'merchant_common/views/Reports/configs/refDashboard.config';
import { mockConfigs } from 'merchant_common/views/Reports/redux/__test__/fixtures/configs.fixtures';

const CONFIG = [
  { path: 'reports.transactions', propName: 'transactions' },
  { path: 'reports.refunds', propName: 'refunds' },
  { path: 'reports.raw_sql', propName: 'rawsql' },
  { path: 'reports.on_demand_settlements', propName: 'settlement_ondemands' },
  { path: 'reports.qr_codes', propName: 'qr_code' },
  { path: 'reports.payment_links', propName: 'paymentlinksv2' },
  { path: 'reports.contacts', propName: 'contacts' },
  { path: 'reports.payment_links', propName: 'payment_links' },
  { path: 'reports.custom', propName: 'custom' },
  { path: 'reports.refunds', propName: 'scrooge_refunds' },
  { path: 'reports.payment_report_with_offers', propName: 'Payments Report With Offers' },
  { path: 'reports.qr_code_report_with_pay_id', propName: 'QR Code Report with Pay_Id' },
  { path: 'reports.payment_btn_report', propName: 'Payment Button Report' },
  { path: 'reports.payment_pages', propName: 'Payment page' },
];

jest.mock('@libs/shared-utils', () => ({
  ...jest.requireActual('@libs/shared-utils'),
  isJKOfflineMerchant: jest.fn(),
}));

describe('test for reportConfigType function', () => {
  CONFIG.forEach(({ path, propName }) => {
    it(`should return true for ${path}`, () => {
      const i18 = {
        isConfigTagEnabled: jest.fn(),
      };
      i18.isConfigTagEnabled.mockImplementation((configPath) => configPath === path);
      const reportConfig = reportConfigType(i18);
      const isTrue = reportConfig[propName];
      expect(isTrue).toBe(true);
    });
  });
});

describe('Validate: getReportsDashboardConfig', () => {
  it('Should only return supported configs for J&K', () => {
    isJKOfflineMerchant.mockReturnValue(true);
    const { parseConfigs } = getReportsDashboardConfig('merchant', {
      user: {
        findTag: jest.fn(),
      },
    });
    const res = parseConfigs(mockConfigs);
    expect(res).toHaveLength(1);
  });

  it('Should return all the reports for non J&k', () => {
    isJKOfflineMerchant.mockReturnValue(false);
    const { parseConfigs } = getReportsDashboardConfig('merchant', {
      user: {
        findTag: jest.fn(),
      },
    });
    const res = parseConfigs(mockConfigs);
    expect(res).toHaveLength(4);
  });
});
