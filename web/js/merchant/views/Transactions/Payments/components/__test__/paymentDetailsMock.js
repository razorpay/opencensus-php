import ShowWhen from 'merchant/components/ShowWhen';

jest.mock('merchant/components/ShowWhen');

jest.mock('merchant/views/Transactions/Payments/components/PaymentPageDetails', () => () => (
  <div>PaymentPageDetails</div>
));

jest.mock('merchant/views/Transactions/Payments/components/OptimizerDetails', () => ({
  OptimizerDetails: ({ scrolledToBottom }) => (
    <div>
      OptimizerDetails <span>scrolledToBottom: {String(scrolledToBottom)}</span>
    </div>
  ),
}));

jest.mock(
  'merchant/views/Transactions/Payments/components/PaymentTransfers',
  () => ({ onCreateTransfer }) => (
    <>
      <div>PaymentTransfers</div>
      <button type="button" onClick={onCreateTransfer}>
        Create Transfer
      </button>
    </>
  ),
);

jest.mock(
  'merchant/views/Settlements/components/SettlementInfo',
  () => ({
    handleSettlementGuideClick,
    trackContactSupport,
    trackKnowMore,
    trackSameDaySettlement,
    trackSettlementClose,
  }) => (
    <>
      <div>SettlementInfo</div>
      <button type="button" onClick={handleSettlementGuideClick}>
        Settlement Guide
      </button>
      <button type="button" onClick={trackContactSupport}>
        Contact Support
      </button>
      <button type="button" onClick={trackKnowMore}>
        Know More
      </button>
      <button type="button" onClick={trackSameDaySettlement}>
        Track Same Day Settlement
      </button>
      <button type="button" onClick={trackSettlementClose}>
        Track Settlement Close
      </button>
    </>
  ),
);

jest.mock('merchant/views/Transactions/Payments/components/PaymentDisputes', () => () => (
  <div>PaymentDisputes</div>
));

jest.mock(
  'merchant/views/Transactions/Payments/components/PaymentMethod',
  () => ({ onUPIClick }) => (
    <>
      <div>PaymentMethod</div>
      <button type="button" onClick={onUPIClick}>
        UPI
      </button>
    </>
  ),
);

jest.mock(
  'merchant/views/Transactions/Payments/components/PaymentRefund',
  () => ({ onToggleClick }) => (
    <>
      <div>PaymentRefund</div>
      <button type="button" onClick={onToggleClick}>
        Refund Details Toggle
      </button>
    </>
  ),
);

jest.mock(
  'merchant/views/Transactions/Payments/components/PaymentReceipt',
  () => ({ onUpdateReferenceId }) => (
    <>
      <div>PaymentReceipt</div>
      <button type="button" onClick={onUpdateReferenceId}>
        Update Refrence Id
      </button>
    </>
  ),
);

beforeEach(() => {
  ShowWhen.mockImplementation(jest.requireActual('merchant/components/ShowWhen').default);
});

beforeAll(() => {
  Object.defineProperty(HTMLElement.prototype, 'scrollHeight', {
    configurable: true,
    value: 600,
  });
  Object.defineProperty(HTMLElement.prototype, 'clientHeight', {
    configurable: true,
    value: 400,
  });
  window.rzpQ = {
    component: jest.fn(),
    merchantActions: () => ({
      initiated: jest.fn(),
      success: jest.fn(),
    }),
  };
});
