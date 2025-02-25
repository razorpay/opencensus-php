import {
  getIsInternationalCardsDisabledReason,
  getProductState,
  getRejectionInfo,
  getWorkflowUnderReviewBannerAndEta,
  getPreferredProduct,
  scrollToPaypalSection,
  isNCState,
  getAnalyticsProductsInfo,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils';
import {
  DisabledInternationalCardsReasons,
  ICProductStates,
  InternationalCardsRejectionCodes,
  ProductTypeForAnalytics,
  ProductWorkflowStatesInBackend,
  BannerType,
} from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import 'jest-location-mock';
import { waitFor } from 'test-utils';

describe('International Cards Utils', () => {
  describe('getIsInternationalCardsDisabledReason', () => {
    test.each([
      [null, 'user is undefined', undefined],
      [
        null,
        'user is activated and has business website',
        { isAccepted: true, merchant: {}, business_website: 'test' },
      ],
      [null, 'user has enabled international', { international: true }],
      [
        DisabledInternationalCardsReasons.UNREGISTERED,
        'user is unregistered',
        { isUnregisteredBusiness: true, isAccepted: true },
      ],
      [
        DisabledInternationalCardsReasons.NOT_ACTIVATED,
        'user is not activated',
        { isAccepted: false },
      ],
      [
        DisabledInternationalCardsReasons.RISK_FOH,
        "user's funds are on hold due to risk",
        { merchant: { hold_funds: true }, isAccepted: true },
      ],
      [
        DisabledInternationalCardsReasons.NO_WEBSITE_DETAILS,
        "user doesn't have website details",
        { isAccepted: true, business_website: null },
      ],
    ])('should return %s if %s', (output, _, input) => {
      const reason = getIsInternationalCardsDisabledReason({ user: input });
      expect(reason).toBe(output);
    });
  });

  describe('getWorkflowUnderReviewBannerAndEta', () => {
    beforeAll(() => {
      jest.useFakeTimers('modern');
      jest.setSystemTime(1676973600000);
    });

    afterAll(() => {
      jest.useRealTimers();
    });

    test.each([
      [
        'should return under review banner when today is before first breachTime',
        1676887200,
        { banner: BannerType.UNDER_REVIEW, eta: 'Feb 22, 2023' },
      ],
      [
        'should return under review breached banner when today has crossed first breachTime',
        1676628000,
        { banner: BannerType.UNDER_REVIEW_BREACHED, eta: 'Feb 22, 2023' },
      ],
      [
        'should return under review breached again banner when today has crossed second breachTime',
        1676368800,
        { banner: BannerType.UNDER_REVIEW_BREACHED_AGAIN, eta: null },
      ],
    ])('%s', (_, workflowCreatedAt, output) => {
      expect(getWorkflowUnderReviewBannerAndEta(workflowCreatedAt)).toStrictEqual(output);
    });
  });

  describe('getRejectionInfo', () => {
    beforeAll(() => {
      jest.useFakeTimers('modern');
      jest.setSystemTime(1676973600000);
    });

    afterAll(() => {
      jest.useRealTimers();
    });

    test.each([
      [InternationalCardsRejectionCodes.RISK_REJECTION, true],
      [InternationalCardsRejectionCodes.MERCHANT_HIGH_CHARGEBACKS_FRAUD_PRESENT, true],
      [InternationalCardsRejectionCodes.DORMANT_MERCHANT, true],
      [InternationalCardsRejectionCodes.RESTRICTED_BUSINESS, true],
      [InternationalCardsRejectionCodes.CLARIFICATION_NOT_PROVIDED, false],
      [InternationalCardsRejectionCodes.WEBSITE_DETAIL_INCOMPLETE, false],
      [InternationalCardsRejectionCodes.BUSINESS_MODEL_MISMATCH, false],
      [InternationalCardsRejectionCodes.INVALID_DOCUMENTS, false],
    ])(
      'uses %s to return isRequestRejectedFor90Days: %s',
      (rejectionMessage, isRequestRejectedFor90Days) => {
        const rejectionInfo = getRejectionInfo(rejectionMessage, 1677060000);
        expect(rejectionInfo.isRequestRejectedFor90Days).toBe(isRequestRejectedFor90Days);
        if (isRequestRejectedFor90Days) {
          expect(rejectionInfo.reason).toMatch('Please submit a new request after May 23, 2023');
        } else {
          expect(rejectionInfo.reason).not.toMatch('Please submit a new request after ');
        }
      },
    );

    test('should not return true for isRequestRejectedFor90Days if workflow rejected date crosses 90 days', () => {
      const rejectionInfo = getRejectionInfo(
        InternationalCardsRejectionCodes.RISK_REJECTION,
        1657060000,
      );
      expect(rejectionInfo.isRequestRejectedFor90Days).toBe(false);
    });
  });

  describe('getProductState', () => {
    test.each([
      [
        { backendProductState: ProductWorkflowStatesInBackend.NO_ACTION_RECEIVED },
        ICProductStates.NOT_ACTIVATED,
      ],
      [
        { backendProductState: ProductWorkflowStatesInBackend.IN_REVIEW },
        ICProductStates.UNDER_REVIEW,
      ],
      [
        {
          backendProductState: ProductWorkflowStatesInBackend.IN_REVIEW,
          workflowInfo: { needs_clarification: 'test', tags: ['awaiting-customer-response'] },
        },
        ICProductStates.ACTION_REQUIRED,
      ],
      [{ backendProductState: ProductWorkflowStatesInBackend.APPROVED }, ICProductStates.ACTIVE],
      [
        {
          backendProductState: ProductWorkflowStatesInBackend.REJECTED,
          isRequestRejectedFor90Days: true,
        },
        ICProductStates.REJECTED,
      ],
      [
        {
          backendProductState: ProductWorkflowStatesInBackend.REJECTED,
          isRequestRejectedFor90Days: false,
        },
        ICProductStates.NOT_ACTIVATED,
      ],
      [{ backendProductState: '' }, null],
    ])(
      'uses %s arguments to return %s',
      ({ backendProductState, workflowInfo = false, isRequestRejectedFor90Days }, output) => {
        expect(
          getProductState({
            backendProductState,
            workflowInfo,
            isRequestRejectedFor90Days,
          }),
        ).toBe(output);
      },
    );
  });

  describe('getPreferredProduct', () => {
    test.each([
      [ProductTypeForAnalytics.All, ['payment_gateway', 'invoices', 'test']],
      [ProductTypeForAnalytics.PG, ['payment_gateway']],
      [ProductTypeForAnalytics.PPLI, ['invoices']],
      [null, ['test']],
    ])('should return %s on passing %s', (output, input) => {
      expect(getPreferredProduct(input)).toBe(output);
    });
  });

  describe('isNCState', () => {
    test.each([
      [false, {}],
      [false, { needs_clarification: 'test' }],
      [false, { tags: ['awaiting-customer-response'] }],
      [false, { needs_clarification: 'test', tags: ['customer-responded'] }],
      [true, { needs_clarification: 'test', tags: ['awaiting-customer-response'] }],
    ])('should return %s on passing %s as arguments', (output, input) => {
      expect(isNCState(input)).toBe(output);
    });
  });

  describe('scrollToPaypalSection', () => {
    const history = {
      replace: jest.fn(),
      push: jest.fn(),
    };

    const pathname = '/app/international-payments';

    const pathnameWithoutApp = pathname.replace('/app', '');
    const paypalQueryParam = 'instrument=paypal';
    const queryParamTwo = 'key2=value2';

    test(`should only call push when url doesn't have  ${paypalQueryParam}`, () => {
      window.location.assign(pathname);
      scrollToPaypalSection(history);

      expect(history.push).toHaveBeenCalledWith({
        pathname: pathnameWithoutApp,
        search: paypalQueryParam,
      });
    });

    test(`should replace url by removing ${paypalQueryParam} first and add ${paypalQueryParam} again`, async () => {
      window.location.assign(`${pathname}?${paypalQueryParam}`);
      scrollToPaypalSection(history);

      expect(history.replace).toHaveBeenCalledWith({
        pathname: pathnameWithoutApp,
        search: '',
      });

      await waitFor(() => {
        expect(history.push).toHaveBeenCalledWith({
          pathname: pathnameWithoutApp,
          search: paypalQueryParam,
        });
      });
    });

    test(`should replace url by removing ${paypalQueryParam}, retaining existing query params and add ${paypalQueryParam} again`, async () => {
      window.location.assign(`${pathname}?${paypalQueryParam}&${queryParamTwo}`);
      scrollToPaypalSection(history);

      expect(history.replace).toHaveBeenCalledWith({
        pathname: pathnameWithoutApp,
        search: queryParamTwo,
      });

      await waitFor(() => {
        expect(history.push).toHaveBeenCalledWith({
          pathname: pathnameWithoutApp,
          search: `${paypalQueryParam}&${queryParamTwo}`,
        });
      });
    });
  });

  describe('getAnalyticsProductsInfo', () => {
    test.each([
      [ProductTypeForAnalytics.All, ICProductStates.ACTIVE, ICProductStates.ACTIVE],
      [ProductTypeForAnalytics.PG, ICProductStates.ACTIVE, ICProductStates.ACTION_REQUIRED],
      [ProductTypeForAnalytics.PPLI, ICProductStates.ACTION_REQUIRED, ICProductStates.ACTIVE],
    ])(
      'should return %s on passing pgProductState: %s ppliProductState: %s',
      (output, pgProductState, ppliProductState) => {
        expect(
          getAnalyticsProductsInfo(pgProductState, ppliProductState, BannerType.APPROVED),
        ).toBe(output);
      },
    );
  });
});
