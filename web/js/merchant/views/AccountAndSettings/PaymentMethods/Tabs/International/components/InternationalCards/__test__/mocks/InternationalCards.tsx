import { initialState as instrumentRequestInitialState } from 'merchant/reducers/instrumentRequests';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import InternationalCards, {
  Props as InternationalCardProps,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards';
import { ProductWorkflowStatesInBackend } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import React from 'react';
import { render } from 'test-utils';

jest.mock('merchant/views/Account/Profile/components/WorkflowRequests/utils', () =>
  Object.assign({
    ...jest.requireActual('merchant/views/Account/Profile/components/WorkflowRequests/utils'),
    isVisible: () => true,
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/HeaderButton',
  () => ({
    __esModule: true,
    default: (props) => {
      const {
        isRequestRejectedFor90Days,
        isAnyProductApproved,
        isAnyProductRequested,
        hasUserDisabledInternationalCards,
        isAnyProductInReview,
        isAnyProductRejected,
        isNoProductApprovedOrInReview,
      } = props;
      const HeaderButton = jest.requireActual(
        'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/HeaderButton',
      ).default;
      return (
        <div data-testid="header-button">
          <p>isAnyProductApproved: {isAnyProductApproved.toString()}</p>
          <p>isAnyProductRequested: {isAnyProductRequested.toString()}</p>
          <p>hasUserDisabledInternationalCards: {hasUserDisabledInternationalCards.toString()}</p>
          <p>isAnyProductInReview: {isAnyProductInReview.toString()}</p>
          <p>isAnyProductRejected: {isAnyProductRejected.toString()}</p>
          <p>isRequestRejectedFor90Days: {isRequestRejectedFor90Days.toString()}</p>
          <p>isNoProductApprovedOrInReview: {isNoProductApprovedOrInReview.toString()}</p>
          <HeaderButton {...props} />
          {/* <button onClick={() => openQuestionnaire()}>Request for international Cards</button> */}
        </div>
      );
    },
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/Banner',
  () => ({
    __esModule: true,
    default: ({ type, workflowEta, bannerMessage, pgProductState, ppliProductState }) => {
      return (
        <div data-testid="banner">
          <p>bannerType: {type}</p>
          <p>workflowEta: {workflowEta ? workflowEta : 'none'}</p>
          <p>bannerMessage: {bannerMessage}</p>
          <p>Banner - pgProductState: {pgProductState}</p>
          <p>Banner - ppliProductState: {ppliProductState}</p>
        </div>
      );
    },
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/ICProducts',
  () => ({
    __esModule: true,
    default: ({ pgProductState, ppliProductState, onRequestAccessClick }) => {
      return (
        <div data-testid="ic-products">
          <p>IC Products - pgProductState: {pgProductState}</p>
          <p>IC Products - ppliProductState: {ppliProductState}</p>
          <button onClick={() => onRequestAccessClick('pg')}>Request to activate - PG</button>
        </div>
      );
    },
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/DisabledInternationalCardsSection',
  () => ({
    __esModule: true,
    default: ({ reason }) => {
      return <p data-testid="disabled-international-cards-section">{reason}</p>;
    },
  }),
);

jest.mock('merchant/views/AccountAndSettings/PaymentMethods/components/LeafListItem', () => ({
  __esModule: true,
  LeafListItemHeader: ({ name, description, actionComponent }) => (
    <div>
      <p data-testid="leaf-list-item-name">{name}</p>
      <p data-testid="leaf-list-item-description">{description}</p>
      {actionComponent}
    </div>
  ),
}));

jest.mock(
  'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/UpdateBusinessDetailsModal',
  () => ({
    __esModule: true,
    default: () => <div data-testid="update-business-modal" />,
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils',
  () => ({
    ...(jest.requireActual(
      'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils',
    ) as Record<string, unknown>),
    getIsInternationalCardsDisabledReason: jest.fn(),
    isNCState: jest.fn(),
    getWorkflowUnderReviewBannerAndEta: jest.fn(),
    getRejectionInfo: jest.fn(),
  }),
);

jest.mock('merchant/views/Settings/Configuration/Questionnaire', () => ({
  __esModule: true,
  default: ({ onQuestionnaireSubmitSuccess, triggerSource, isRevampFlow }) => (
    <div data-testid="questionnaire-modal">
      <p>triggerSource: {triggerSource ? triggerSource : 'none'}</p>
      <p>isRevampFlow: {isRevampFlow.toString()}</p>
      <button type="button" onClick={onQuestionnaireSubmitSuccess}>
        Toggle Questionnaire success
      </button>
    </div>
  ),
}));

export const noActionReceivedProductStatus = {
  payment_gateway: ProductWorkflowStatesInBackend.NO_ACTION_RECEIVED,
  invoices: ProductWorkflowStatesInBackend.NO_ACTION_RECEIVED,
  payment_links: ProductWorkflowStatesInBackend.NO_ACTION_RECEIVED,
  payment_pages: ProductWorkflowStatesInBackend.NO_ACTION_RECEIVED,
};

export const defaultProps: Pick<
  InternationalCardProps,
  'instrument' | 'onQuestionnaireSubmitSuccess' | 'productStatus'
> = {
  instrument: instrumentRequestInitialState.pg[6],
  onQuestionnaireSubmitSuccess: jest.fn(),
  productStatus: {
    payment_gateway: ProductWorkflowStatesInBackend.NO_ACTION_RECEIVED,
    invoices: ProductWorkflowStatesInBackend.APPROVED,
    payment_links: ProductWorkflowStatesInBackend.APPROVED,
    payment_pages: ProductWorkflowStatesInBackend.APPROVED,
    products_pa_cb: ProductWorkflowStatesInBackend.APPROVED,
  },
};

export const defaultStoreState = {
  user: {},
};

export const renderApp = ({ productStatus = {}, workflowInfo = {}, user = {} } = {}) =>
  render(
    <InternationalCards
      {...defaultProps}
      productStatus={{
        ...defaultProps.productStatus,
        ...productStatus,
      }}
    />,
    {
      initialState: {
        session: {
          user: {
            business_website: null,
            international: false,
            isOrgRZP: true,
            ...user,
          },
        },
        workflows: {
          [WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI]: {
            workflow_exists: false,
            needs_clarification: null,
            permission: null,
            request_under_validation: false,
            tags: [],
            ...workflowInfo,
          },
        },
      },
      showModal: true,
    },
  );
