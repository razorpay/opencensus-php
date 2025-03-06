import React from 'react';
import { screen, act } from '@testing-library/react';
import { render, userEvent } from 'test-utils';
import CreateNewCampaign from '../CreateNewCampaign';

const fillAndSubmitTriggersAndActions = async () => {
  await act(async () => {
    await userEvent.click(screen.getByPlaceholderText('Select event'));
    await userEvent.click(screen.getByText('wallet_credit'));
    await userEvent.click(screen.getByRole('link', { name: 'Action' }));
    await userEvent.click(screen.getByPlaceholderText('Select action'));
    await userEvent.click(screen.getByText('Credit Wallet'));
    await userEvent.click(screen.getByPlaceholderText('Select wallet'));
    await userEvent.click(screen.getByText('Wallet1'));
    await userEvent.type(screen.getByLabelText('Credit Amount'), '100');
    await userEvent.click(screen.getByText('Next'));
  });
};

const fillAndSubmitBurnRules = async () => {
  await act(async () => {
    await userEvent.type(screen.getByPlaceholderText('Enter Duration'), '10');
    await userEvent.click(screen.getByPlaceholderText('Select Duration'));
    await userEvent.click(screen.getByText('Days'));
    await userEvent.type(screen.getByPlaceholderText('Enter Value'), '100');
    await userEvent.click(screen.getByText('Next'));
  });
};

const fillAndSubmitCampaignSettings = async () => {
  await act(async () => {
    await userEvent.click(screen.getByRole('checkbox', { name: 'Start Immediately' }));
    await userEvent.click(screen.getByRole('checkbox', { name: 'No End Date' }));
    await userEvent.click(screen.getByText('Next'));
  });
};

jest.setTimeout(50000);

describe('<CreateNewCampaign />', () => {
  it('should render create new campaign screen', () => {
    render(<CreateNewCampaign />, {
      path: '/wallet/campaigns/new',
      initialEntries: [
        '/wallet/campaigns/new?campaignName=Festive%20Cashback&campaignType=trigger',
      ],
    });

    expect(screen.getByText('Festive Cashback')).toBeInTheDocument();
  });

  it('should not move to Burn Rules step when Triggers & Actions step is not filled', async () => {
    render(<CreateNewCampaign />, {
      path: '/wallet/campaigns/new',
      initialEntries: [
        '/wallet/campaigns/new?campaignName=Festive%20Cashback&campaignType=trigger',
      ],
    });

    await act(async () => {
      await userEvent.click(screen.getByRole('button', { name: /next/i }));
    });

    expect(screen.getByText('Required')).toBeInTheDocument();
    //Burn rules input fields should not be visible
    expect(screen.queryByText('Points expire after')).not.toBeInTheDocument();
  });

  it('should move to Burn Rules step when Triggers & Actions step is filled', async () => {
    render(<CreateNewCampaign />, {
      path: '/wallet/campaigns/new',
      initialEntries: [
        '/wallet/campaigns/new?campaignName=Festive%20Cashback&campaignType=trigger',
      ],
    });

    await fillAndSubmitTriggersAndActions();

    expect(screen.queryByText('Points expire after')).toBeInTheDocument();
  });

  it('should not move to Campaign Settings step when Burn Rules step is not filled', async () => {
    render(<CreateNewCampaign />, {
      path: '/wallet/campaigns/new',
      initialEntries: [
        '/wallet/campaigns/new?campaignName=Festive%20Cashback&campaignType=trigger',
      ],
    });

    await fillAndSubmitTriggersAndActions();

    await act(async () => {
      await userEvent.click(screen.getByText('Next'));
    });

    expect(screen.queryByText('Schedule')).not.toBeInTheDocument();
  });

  it('should move to Campaign Settings step if all the details are filled', async () => {
    render(<CreateNewCampaign />, {
      path: '/wallet/campaigns/new',
      initialEntries: [
        '/wallet/campaigns/new?campaignName=Festive%20Cashback&campaignType=trigger',
      ],
    });

    await fillAndSubmitTriggersAndActions();
    await fillAndSubmitBurnRules();

    expect(screen.queryByText('Schedule')).toBeInTheDocument();
  });

  it('should not move to Review step if all the details are filled', async () => {
    render(<CreateNewCampaign />, {
      path: '/wallet/campaigns/new',
      initialEntries: [
        '/wallet/campaigns/new?campaignName=Festive%20Cashback&campaignType=trigger',
      ],
    });

    await fillAndSubmitTriggersAndActions();
    await fillAndSubmitBurnRules();

    await act(async () => {
      await userEvent.click(screen.getByText('Next'));
    });

    //Part of review step
    expect(screen.queryByText('Publish Campaign')).not.toBeInTheDocument();
  });

  it('should move to Review step if all the details are filled', async () => {
    render(<CreateNewCampaign />, {
      path: '/wallet/campaigns/new',
      initialEntries: [
        '/wallet/campaigns/new?campaignName=Festive%20Cashback&campaignType=trigger',
      ],
    });

    await fillAndSubmitTriggersAndActions();
    await fillAndSubmitBurnRules();
    await fillAndSubmitCampaignSettings();

    expect(screen.queryByText('Publish Campaign')).toBeInTheDocument();
  });

  it('should successfully create the campaign ', async () => {
    render(<CreateNewCampaign />, {
      path: '/wallet/campaigns/new',
      initialEntries: [
        '/wallet/campaigns/new?campaignName=Festive%20Cashback&campaignType=trigger',
      ],
    });

    await fillAndSubmitTriggersAndActions();
    await fillAndSubmitBurnRules();
    await fillAndSubmitCampaignSettings();

    await act(async () => {
      await userEvent.click(screen.getByText('Publish Campaign'));
    });

    expect(
      screen.getByText('Please review your campaign rules carefully before publishing it.'),
    ).toBeInTheDocument();

    await act(async () => {
      await userEvent.click(screen.getByTestId('campaign-submit-button'));
    });

    expect(await screen.findByText('Campaign Created!')).toBeInTheDocument();
  });

  it('should fail while creating the campaign ', async () => {
    render(<CreateNewCampaign />, {
      path: '/wallet/campaigns/new',
      initialEntries: [
        '/wallet/campaigns/new?campaignName=Failure%20Campaign&campaignType=trigger',
      ],
    });

    await fillAndSubmitTriggersAndActions();
    await fillAndSubmitBurnRules();
    await fillAndSubmitCampaignSettings();

    await act(async () => {
      await userEvent.click(screen.getByText('Publish Campaign'));
    });

    expect(
      screen.getByText('Please review your campaign rules carefully before publishing it.'),
    ).toBeInTheDocument();

    await act(async () => {
      await userEvent.click(screen.getByTestId('campaign-submit-button'));
    });

    expect(
      await screen.findByText('Error in creating campaign, please try again.'),
    ).toBeInTheDocument();
  });
});
