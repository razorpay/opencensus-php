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
        creditID: 'Credit ID 1',
        creditsDescription: 'Test Credit',
        credits: 10,
        createdAt: '2021-01-01',
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
