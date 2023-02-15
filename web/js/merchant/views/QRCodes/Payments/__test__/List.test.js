import React from 'react';
import { render } from 'test-utils';
import DashboardBanner from 'common/ui/DashboardBanner';
import { createMemoryHistory } from 'history';

let history;

describe('QR Code List View', () => {
  const renderApp = () => render(<DashboardBanner />);

  beforeAll(() => {
    history = createMemoryHistory();
    history.push = jest.fn();
  });

  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('should render component without errors', () => {
    expect(() => renderApp()).not.toThrowError();
  });
});
