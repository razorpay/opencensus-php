import React from 'react';
import { screen, act } from '@testing-library/react';
import { render, userEvent, waitFor } from 'test-utils';
import Campaigns from '../index';
import { CampaignListResponse } from './mocks/fixtures';

jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Object),
  useNavigate: jest.fn(),
}));

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({
    abExperiments: {
      wallet_campaigns: {
        variables: {
          result: 'on',
        },
      },
    },
  }),
}));

describe('<Campaigns />', () => {
  let mockedNavigate;

  beforeEach(() => {
    mockedNavigate = jest.fn();
    require('react-router-dom').useNavigate.mockReturnValue(mockedNavigate);
    mockedNavigate.mockClear();
  });

  it('should render campaigns tab', () => {
    render(<Campaigns />);

    expect(screen.getByText('Active Campaigns')).toBeInTheDocument();
    expect(screen.getByText('New Campaign')).toBeInTheDocument();
  });

  it('should open modal on clicking new campaign button', async () => {
    render(<Campaigns />);

    await userEvent.click(screen.getByRole('button', { name: /new campaign/i }));

    expect(screen.getByText('Create New Campaign')).toBeInTheDocument();
  });

  it('should not be able to navigate to create campaign screen without entering campaign name', async () => {
    render(<Campaigns />);

    await act(async () => {
      await userEvent.click(screen.getByRole('button', { name: /new campaign/i }));
      await userEvent.click(screen.getByRole('button', { name: /create campaign/i }));
    });

    expect(mockedNavigate).not.toHaveBeenCalled();
  });

  it('should navigate to create campaign screen on entering campaign name', async () => {
    render(<Campaigns />);

    await act(async () => {
      await userEvent.click(screen.getByRole('button', { name: /new campaign/i }));
      await userEvent.type(screen.getByLabelText('Name'), 'Festive Cashback');
      await userEvent.click(screen.getByRole('button', { name: /create campaign/i }));
    });

    expect(mockedNavigate).toHaveBeenCalledWith(
      '/wallet/campaigns/new?campaignName=Festive%20Cashback&campaignType=trigger',
    );
  });

  it('should render list of campaigns with pagination', async () => {
    render(<Campaigns />);

    await waitFor(() => {
      expect(screen.getByText(CampaignListResponse[0].data.campaigns[0].id)).toBeInTheDocument();
    });

    await userEvent.click(screen.getByLabelText('Next Page'));

    await waitFor(() => {
      expect(screen.getByText(CampaignListResponse[1].data.campaigns[0].id)).toBeInTheDocument();
    });
  });
});
