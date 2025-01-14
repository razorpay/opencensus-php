import React, { useState } from 'react';
import { render, screen, waitFor, userEvent } from 'test-utils';

import Questionaire, {
  QuestionareProps,
} from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/components/Questionaire';
import {
  PolicyPageCreationFormFieldType,
  WebsitePolicyPages,
  WebsiteSubmitModalSteps,
} from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/types';

import { mockWebsiteVerificationPageStatusParitalSuccess } from '../../__test__/mocks/fixtures';
import { defaultPolicyPageCreationFormField } from '../constants';

const mockSetCurrentStep = jest.fn();
const mockShowNotification = jest.fn();
const defaultProps: QuestionareProps = {
  isMobile: false,
  isOpen: true,
  policyPagesToBeMade: [],
  mode: 'live',
  showNotification: mockShowNotification,
  setCurrentStep: mockSetCurrentStep,
  org: {
    business_name: 'Razorpay',
  },
  formState: defaultPolicyPageCreationFormField,
  setFormState: () => {},
};

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/utils',
  () => {
    const originalModule = jest.requireActual(
      'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/utils',
    );
    return {
      __esModule: true,
      ...originalModule,
      getWebsiteCount: jest.fn(() => {
        return 0;
      }),
    };
  },
);

const mockedWebsiteVerificationStage = {
  worklfow_exist: false,
  bvs_check_status: 'FAILED',
  mcc_check_status: 'PASSED',
};

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/hooks/useBusinessWebsiteData',
  () => {
    return jest.fn(() => ({
      websiteUpdateData: {
        website_verification_stage: mockedWebsiteVerificationStage,
        website_verification_page_status: mockWebsiteVerificationPageStatusParitalSuccess,
      },
    }));
  },
);

const App = (props) => {
  const [policyCreationState, setPolicyCreationState] = useState<PolicyPageCreationFormFieldType>(
    defaultPolicyPageCreationFormField,
  );
  return (
    <Questionaire
      {...props}
      formState={policyCreationState}
      setFormState={setPolicyCreationState}
    />
  );
};

const renderApp = (props = defaultProps) => {
  const renderOutput = render(<App {...props} />);
  return renderOutput;
};

describe('Questionaire', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockedWebsiteVerificationStage.mcc_check_status = 'PASSED';
  });

  describe('Render questionaire for TnC', () => {
    const policyPagesToBeMade = [WebsitePolicyPages.TERMS];
    const appProps = { ...defaultProps, policyPagesToBeMade };

    it('should render no further details required', () => {
      renderApp(appProps);

      expect(
        screen.getByText(
          `We’ll be creating the ‘Terms and Conditions’ page using your given details`,
        ),
      ).toBeInTheDocument();
    });

    it('should render no further details required on mobile', () => {
      renderApp({
        ...appProps,
        isMobile: true,
      });

      expect(
        screen.getByText(
          `We’ll be creating the ‘Terms and Conditions’ page using your given details`,
        ),
      ).toBeInTheDocument();
    });
  });

  describe('Render questionaire for Shipping', () => {
    const policyPagesToBeMade = [WebsitePolicyPages.SHIPPING];
    const appProps = { ...defaultProps, policyPagesToBeMade };

    it('should render shipping questionaire', () => {
      renderApp(appProps);

      const shippingPeriod = screen.getByTestId('question-shipping_period');
      const supportEmail = screen.getByTestId('support-email');
      const supportContact = screen.getByTestId('support-contact-number');
      const questionChips = screen.getByTestId('chips-shipping_period');

      expect(shippingPeriod).toBeInTheDocument();
      expect(supportContact).toBeInTheDocument();
      expect(supportEmail).toBeInTheDocument();
      expect(questionChips).toBeInTheDocument();
    });
  });

  describe('Render questionaire for Refund', () => {
    const policyPagesToBeMade = [WebsitePolicyPages.REFUND];
    const appProps = { ...defaultProps, policyPagesToBeMade };

    it('should render refund questionaire', () => {
      renderApp(appProps);

      const refundRequestPeriod = screen.getByTestId('question-refund_request_period');
      const refundProcessPeriod = screen.getByTestId('question-refund_process_period');
      const refundRequestPeriodChips = screen.getByTestId('chips-refund_request_period');
      const refundProcessPeriodChips = screen.getByTestId('chips-refund_process_period');

      expect(refundRequestPeriod).toBeInTheDocument();
      expect(refundProcessPeriod).toBeInTheDocument();
      expect(refundRequestPeriodChips).toBeInTheDocument();
      expect(refundProcessPeriodChips).toBeInTheDocument();
    });

    it('should render error msg on submit click without selection', async () => {
      renderApp(appProps);

      const submitButton = screen.getByRole('button', { name: 'Submit' });

      expect(submitButton).toBeInTheDocument();
      await userEvent.click(submitButton);

      await waitFor(() => {
        const errorMsg = screen.getAllByText('Please select an option');
        expect(errorMsg).toHaveLength(2);
      });
    });
  });

  describe('Render questionaire for Contact Us', () => {
    const policyPagesToBeMade = [WebsitePolicyPages.CONTACT];
    const appProps = { ...defaultProps, policyPagesToBeMade };

    it('should render Contact Us questionaire', () => {
      renderApp(appProps);

      const supportEmail = screen.getByTestId('support-email');
      const supportContact = screen.getByTestId('support-contact-number');

      expect(supportContact).toBeInTheDocument();
      expect(supportEmail).toBeInTheDocument();
    });
  });

  describe('Render questionaire for Privacy', () => {
    const policyPagesToBeMade = [WebsitePolicyPages.PRIVACY];
    const appProps = { ...defaultProps, policyPagesToBeMade };

    it('should render Privacy questionaire', () => {
      renderApp(appProps);

      const supportEmail = screen.getByTestId('support-email');

      expect(supportEmail).toBeInTheDocument();
    });
  });

  describe('go back', () => {
    const policyPagesToBeMade = [WebsitePolicyPages.SHIPPING];
    const appProps = { ...defaultProps, policyPagesToBeMade };

    it('should go back on CTA click', async () => {
      renderApp(appProps);

      const goBackBtn = screen.getByRole('button', { name: 'Go back' });
      expect(goBackBtn).toBeInTheDocument();
      await userEvent.click(goBackBtn);

      expect(mockSetCurrentStep).toHaveBeenCalledWith(
        WebsiteSubmitModalSteps.ADD_MISSING_POLICY_PAGES,
      );
    });
  });

  describe('submit form', () => {
    const policyPagesToBeMade = [WebsitePolicyPages.SHIPPING];
    const appProps = { ...defaultProps, policyPagesToBeMade };

    it('should submit form on CTA click and valid input', async () => {
      renderApp(appProps);

      await userEvent.click(screen.getByRole('radio', { name: 'Not Applicable' }));
      await userEvent.type(
        screen.getByRole('textbox', { name: 'Enter your support email ID' }),
        'abc@rzp.com',
      );
      await userEvent.type(
        screen.getByRole('textbox', { name: 'Enter your support contact number' }),
        '9999999999',
      );
      const submitButton = screen.getByRole('button', { name: 'Submit' });
      expect(submitButton).toBeInTheDocument();

      await userEvent.click(submitButton);

      await waitFor(() => {
        expect(mockShowNotification).toHaveBeenCalled();
      });
    });
  });
});
