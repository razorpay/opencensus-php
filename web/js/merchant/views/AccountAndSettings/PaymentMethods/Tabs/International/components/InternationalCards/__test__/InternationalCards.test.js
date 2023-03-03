import { screen, userEvent } from 'test-utils';
import {
  renderApp,
  defaultProps,
  noActionReceivedProductStatus,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/__test__/mocks/InternationalCards';
import {
  getIsInternationalCardsDisabledReason,
  getRejectionInfo,
  getWorkflowUnderReviewBannerAndEta,
  isNCState,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils';
import {
  BannerType,
  ProductWorkflowStatesInBackend,
} from 'merchant/views/AccountAndSettings/PaymentMethods/typings';

describe('InternationalCards', () => {
  test('should render leaf list item header', () => {
    getIsInternationalCardsDisabledReason.mockReturnValueOnce('test');
    renderApp();
    expect(screen.getByTestId('leaf-list-item-name')).toHaveTextContent(
      defaultProps.instrument.name,
    );
    expect(screen.getByTestId('leaf-list-item-description')).toHaveTextContent(
      defaultProps.instrument.description,
    );
    expect(screen.queryByTestId('header-button')).toBeInTheDocument();
  });

  test('should render DisabledInternationalCardsSection only when no product is approved', () => {
    getIsInternationalCardsDisabledReason.mockReturnValueOnce('test disabled message');
    renderApp({
      productStatus: noActionReceivedProductStatus,
    });
    expect(screen.getByTestId('disabled-international-cards-section')).toHaveTextContent(
      'test disabled message',
    );
    expect(screen.queryByTestId('banner')).not.toBeInTheDocument();
    expect(screen.queryByTestId('ic-products')).not.toBeInTheDocument();
  });

  describe('When international cards are enabled', () => {
    beforeAll(() => {
      getIsInternationalCardsDisabledReason.mockReturnValue(null);
    });

    describe('HeaderButton', () => {
      test('should render Header Button component', () => {
        renderApp({
          productStatus: {
            payment_gateway: ProductWorkflowStatesInBackend.REJECTED,
            invoices: ProductWorkflowStatesInBackend.APPROVED,
          },
        });
        expect(screen.getByTestId('header-button')).toBeInTheDocument();
        expect(screen.getByText('isAnyProductApproved: true')).toBeInTheDocument();
        expect(screen.getByText('isAnyProductRequested: true')).toBeInTheDocument();
        expect(screen.getByText('hasUserDisabledInternationalCards: true')).toBeInTheDocument();
        expect(screen.getByText('isAnyProductInReview: false')).toBeInTheDocument();
        expect(screen.getByText('isAnyProductRejected: true')).toBeInTheDocument();
        expect(screen.getByText('isRequestRejectedFor90Days: false')).toBeInTheDocument();
        expect(screen.getByText('isNoProductApprovedOrInReview: false')).toBeInTheDocument();
      });

      test('should open questionnaire modal on clicking Request for international Cards', async () => {
        renderApp({
          productStatus: noActionReceivedProductStatus,
        });
        const requestForInternationalCards = screen.getByText('Request for international cards');
        expect(requestForInternationalCards).toBeInTheDocument();
        await userEvent.click(requestForInternationalCards);
        expect(screen.getByTestId('questionnaire-modal')).toBeInTheDocument();
        expect(screen.getByText('triggerSource: none'));
        expect(screen.getByText('isRevampFlow: true'));
      });
    });

    test('should render NC Banner when any Product is in review and isNCState returns true', () => {
      isNCState.mockReturnValueOnce(true);
      renderApp({
        productStatus: {
          payment_gateway: ProductWorkflowStatesInBackend.IN_REVIEW,
        },
      });
      expect(screen.getByText(`bannerType: ${BannerType.NEEDS_CLARIFICATION}`)).toBeInTheDocument();
      expect(screen.getByText('workflowEta: none')).toBeInTheDocument();
    });

    test('should render under review banner when any Product is in review and isNCState returns false', () => {
      isNCState.mockReturnValueOnce(false);
      getWorkflowUnderReviewBannerAndEta.mockReturnValueOnce({
        banner: BannerType.UNDER_REVIEW_BREACHED,
        eta: 'test-eta',
      });
      renderApp({
        productStatus: {
          payment_gateway: ProductWorkflowStatesInBackend.IN_REVIEW,
        },
        workflowInfo: {
          workflow_created_at: 'workflow_created_at',
        },
      });
      expect(
        screen.getByText(`bannerType: ${BannerType.UNDER_REVIEW_BREACHED}`),
      ).toBeInTheDocument();
      expect(screen.getByText('workflowEta: test-eta')).toBeInTheDocument();
    });

    test('should render rejected banner and rejected message when any Product is rejected', () => {
      getRejectionInfo.mockReturnValueOnce({
        reason: 'rejection reason',
        isRequestRejectedFor90Days: true,
      });
      renderApp({
        productStatus: {
          payment_gateway: ProductWorkflowStatesInBackend.REJECTED,
        },
        workflowInfo: {
          rejection_reason_message: 'rejection_reason_message',
          workflow_rejected_at: 'workflow_rejected_at',
        },
      });
      expect(screen.getByText(`bannerType: ${BannerType.REJECTED}`)).toBeInTheDocument();
      expect(screen.getByText('bannerMessage: rejection reason')).toBeInTheDocument();
      expect(screen.getByText('isRequestRejectedFor90Days: true')).toBeInTheDocument();
    });

    describe('Approved banner', () => {
      test.each([
        [
          'payment gateway banner message when payment gateway is approved',
          { payment_gateway: ProductWorkflowStatesInBackend.APPROVED },
          'payment gateway',
        ],
        [
          'ppli banner message when ppli is approved',
          { invoices: ProductWorkflowStatesInBackend.APPROVED },
          'payment pages, payment links, and invoices',
        ],
        [
          ' payment gateway and ppli banner message when payment gateway and ppli are approved',
          {
            payment_gateway: ProductWorkflowStatesInBackend.APPROVED,
            invoices: ProductWorkflowStatesInBackend.APPROVED,
          },
          'payment gateway, payment pages, payment links, and invoices',
        ],
      ])('should be shown and %s', (_, productStatus, bannerMessage) => {
        renderApp({
          productStatus,
        });

        expect(screen.getByText(`bannerType: ${BannerType.APPROVED}`)).toBeInTheDocument();
        expect(
          screen.getByText(
            new RegExp(
              `bannerMessage: You can now collect international card payments on ${bannerMessage}`,
            ),
          ),
        ).toBeInTheDocument();
      });

      describe('ICProducts', () => {
        beforeEach(() => {
          renderApp({
            productStatus: {
              payment_gateway: ProductWorkflowStatesInBackend.NO_ACTION_RECEIVED,
              invoices: ProductWorkflowStatesInBackend.APPROVED,
            },
            user: {
              international: true,
            },
          });
        });

        test('should be shown when user has enabled international', () => {
          expect(screen.getByTestId('ic-products')).toBeInTheDocument();
          expect(
            screen.getByText('IC Products - pgProductState: NOT_ACTIVATED'),
          ).toBeInTheDocument();
          expect(screen.getByText('IC Products - ppliProductState: ACTIVE')).toBeInTheDocument();
        });

        test("should show update business modal when request to activate is clicked from pg and user doesn't have business website", async () => {
          const requestToActivatePgBtn = screen.getByRole('button', {
            name: 'Request to activate - PG',
          });
          expect(requestToActivatePgBtn).toBeInTheDocument();
          await userEvent.click(requestToActivatePgBtn);
          expect(screen.getByTestId('update-business-modal')).toBeInTheDocument();
        });
      });
    });
  });
});
