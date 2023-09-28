import React from 'react';

import App from 'merchant/views/Transactions/v2/common/components/GoBack';
import { render, screen, userEvent } from 'test-utils';

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

  test('should go to payments route when it is clicked without prevPath', async () => {
    const { history } = renderApp();
    history.push = jest.fn();
    const GoBackCTA = screen.getByText('Go Back');
    await userEvent.click(GoBackCTA);
    expect(history.push).toHaveBeenCalledWith(
      { hash: '', pathname: '/payments', search: '' },
      undefined,
      {},
    );
  });

  test('should go to back to previous route when it is clicked with prevPath', async () => {
    const prevPath = '/previous';
    const { history } = renderApp({
      initialEntries: [{ pathname: '/', state: { prevPath } }],
    });
    history.push = jest.fn();
    const GoBackCTA = screen.getByText('Go Back');
    await userEvent.click(GoBackCTA);
    expect(history.push).toHaveBeenCalledWith(
      { hash: '', pathname: '/previous', search: '' },
      undefined,
      {},
    );
  });
});
