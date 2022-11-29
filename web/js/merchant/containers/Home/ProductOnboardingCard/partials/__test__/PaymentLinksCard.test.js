import React from 'react';
import { render } from 'test-utils';
import PaymentLinksCard from 'merchant/containers/Home/ProductOnboardingCard/partials/PaymentLinksCard';
import { createMemoryHistory } from 'history';

let history;

describe('ProductOnboardingCard - PaymentLinksCard', () => {
  beforeAll(() => {
    history = createMemoryHistory();
    history.push = jest.fn();
  });

  beforeEach(() => {
    jest.clearAllMocks();
  });
  test('should render component without errors', () => {
    expect(() => render(<PaymentLinksCard history={history} product="PH" />)).not.toThrowError();
  });
});
