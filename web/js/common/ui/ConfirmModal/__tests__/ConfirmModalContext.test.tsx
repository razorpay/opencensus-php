import React from 'react';
import PropTypes from 'prop-types';

import { delay, render, screen, userEvent } from 'common/services/test/test-utils';

const defaultProps = {
  header: 'header',
  message: 'message',
  abortLabel: 'abortLabel',
  affirmativeLabel: 'affirmativeLabel',
  affirmativePendingLabel: 'affirmativePendingLabel',
  action: () => delay(200),
  abort: () => delay(200),
};
describe('ConfirmModalContext', () => {
  afterEach(() => {
    jest.clearAllMocks();
  });
  const renderApp = (props = {}) => {
    class App extends React.Component {
      static contextTypes = {
        // eslint-disable-next-line no-undef
        confirm: PropTypes.func,
      };

      // Note: ConfirmModalProvider is already there as a render wrapper in test-utils.
      componentDidMount() {
        this.context.confirm({
          ...defaultProps,
          ...props,
        });
      }

      render() {
        return null;
      }
    }
    return render(<App />);
  };

  test('keeps affirm button disabled during modal close animation', async () => {
    const AFFIRM_ACTION_DELAY = 100;
    renderApp({
      action: () => delay(AFFIRM_ACTION_DELAY),
    });
    expect(screen.getByText('affirmativeLabel')).toBeInTheDocument();
    await userEvent.click(screen.getByText('affirmativeLabel'));
    expect(screen.getByText('affirmativePendingLabel')).toBeInTheDocument();
    // Pending label should sustain for modal animation duration.
    await delay(AFFIRM_ACTION_DELAY + 50);
    expect(screen.getByText('affirmativePendingLabel')).toBeInTheDocument();
    await delay(300);
    expect(screen.queryByText('affirmativePendingLabel')).not.toBeInTheDocument();
  });
});
