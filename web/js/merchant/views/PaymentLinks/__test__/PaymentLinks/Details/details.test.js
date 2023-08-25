import React from 'react';
import {
  App,
  generateUser,
  paymentLinkConfig,
} from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/Details';
import { render, screen, userEvent, fireEvent } from 'test-utils';

import * as analytics from 'common/utils/analytics';

describe('Payment Link Details', () => {
  const trackEditNotes = jest.fn();
  const editPaymentLink = jest.fn();
  const notifyCustomerFnMock = jest.fn();
  const onCancelMock = jest.fn();
  const editReceiptMock = jest.fn();
  const nextReminders = [1619860800, 1620292800, 1620724800];

  beforeAll(() => {
    jest.useFakeTimers();
    window.rzp_user = {};
    window.hj = jest.fn();
    window.rzpAnalytics = jest.fn();
    jest.spyOn(analytics, 'analyticsTrack').mockImplementation(jest.fn);

    window.rzpQ = {
      component: jest.fn(),
      paymentLinks: () => ({
        interaction: jest.fn(),
      }),
    };
  });

  beforeEach(() => {
    jest.clearAllMocks();
  });

  afterEach(() => {
    jest.useRealTimers();
  });

  const renderApp = (props = {}) => {
    return render(
      <App
        {...props}
        user={props.user || generateUser()}
        nextReminders={nextReminders}
        trackEditNotes={trackEditNotes}
        editPaymentLink={editPaymentLink}
        isPaymentLinksRemindersEnabled={false}
        paymentlink={props.getPaymentLinkConfig || paymentLinkConfig()}
        isRoleAllowedEdit
      />,
      {
        initialState: {
          session: {
            user: {
              isPaymentlinksV2Enabled: true,
            },
          },
        },
      },
    );
  };

  test('component "Details" should be defined', () => {
    expect(App).toBeDefined();
  });

  test('should have Payment Link "Id" in document', () => {
    renderApp();
    const paymentLinkId = screen.getByText('plink_Km8evXT2XtGXc7');
    expect(paymentLinkId).toBeInTheDocument();
  });

  test('should have Add New Notes CTA in the document with following values', async () => {
    const { getByPlaceholderText, getByText } = renderApp();
    jest.spyOn(window, 'setTimeout').mockImplementation((cb) =>
      setTimeout(() => {
        cb();
      }, 1000),
    );
    const addNewCTA = screen.getByRole('button', {
      name: /Add New/i,
    });
    expect(addNewCTA).toBeInTheDocument();
    await userEvent.click(addNewCTA);
    expect(getByText('Cancel')).toBeInTheDocument();
    expect(getByText('Save')).toBeInTheDocument();
    expect(getByText('Resend Link')).toBeInTheDocument();
    const paymentLinkUrl = getByText('https://rzp.io/i/GN3SOBucBc');
    userEvent.click(paymentLinkUrl);
    const descriptionElement = getByPlaceholderText('Description (value)');
    const titleElement = getByPlaceholderText('Title (key)');
    fireEvent.change(descriptionElement, { target: { value: 'abc' } });
    fireEvent.change(titleElement, { target: { value: 'title' } });
    expect(descriptionElement.value).toBe('abc');
    expect(titleElement.value).toBe('title');
  });

  test('should render the Customer Details in the document', async () => {
    renderApp();
    const customerDetails = await screen.findByText(/Customer Details/i);
    expect(customerDetails).toBeInTheDocument();
    expect(customerDetails).toHaveClass('pair-label');
  });

  test('should render following labels', () => {
    renderApp();
    expect(screen.getByText('Notes')).toBeInTheDocument();
    expect(screen.getByText('Amount')).toBeInTheDocument();
    expect(screen.getByText('Link Url')).toBeInTheDocument();
    expect(screen.getByText('Reminders')).toBeInTheDocument();
    expect(screen.getByText('Expires On')).toBeInTheDocument();
    expect(screen.getByText('Created By')).toBeInTheDocument();
    expect(screen.getByText('Receipt No.')).toBeInTheDocument();
    expect(screen.getByText('Edit Expiry')).toBeInTheDocument();
  });

  test('should render only loader and no label in the document', () => {
    const data = {
      upiLink: true,
      status: 'partially_paid',
      email_status: 'sent',
      partial_payment: true,
    };
    const getPaymentLinkConfig = paymentLinkConfig(data);
    const props = {
      getPaymentLinkConfig,
      isLoading: true,
      isPaymentLinksRemindersEnabled: true,
      isAutoRemindersUpdating: true,
    };
    renderApp(props);
    expect(screen.queryByText(/Amount/i)).not.toBeInTheDocument();
  });

  test('should render paid on in the document', () => {
    const data = {
      upiLink: true,
      email_status: 'sent',
      partial_payment: true,
      reminders: {
        status: 'failed',
      },
    };
    const getPaymentLinkConfig = paymentLinkConfig(data);
    delete getPaymentLinkConfig.status;
    delete getPaymentLinkConfig.sms_status;
    delete getPaymentLinkConfig.user;
    delete getPaymentLinkConfig.user_id;
    delete getPaymentLinkConfig.customer_details.customer_email;
    const props = {
      getPaymentLinkConfig,
      isLoading: true,
      isPaymentLinksRemindersEnabled: true,
      isAutoRemindersUpdating: true,
    };
    renderApp(props);
    expect(screen.queryByText(/Paid on/i)).not.toBeInTheDocument();
  });

  test('should render Resend link and user should be able to click on the resend link', async () => {
    const data = {
      upiLink: true,
      status: 'partially_paid',
      partial_payment: true,
    };
    const getPaymentLinkConfig = paymentLinkConfig(data);
    const props = {
      getPaymentLinkConfig,
      isLoading: false,
      isPaymentLinksRemindersEnabled: true,
      isAutoRemindersUpdating: true,
      notifyCustomer: notifyCustomerFnMock,
    };
    renderApp(props);
    const resendLinkCTA = screen.getByText('Resend Link');
    expect(resendLinkCTA).toBeInTheDocument();
    await userEvent.click(resendLinkCTA);
    expect(notifyCustomerFnMock).toHaveBeenCalled();
  });

  test('should render "UPI Payment Link" as payment link type', () => {
    const data = {
      upiLink: true,
      status: 'partially_paid',
      partial_payment: true,
    };
    const mockUserData = {
      isPaymentlinksV2Enabled: true,
      isPaymentLinkCreationV2Enabled: true,
    };
    const getPaymentLinkConfig = paymentLinkConfig(data);
    const user = generateUser(mockUserData);
    const props = {
      user,
      isLoading: false,
      getPaymentLinkConfig,
      isAutoRemindersUpdating: true,
      notifyCustomer: notifyCustomerFnMock,
      isPaymentLinksRemindersEnabled: true,
    };
    renderApp(props);
    expect(screen.getByText('UPI Payment Link')).toBeInTheDocument();
  });

  test('should render "Standard Payment Link" as payment link type', () => {
    const data = {
      upiLink: false,
      status: 'partially_paid',
      partial_payment: true,
    };
    const mockUserData = {
      isPaymentlinksV2Enabled: true,
      isPaymentLinkCreationV2Enabled: true,
    };
    const getPaymentLinkConfig = paymentLinkConfig(data);
    const user = generateUser(mockUserData);
    const props = {
      user,
      isLoading: false,
      getPaymentLinkConfig,
      isAutoRemindersUpdating: true,
      notifyCustomer: notifyCustomerFnMock,
      isPaymentLinksRemindersEnabled: true,
    };
    renderApp(props);
    expect(screen.getByText('Standard Payment Link')).toBeInTheDocument();
  });

  test('should render Duplicate Payment Link & should be clickable', async () => {
    const data = {
      upiLink: false,
      status: 'partially_paid',
      partial_payment: true,
    };
    const mockUserData = {
      isPaymentlinksV2Enabled: true,
      isPaymentLinkCreationV2Enabled: true,
    };
    const getPaymentLinkConfig = paymentLinkConfig(data);
    const user = generateUser(mockUserData);
    const props = {
      user,
      isLoading: false,
      getPaymentLinkConfig,
      isAutoRemindersUpdating: true,
      notifyCustomer: notifyCustomerFnMock,
      isPaymentLinksRemindersEnabled: true,
    };
    renderApp(props);
    const duplicatePaymentLinkCTA = screen.getByText('Duplicate Payment Link');
    expect(duplicatePaymentLinkCTA).toBeInTheDocument();
    await userEvent.click(duplicatePaymentLinkCTA);
    expect(analytics.analyticsTrack).toHaveBeenCalled();
  });

  test('should render "Cancel Link" & "Change Reference" & it should have onClick callback', async () => {
    const data = {
      upiLink: true,
      status: 'created',
      partial_payment: true,
    };
    const mockUserData = {
      isPaymentlinksV2Enabled: true,
      isPaymentLinkCreationV2Enabled: true,
    };
    const getPaymentLinkConfig = paymentLinkConfig(data);
    const user = generateUser(mockUserData);
    const props = {
      user,
      isLoading: false,
      getPaymentLinkConfig,
      isAutoRemindersUpdating: true,
      notifyCustomer: notifyCustomerFnMock,
      isPaymentLinksRemindersEnabled: true,
      trackEditReceipt: editReceiptMock,
      isRoleAllowedEdit: true,
      onCancel: onCancelMock,
    };
    renderApp(props);
    const cancelLinkCTA = screen.getByText('Cancel Link');
    expect(cancelLinkCTA).toBeInTheDocument();
    await userEvent.click(cancelLinkCTA);
    expect(onCancelMock).toHaveBeenCalled();
  });

  test('should render Edit Business Segment in document', () => {
    const data = {
      upiLink: true,
      status: 'partially_paid',
      partial_payment: true,
    };
    const mockUserData = {
      isPaymentlinksV2Enabled: true,
      isPaymentLinkCreationV2Enabled: true,
      isCustomNotesDropdownEnabled: true,
    };
    const getPaymentLinkConfig = paymentLinkConfig(data);
    const user = generateUser(mockUserData);
    const props = {
      user,
      isLoading: false,
      getPaymentLinkConfig,
      isAutoRemindersUpdating: true,
      notifyCustomer: notifyCustomerFnMock,
      isPaymentLinksRemindersEnabled: true,
    };
    renderApp(props);
    expect(screen.getByText('Edit Business Segment')).toBeInTheDocument();
  });

  test('should render customer name,  expiry by & have option to edit expiry" ', () => {
    const data = {
      upiLink: true,
      status: 'created',
      partial_payment: true,
      expire_by: '0',
      customer_name: 'rzp customer',
    };
    const mockUserData = {
      isPaymentlinksV2Enabled: true,
      isPaymentLinkCreationV2Enabled: true,
    };
    const getPaymentLinkConfig = paymentLinkConfig(data);
    const user = generateUser(mockUserData);
    const props = {
      user,
      isLoading: false,
      getPaymentLinkConfig,
      isAutoRemindersUpdating: true,
      notifyCustomer: notifyCustomerFnMock,
      isPaymentLinksRemindersEnabled: true,
      trackEditReceipt: editReceiptMock,
      isRoleAllowedEdit: true,
      onCancel: onCancelMock,
      showNoExpiry: true,
    };
    renderApp(props);
    expect(screen.getByText('rzp customer')).toBeInTheDocument();
    expect(screen.getByText('Expires On')).toBeInTheDocument();
    expect(screen.getByText('Edit Expiry')).toBeInTheDocument();
  });
});
