import React from 'react';
import { render, userEvent } from 'test-utils';
import CreditTable from 'merchant/views/Account/Credits/components/ViewCreditHistoryTable';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as selfServeActions from 'common/utils/selfServeAnalytics';

describe('CreditTable', () => {
  const props = {
    title: 'Test',
    creditItems: [
      {
        id: 'Credit ID 1',
        campaign: 'Test Credit',
        value: 10000,
        created_at: 1728567003,
      },
    ],
    type: 'test',
  };

  const closeModalSpy = jest.spyOn(ModalActions, 'closeModal');
  const selfServeTrackSuccessSpy = jest.spyOn(selfServeActions, 'selfServeTrackSuccess');

  const renderApp = () => {
    return render(<CreditTable {...props} />);
  };

  it('renders without crashing', () => {
    renderApp();
  });

  it('calls closeModal when Close button is clicked', async () => {
    const { getByText } = renderApp();
    await userEvent.click(getByText('Close'));
    expect(closeModalSpy).toHaveBeenCalled();
  });

  it('displays the correct title', () => {
    const { getByText } = renderApp();
    expect(getByText('Test History')).toBeInTheDocument();
  });

  it('displays the correct columns headers', () => {
    const { getByText } = renderApp();
    expect(getByText('Credit Id')).toBeInTheDocument();
    expect(getByText('Description')).toBeInTheDocument();
    expect(getByText('Amount')).toBeInTheDocument();
  });

  it('tracks view event on mount', () => {
    const selfServeTrackSuccess = jest.fn();
    render(<CreditTable {...props} selfServeTrackSuccess={selfServeTrackSuccess} />);
    expect(selfServeTrackSuccessSpy).toHaveBeenCalled();
  });
});
