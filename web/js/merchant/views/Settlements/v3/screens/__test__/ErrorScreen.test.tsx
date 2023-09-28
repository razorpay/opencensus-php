import { ERROR_TYPE } from 'merchant/views/Settlements/v3/typings';
import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import ErrorScreen from 'merchant/views/Settlements/v3/screens/ErrorScreen';

const defaultProps = {
  handleRefresh: jest.fn(),
};

const renderApp = (props) => render(<ErrorScreen {...defaultProps} {...props} />);

describe('Error Screen', () => {
  test('should render error screen and go back link when merchant searched invalid settlement id', async () => {
    const { history } = renderApp({ type: ERROR_TYPE.INVALID_ID });
    const historyPushSpy = jest.spyOn(history, 'push');

    expect(screen.getByText('Enter valid settlement ID')).toBeInTheDocument();
    const goBackLink = screen.getByRole('button', { name: 'Go back' });
    await userEvent.click(goBackLink);
    expect(historyPushSpy).toHaveBeenCalledTimes(1);
    expect(historyPushSpy).toHaveBeenCalledWith(
      { hash: '', pathname: '/settlements', search: '' },
      undefined,
      {},
    );
  });

  test('should render error screen and refresh page button when api fails due to server error', async () => {
    renderApp({ type: ERROR_TYPE.SERVER_ERROR });
    expect(screen.getByText('Settlement details could not be loaded')).toBeInTheDocument();
    expect(screen.getByText('this page or try again later')).toBeInTheDocument();

    const refreshBtn = screen.getByRole('button', { name: 'Refresh' });
    await userEvent.click(refreshBtn);
    expect(defaultProps.handleRefresh).toHaveBeenCalledTimes(1);
    expect(defaultProps.handleRefresh).toHaveBeenCalledWith();
  });
});
