import { reportConfigType } from 'merchant_common/views/Reports/configs/index';

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
