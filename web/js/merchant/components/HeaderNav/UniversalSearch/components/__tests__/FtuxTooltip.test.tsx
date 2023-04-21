import * as trackInfo from 'merchant/components/HeaderNav/UniversalSearch/utils';
import * as ftuxVisibilityAction from 'merchant/components/HeaderNav/UniversalSearch/utils/ftuxVisibility';
import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import FtuxTooltip from 'merchant/components/HeaderNav/UniversalSearch/components/FtuxTooltip';

const defaultProps = {
  setIsFtuxVisible: jest.fn(),
};

const renderApp = ({ props }: { props?: Record<string, any> }) =>
  render(<FtuxTooltip {...defaultProps} {...props} />);

const validateTooltip = () => {
  expect(screen.getByText('Introducing Search')).toBeInTheDocument();
  expect(
    screen.getByText('You can search for payment products, Account & Settings, and more'),
  ).toBeInTheDocument();
  expect(screen.getByText('GOT IT')).toBeInTheDocument();
};

describe('FtuxTooltip', () => {
  const trackSearchInfoSpy = jest.spyOn(trackInfo, 'trackSearchBarInfo');

  test('should render flux tooltip its visible state is true', () => {
    jest.spyOn(ftuxVisibilityAction, 'isVisible').mockImplementation(() => true);
    renderApp({});
    validateTooltip();
  });

  test('should not render flux tooltip its visible state is false', () => {
    jest.spyOn(ftuxVisibilityAction, 'isVisible').mockImplementation(() => false);
    renderApp({});
    expect(screen.queryByText('Introducing Search')).not.toBeInTheDocument();
  });

  test('should hide flux tooltip on clicking Got it', async () => {
    jest.spyOn(ftuxVisibilityAction, 'isVisible').mockImplementation(() => true);
    renderApp({});
    validateTooltip();
    const hideAction = screen.getByText('GOT IT');
    jest.spyOn(ftuxVisibilityAction, 'isVisible').mockImplementation(() => false);
    await userEvent.click(hideAction);
    expect(trackSearchInfoSpy).toHaveBeenCalledTimes(1);
    expect(screen.queryByText('Introducing Search')).not.toBeInTheDocument();
  });
});
