import React from 'react';

import { CAMPAIGN_HERO_MOCK_RESPONSE } from 'merchant/containers/Home/RTUX/__tests__/mocks';
import { CampaignHero } from 'merchant/widgets/CampaignHero';
import { render, screen, userEvent, waitFor } from 'test-utils';

const mockRetry = jest.fn();
jest.mock('merchant/widgets/hooks', () => ({ useRetryWidget: () => [false, mockRetry] }));

const campaign1 = CAMPAIGN_HERO_MOCK_RESPONSE.data.campaign_hero_card_data.assetData[0];
const initProps = { queryKey: [], isLoading: false, ...CAMPAIGN_HERO_MOCK_RESPONSE };

describe('Widgets->CampaignHero', () => {
  const renderApp = (defaultProps = {}) => {
    return render(<CampaignHero {...initProps} {...defaultProps} />);
  };

  test('should display cards for loading state', async () => {
    renderApp({ isLoading: true });
    await waitFor(() => {
      expect(screen.getAllByTestId('campaign-hero-loader')).toHaveLength(3);
    });
  });

  test('should render cards after loading', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.queryByTestId('campaign-hero-loader')).not.toBeInTheDocument();
      expect(screen.queryAllByTestId('campaign-hero-card')).toHaveLength(2);
    });
  });

  test('should render card with all properties', async () => {
    const data = campaign1.templates[0].data.rtux_ucs_campaigns;
    renderApp();

    await waitFor(() => {
      expect(screen.getAllByTestId('campaign-hero-image')[0]).toHaveAttribute('src', data.image.sm);
      expect(screen.getAllByTestId('campaign-hero-image')[0]).toHaveAttribute('alt', data.alt_text);
    });
  });

  test('should retry widget on error', async () => {
    renderApp({ error: { message: 'Error' } });
    const retryButton = await screen.findByRole('button', { name: /try again/i });
    await userEvent.click(retryButton);
    await waitFor(() => {
      expect(mockRetry).toHaveBeenCalled();
    });
  });
});
