import React from 'react';
import { render, screen } from 'test-utils';
import { fireEvent } from '@testing-library/react';
import AbandonedWebhookSettings from 'merchant/views/MagicCheckout/MagicSettings/components/common/AbandonedWebhookSettings';
import {
  fetchFeatureFlagData,
  fetchWebhookUrlData,
  updateFeatureFlagSettings,
} from 'merchant/views/MagicCheckout/MagicSettings/components/common/AbandonedWebhookSettings/api';
import { ABANDONED_WEBHOOK_CARD_LABEL } from 'merchant/views/MagicCheckout/MagicSettings/components/common/AbandonedWebhookSettings/constants';

jest.mock(
  'merchant/views/MagicCheckout/MagicSettings/components/common/AbandonedWebhookSettings/api',
  () => ({
    fetchFeatureFlagData: jest.fn(),
    fetchWebhookUrlData: jest.fn(),
    updateFeatureFlagSettings: jest.fn(),
  }),
);

describe('AbandonedWebhookCard', () => {
  const merchantId = 'test-merchant';

  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('should render correctly', async () => {
    fetchFeatureFlagData.mockResolvedValue({ data: { one_cc_abandoned_webhook: false } });
    fetchWebhookUrlData.mockResolvedValue({ data: { webhook_url: '' } });

    render(<AbandonedWebhookSettings merchantId={merchantId} />);

    expect(await screen.findByText(ABANDONED_WEBHOOK_CARD_LABEL.HEADING)).toBeInTheDocument();
    expect(screen.getByRole('switch')).toBeInTheDocument();
    expect(
      screen.getByPlaceholderText(ABANDONED_WEBHOOK_CARD_LABEL.INPUT_PLACEHOLDER),
    ).toBeInTheDocument();
  });

  test('toggles feature flag switch', async () => {
    fetchFeatureFlagData.mockResolvedValue({ one_cc_abandoned_webhook: false });
    fetchWebhookUrlData.mockResolvedValue({ webhook_url: '' });
    updateFeatureFlagSettings.mockResolvedValue({ one_cc_abandoned_webhook: true });
    render(<AbandonedWebhookSettings merchantId={merchantId} />);

    const toggleSwitch = await screen.findByRole('switch');
    fireEvent.click(toggleSwitch);

    expect(updateFeatureFlagSettings).toHaveBeenCalledWith({
      merchant_id: merchantId,
      configs: { one_cc_abandoned_webhook: true },
      mode: 'test',
    });
  });

  test('updates webhook URL input field', async () => {
    fetchFeatureFlagData.mockResolvedValue({ data: { one_cc_abandoned_webhook: true } });
    fetchWebhookUrlData.mockResolvedValue({ data: { webhook_url: '' } });

    render(<AbandonedWebhookSettings merchantId={merchantId} />);

    const input = await screen.findByPlaceholderText(
      ABANDONED_WEBHOOK_CARD_LABEL.INPUT_PLACEHOLDER,
    );
    fireEvent.change(input, { target: { value: 'https://xyz.com' } });

    expect(input).toHaveValue('https://xyz.com');
  });

  test('disables Save button when input is empty', async () => {
    fetchFeatureFlagData.mockResolvedValue({ data: { one_cc_abandoned_webhook: true } });
    fetchWebhookUrlData.mockResolvedValue({ data: { webhook_url: '' } });

    render(<AbandonedWebhookSettings merchantId={merchantId} />);

    const saveButton = await screen.findByRole('button', {
      name: ABANDONED_WEBHOOK_CARD_LABEL.saveButton,
    });
    expect(saveButton).toBeDisabled();
  });

  test('enables Save button when input is valid ', async () => {
    fetchFeatureFlagData.mockResolvedValue({
      configs: { one_cc_abandoned_webhook: true },
    });
    fetchWebhookUrlData.mockResolvedValue({ webhook_url: '' });

    render(<AbandonedWebhookSettings merchantId={merchantId} />);

    const input = await screen.findByPlaceholderText(
      ABANDONED_WEBHOOK_CARD_LABEL.INPUT_PLACEHOLDER,
    );
    fireEvent.change(input, { target: { value: 'https://webhook.site/test' } });

    const saveButton = screen.getByRole('button', {
      name: ABANDONED_WEBHOOK_CARD_LABEL.SAVE,
    });
    expect(saveButton).toBeEnabled();
  });
});
