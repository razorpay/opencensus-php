import '@testing-library/jest-dom/extend-expect';
import Divider from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/Divider';
import React from 'react';
import { render } from 'test-utils';

describe('Divider', () => {
  const renderApp = (props) => render(<Divider {...props} />);

  test('should render divider with margin', () => {
    const { getByTestId } = renderApp({ noMargin: false });
    expect(getByTestId('divider')).toBeInTheDocument();
    expect(getByTestId('divider')).toHaveStyle({ margin: '12px 0px 14px 0px' });
  });

  test('should render divider without margin', () => {
    const { getByTestId } = renderApp({ noMargin: true });
    expect(getByTestId('divider')).toBeInTheDocument();
  });
});
