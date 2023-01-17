import React from 'react';
import store from 'merchant/store';
import '@testing-library/jest-dom/extend-expect';
import {
  updateUser,
  defaultProps,
  rzpUserConfig,
  AffordabilityStoreConfiguration,
} from './mocks/fixtures';
import Affordability from 'merchant/views/Affordability';
import { render, screen } from 'test-utils';

const getStateSpy = jest.spyOn(store, 'getState');

describe('Affordability', () => {
  /*
   * Mocking 'user/merchant' level configuration from redux store.
   */
  updateUser(getStateSpy, AffordabilityStoreConfiguration);

  /**
   * Setting 'user/merchant' window level configuration
   */
  beforeAll(() => {
    window.rzp_user = rzpUserConfig('activated', 'owner');
    jest.useFakeTimers();
  });

  afterEach(() => {
    jest.useRealTimers();
  });

  /*
   * @param {*} props = {}
   * @return <PaymentLink /> index file
   */
  const renderApp = ({ props } = {}) => {
    return render(<Affordability {...defaultProps} {...props} />, { showModal: true });
  };

  test('should render component without errors', () => {
    expect(renderApp).not.toThrowError();
  });

  test('should render Affordability title', () => {
    renderApp();
    expect(screen.getByText('Widget')).toBeInTheDocument();
  });
});
