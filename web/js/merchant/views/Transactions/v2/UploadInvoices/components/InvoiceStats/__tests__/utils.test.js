import {
  getPartnerDetials,
  isMerchantFullyOnboarded,
  getOnboardingCardDetails,
} from 'merchant/views/Transactions/v2/UploadInvoices/components/InvoiceStats/utils';
import {
  ONBOARDING_PARTNERS,
  ONBOARDING_STATUS,
} from 'merchant/views/Transactions/v2/UploadInvoices/components/InvoiceStats/constant';

describe('getPartnerDetials', () => {
  test.each([
    [
      'both partners present',
      [
        { name: ONBOARDING_PARTNERS.E_INVOICE, status: ONBOARDING_STATUS.ONBOARDED },
        { name: ONBOARDING_PARTNERS.GST_PORTAL, status: ONBOARDING_STATUS.ONBOARDED },
      ],
      {
        einvoice: { name: ONBOARDING_PARTNERS.E_INVOICE, status: ONBOARDING_STATUS.ONBOARDED },
        gstPortal: { name: ONBOARDING_PARTNERS.GST_PORTAL, status: ONBOARDING_STATUS.ONBOARDED },
      },
    ],
    [
      'only E_INVOICE present',
      [{ name: ONBOARDING_PARTNERS.E_INVOICE, status: ONBOARDING_STATUS.ONBOARDED }],
      {
        einvoice: { name: ONBOARDING_PARTNERS.E_INVOICE, status: ONBOARDING_STATUS.ONBOARDED },
        gstPortal: undefined,
      },
    ],
    [
      'only GST_PORTAL present',
      [{ name: ONBOARDING_PARTNERS.GST_PORTAL, status: ONBOARDING_STATUS.ONBOARDED }],
      {
        einvoice: undefined,
        gstPortal: { name: ONBOARDING_PARTNERS.GST_PORTAL, status: ONBOARDING_STATUS.ONBOARDED },
      },
    ],
    [
      'no partners present',
      [],
      {
        einvoice: undefined,
        gstPortal: undefined,
      },
    ],
  ])('should return correct details when %s', (_, input, expected) => {
    const [einvoice, gstPortal] = getPartnerDetials(input);
    expect(einvoice).toEqual(expected.einvoice);
    expect(gstPortal).toEqual(expected.gstPortal);
  });
});

describe('isMerchantFullyOnboarded', () => {
  test.each([
    [
      'both E_INVOICE and GST_PORTAL are onboarded',
      [
        { name: ONBOARDING_PARTNERS.E_INVOICE, status: ONBOARDING_STATUS.ONBOARDED },
        { name: ONBOARDING_PARTNERS.GST_PORTAL, status: ONBOARDING_STATUS.ONBOARDED },
      ],
      true,
    ],
    [
      'E_INVOICE is not onboarded',
      [{ name: ONBOARDING_PARTNERS.GST_PORTAL, status: ONBOARDING_STATUS.ONBOARDED }],
      false,
    ],
    [
      'GST_PORTAL is not onboarded',
      [{ name: ONBOARDING_PARTNERS.E_INVOICE, status: ONBOARDING_STATUS.ONBOARDED }],
      false,
    ],
    ['both E_INVOICE and GST_PORTAL are not onboarded', [], false],
    [
      'E_INVOICE is onboarded but GST_PORTAL status is missing',
      [{ name: ONBOARDING_PARTNERS.E_INVOICE, status: ONBOARDING_STATUS.ONBOARDED }],
      false,
    ],
    [
      'GST_PORTAL is onboarded but E_INVOICE status is missing',
      [{ name: ONBOARDING_PARTNERS.GST_PORTAL, status: ONBOARDING_STATUS.ONBOARDED }],
      false,
    ],
    ['both partner statuses are missing', [], false],
  ])('should return %s', (_, input, expected) => {
    expect(isMerchantFullyOnboarded(input)).toBe(expected);
  });
});

describe('getOnboardingCardDetails', () => {
  test.each([
    [
      'GST_PORTAL status is undefined',
      [{ name: ONBOARDING_PARTNERS.E_INVOICE, status: ONBOARDING_STATUS.ONBOARDED }],
      {
        bannerText: 'Tired of uploading invoice for every transaction?',
        buttonText: 'Link your GST account now',
        partner: ONBOARDING_PARTNERS.GST_PORTAL,
        status: undefined,
      },
    ],
    [
      'E_INVOICE status is undefined',
      [{ name: ONBOARDING_PARTNERS.GST_PORTAL, status: ONBOARDING_STATUS.ONBOARDED }],
      {
        bannerText: 'Complete just 1 more step to auto fetch e-invoices!',
        buttonText: 'Link your NIC portal now',
        partner: ONBOARDING_PARTNERS.E_INVOICE,
        status: undefined,
      },
    ],
    [
      'both statuses are defined',
      [
        { name: ONBOARDING_PARTNERS.E_INVOICE, status: ONBOARDING_STATUS.ONBOARDED },
        { name: ONBOARDING_PARTNERS.GST_PORTAL, status: ONBOARDING_STATUS.EXPIRED },
      ],
      {
        bannerText: 'Complete just 1 more step to auto fetch e-invoices!',
        buttonText: 'Resume linking of GST portal',
        partner: ONBOARDING_PARTNERS.GST_PORTAL,
        status: ONBOARDING_STATUS.EXPIRED,
      },
    ],
    [
      'no partner statuses provided',
      [],
      {
        bannerText: 'Tired of uploading invoice for every transaction?',
        buttonText: 'Link your GST account now',
        partner: ONBOARDING_PARTNERS.GST_PORTAL,
        status: undefined,
      },
    ],
  ])('should return correct onboarding card details when %s', (_, input, expected) => {
    const result = getOnboardingCardDetails(input);
    expect(result).toEqual(expected);
  });
});
