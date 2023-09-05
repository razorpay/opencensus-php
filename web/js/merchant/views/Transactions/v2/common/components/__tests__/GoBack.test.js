import React from 'react';

import App from 'merchant/views/Transactions/v2/common/components/GoBack';
import { render, screen, userEvent } from 'test-utils';

jest.setTimeout(35000);

describe('GoBack', () => {
  const onClickCb = jest.fn();
  const renderApp = ({ historyOptions } = {}) =>
    render(<App onClickCb={onClickCb} />, {
      historyOptions,
    });

  test('should have "Go Back" as Text', () => {
    renderApp();
    expect(screen.getByText('Go Back')).toBeInTheDocument();
  });

  test('should call its callback when clicked', async () => {
    renderApp();
    const GoBackCTA = screen.getByText('Go Back');
    await userEvent.click(GoBackCTA);
    expect(onClickCb).toHaveBeenCalled();
  });

  test('should go to payments route when it is clicked without prevPath', async () => {
    const { history } = renderApp();
    history.push = jest.fn();
    const GoBackCTA = screen.getByText('Go Back');
    await userEvent.click(GoBackCTA);
    expect(history.push).toHaveBeenCalledWith('/payments');
  });

  test('should go to back to previous route when it is clicked with prevPath', async () => {
    const historyOptions = {
      initialEntries: [{ pathname: '/current', state: { prevPath: '/previous' } }],
    };
    const { history } = renderApp({ historyOptions });
    history.goBack = jest.fn();
    const GoBackCTA = screen.getByText('Go Back');
    await userEvent.click(GoBackCTA);
    expect(history.goBack).toHaveBeenCalled();
  });
});
