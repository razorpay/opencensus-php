import { server } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/Details/__tests__/mocks/handlers';
import { failureBannerHandlers } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/FailureBanner/__tests__/mocks/handlers';

export const getMockUser = () => ({
  promoter_pan_name: 'pan_name',
  iec_code: 'iec_code',
  merchant: {
    purpose_code: 'purpose_code',
    category: 'category',
  },
});

export const vkycFailureBannerTests = [
  {
    test: 'should show no iec code banner',
    input: () => {
      server.use(
        failureBannerHandlers.successEddDetails(),
        failureBannerHandlers.successWithoutIecCode(),
      );
    },
    output: () => /Update your IEC code to activate international bank transfers/,
  },
  {
    test: 'should show edd not verified banner',
    input: () => {
      server.use(
        failureBannerHandlers.successEddDetails(),
        failureBannerHandlers.successPurposeCode(),
      );
    },
    output: () =>
      /International bank transfers are not yet enabled. To enable, please complete your video KYC./,
  },
  {
    test: 'should show vkyc approved banner',
    input: () => {
      server.use(
        failureBannerHandlers.successVkycApproved(),
        failureBannerHandlers.successPurposeCode(),
      );
    },
    output: () =>
      /Your Video Based Customer Identification process \(V-CIP\) is successfully completed and verified./,
  },
  {
    test: 'should show vkyc rejected banner',
    input: () => {
      server.use(
        failureBannerHandlers.successVkycRejected(),
        failureBannerHandlers.successPurposeCode(),
      );
    },
    output: () => /We couldn't approve your request./,
  },
  {
    test: 'should show vkyc under review banner',
    input: () => {
      server.use(
        failureBannerHandlers.successVkycUnderReview(),
        failureBannerHandlers.successPurposeCode(),
      );
    },
    output: () => /Your video KYC details are under review. This can take up to 24 hours./,
  },
  {
    test: 'should show vkyc initiated banner',
    input: () => {
      server.use(
        failureBannerHandlers.successVkycInitiated(),
        failureBannerHandlers.successPurposeCode(),
      );
    },
    output: () =>
      /Video KYC is currently in progress and must be completed exclusively by the authorized signatory./,
  },
  {
    test: 'should show vkyc fraud rejected banner',
    input: () => {
      server.use(
        failureBannerHandlers.successVkycFraudRejected(),
        failureBannerHandlers.successPurposeCode(),
      );
    },
    output: () => /Unfortunately we couldn't approve your request since/,
  },
  {
    test: 'should show vkyc failed banner',
    input: () => {
      server.use(
        failureBannerHandlers.successVkycFailed(),
        failureBannerHandlers.successPurposeCode(),
      );
    },
    output: () => /We couldn't approve your request./,
  },
];
