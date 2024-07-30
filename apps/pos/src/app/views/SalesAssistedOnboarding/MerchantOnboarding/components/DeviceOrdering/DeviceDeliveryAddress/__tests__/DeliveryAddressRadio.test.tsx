import React from 'react';
import DeliveryAddressRadio from '../DeliveryAddressRadio';
import { DeviceDeliveryAddressTypes } from 'apps/pos/src/app/types/DeviceSelection';
import { render, screen } from 'apps/pos/src/services/test/test-utils';

const defaultProps = {
  value: 'registered' as DeviceDeliveryAddressTypes,
  address: {
    line1: 'line1',
    line2: 'line2',
    city: 'city',
    zipcode: 'zipcode',
    state: 'state',
    country: 'country',
    landmark: 'landmark',
    name: 'TestName',
    contact: '1234567890',
  },
};

const renderApp = (props = {}) => {
  const initProps = {
    ...defaultProps,
    ...props,
  };
  render(<DeliveryAddressRadio {...initProps} />);
};

describe('DeliveryAddressRadio', () => {
  test('should render the component on screen', () => {
    renderApp();
    expect(screen.getByText('TestName')).toBeInTheDocument();
    expect(screen.getByText('1234567890')).toBeInTheDocument();
    expect(screen.getByText('line1')).toBeInTheDocument();
    expect(screen.getByText('city, state - zipcode')).toBeInTheDocument();
    expect(screen.getByText('Registered Address')).toBeInTheDocument();
  });
});
