import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';

import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

import { CardButton } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/common/components/CardButton';

describe('<CardButton />', () => {
  const onClickFn = jest.fn();

  afterEach(() => {
    jest.clearAllMocks();
  });

  const App = (props = {}) => {
    const defaultProps = {
      title: 'Title',
      description: 'Description',
      icon: <i />,
      onClick: onClickFn,
    };

    return (
      <BladeProvider themeTokens={bladeTheme}>
        <CardButton {...defaultProps} {...props} />
      </BladeProvider>
    );
  };

  test('should render', () => {
    render(<App />);

    expect(screen.getByText(/title/i)).toBeInTheDocument();
  });

  test('should call `onClick` function when clicked', () => {
    render(<App />);

    fireEvent.click(screen.getByRole('button'));
    expect(onClickFn).toHaveBeenCalled();
  });
});
