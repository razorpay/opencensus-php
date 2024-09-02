import React from 'react';

import App from 'merchant/views/Transactions/v2/common/components/GoBack';
import { render, screen, userEvent } from 'test-utils';

const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
}));

jest.setTimeout(35000);

describe('GoBack', () => {
  const onClickCb = jest.fn();
  const renderApp = ({ initialEntries, onClickCb } = {}) =>
    render(<App onClickCb={onClickCb} />, {
      initialEntries,
    });

  test('should have "Go Back" as Text', () => {
    renderApp();
    expect(screen.getByText('Go Back')).toBeInTheDocument();
  });

  test('should call its callback when clicked', async () => {
    renderApp({ onClickCb });
    const GoBackCTA = screen.getByText('Go Back');
    await userEvent.click(GoBackCTA);
    expect(onClickCb).toHaveBeenCalled();
  });

  test('should go to previous route by default', async () => {
    renderApp();
    const GoBackCTA = screen.getByText('Go Back');
    await userEvent.click(GoBackCTA);
    expect(mockNavigate).toHaveBeenCalledWith(-1);
  });
});
