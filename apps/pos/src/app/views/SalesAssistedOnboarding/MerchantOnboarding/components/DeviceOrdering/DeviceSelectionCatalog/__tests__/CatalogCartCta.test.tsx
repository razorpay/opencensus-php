import React from 'react';
import CatalogCartCta from '../CatalogCartCta';
import { render, screen, userEvent } from 'apps/pos/src/services/test/test-utils';
import { TestAddedDevice } from 'apps/pos/src/services/mocks/fixtures/deviceSelection';

const defaultProps = {
  addedDevices: [TestAddedDevice],
  isUpdateModularLoading: false,
  isStepCompleted: false,
  handleModularUpdate: jest.fn(),
  handleProceed: jest.fn(),
};

const renderApp = (props = {}) => {
  const initProps = {
    ...defaultProps,
    ...props,
  };
  render(<CatalogCartCta {...initProps} />);
};

describe('CatalogCartCta', () => {
  test('should render cta component on screen', () => {
    renderApp();
    expect(screen.getByText('Proceed to cart')).toBeInTheDocument();
  });

  test('should trigger handleModularUpdate with correct params', async () => {
    renderApp();
    await userEvent.click(screen.getByText('Proceed to cart'));
    expect(defaultProps.handleModularUpdate).toHaveBeenCalledWith({
      device_selection_completion_field: true,
      modular_callback: expect.any(Function),
    });
  });

  test('should not trigger handleModularUpdate if step is completed', async () => {
    renderApp({ isStepCompleted: true });
    await userEvent.click(screen.getByText('Proceed to cart'));
    expect(defaultProps.handleModularUpdate).not.toHaveBeenCalled();
    expect(defaultProps.handleProceed).toHaveBeenCalled();
  });
});
