import React from 'react';
import { render, userEvent, screen, waitFor } from 'test-utils';
import UpdateWebsiteDetails from '../UpdateWebsiteDetails';
import { FLOWS, FORM_FIELDS } from '../Constants';

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({
    abExperiments: {},
  }),
}));

const renderApp = () =>
  render(<UpdateWebsiteDetails shouldShowV2 isOpen flowType={FLOWS.ADDITIONAL_WEBSITE} />);

describe('Update Website Details', () => {
  test('renders without crashing', () => {
    renderApp();
  });

  test('should select website by default', () => {
    renderApp();
    expect(screen.getByRole('radio', { name: /website/i })).toBeChecked();
  });

  test('should toggle between mode of integration', async () => {
    renderApp();
    await userEvent.click(screen.getByRole('radio', { name: /app/i }));
    await waitFor(() => expect(screen.getByRole('radio', { name: /app/i })).toBeChecked());
  });

  test('should display necessary fields if website is selected', () => {
    renderApp();
    expect(screen.getByRole('textbox', { name: /website url required \*/i })).toBeInTheDocument();
    expect(
      screen.getByRole('textbox', { name: /reason for adding new website required \*/i }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('textbox', { name: new RegExp(`${FORM_FIELDS.ABOUT_US.label} required *`) }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('textbox', {
        name: new RegExp(`${FORM_FIELDS.PRICING_DETAILS.label} required *`),
      }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('textbox', {
        name: new RegExp(`${FORM_FIELDS.CONTACT_US.label} required *`),
      }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('textbox', { name: new RegExp(`${FORM_FIELDS.TNC.label} required *`) }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('textbox', {
        name: new RegExp(`${FORM_FIELDS.PRIVACY_POLICY.label} required *`),
      }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('textbox', {
        name: new RegExp(`${FORM_FIELDS.REFUND_POLICY.label} required *`),
      }),
    ).toBeInTheDocument();
  });

  test('should display necessary fields if app is selected', async () => {
    renderApp();
    await userEvent.click(screen.getByRole('radio', { name: /app/i }));
    await waitFor(() =>
      expect(screen.getByRole('textbox', { name: /app url required \*/i })).toBeInTheDocument(),
    );
    expect(
      screen.getByRole('textbox', { name: /reason for adding new app required \*/i }),
    ).toBeInTheDocument();
  });

  test('should display test credentials fields if login credentials are needed', () => {
    renderApp();
    expect(
      screen.getByRole('textbox', {
        name: new RegExp(`${FORM_FIELDS.USERNAME.label} required *`, 'i'),
      }),
    ).toBeInTheDocument();
    expect(
      screen.getByPlaceholderText(new RegExp(`${FORM_FIELDS.PASSWORD.placeholder}`, 'i')),
    ).toBeInTheDocument();
  });

  test('should hide test credentials fields if login credentials are not needed', async () => {
    renderApp();
    await userEvent.click(screen.getByRole('radio', { name: /no/i }));
    expect(
      screen.queryByRole('textbox', {
        name: new RegExp(`${FORM_FIELDS.USERNAME.label} required *`, 'i'),
      }),
    ).not.toBeInTheDocument();
    expect(
      screen.queryByRole(new RegExp(`${FORM_FIELDS.PASSWORD.placeholder}`, 'i')),
    ).not.toBeInTheDocument();
  });

  test('should display error if website url is invalid', async () => {
    renderApp();
    const websiteInputBox = screen.getByRole('textbox', { name: /website url required \*/i });
    await userEvent.type(websiteInputBox, 'invalid-url');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));
    await waitFor(() => expect(screen.getByText(/Please enter valid url/i)).toBeInTheDocument());
  });

  test('should display error if reason is less that 50 words', async () => {
    renderApp();
    const websiteInputBox = screen.getByRole('textbox', {
      name: /reason for adding new website required \*/i,
    });
    await userEvent.type(websiteInputBox, 'only three words');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));
    await waitFor(() => expect(screen.getByText(/Minimum 50 words required/i)).toBeInTheDocument());
  });

  test('should clear error message if user re-types', async () => {
    renderApp();
    const websiteInputBox = screen.getByRole('textbox', {
      name: /reason for adding new website required \*/i,
    });
    await userEvent.type(websiteInputBox, 'only three words');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));
    await waitFor(() => expect(screen.getByText(/Minimum 50 words required/i)).toBeInTheDocument());
    await userEvent.type(websiteInputBox, 'more words to make it 50');
    await waitFor(() =>
      expect(screen.queryByText(/Minimum 50 words required/i)).not.toBeInTheDocument(),
    );
  });

  test('should show error while entering invalid URLs', async () => {
    renderApp();
    const websiteField = screen.getByRole('textbox', { name: /website url required \*/i });
    const submitCta = screen.getByRole('button', {
      name: /Submit website for review/i,
    });

    expect(websiteField).toBeInTheDocument();
    expect(submitCta).toBeInTheDocument();

    await userEvent.type(websiteField, 'test');
    await userEvent.click(submitCta);

    expect(screen.getByText('Please enter valid url')).toBeInTheDocument();
  });
});
