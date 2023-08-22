import React from 'react';
import { render, screen } from 'test-utils';

import store from 'merchant/store';

import { DynamicFields } from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields';
import { showDynamicFields, showPayerNamePL } from 'merchant/views/PaymentLinks/utils';

import {
  DEFAULT_USER,
  DYNAMIC_FIELDS,
} from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/DynamicFields';

jest.mock('merchant/views/PaymentLinks/utils', () => ({
  ...jest.requireActual('merchant/views/PaymentLinks/utils'),
  showPayerNamePL: jest.fn(),
  showDynamicFields: jest.fn(),
}));

const getStateSpy = jest.spyOn(store, 'getState');

const renderApp = (props = {}, initialState = {}) => {
  return render(<DynamicFields {...props} />, { initialState });
};

describe('Dynamic Fields', () => {
  test('should not render anything if feature flag "enable_payer_name_for_pl" is not set', () => {
    showPayerNamePL.mockReturnValue(false);

    renderApp();

    expect(screen.getByTestId('component-wrapper')).toBeEmptyDOMElement();
  });

  test('should render "Payer Name" field', () => {
    getStateSpy.mockReturnValue({ session: { user: { isDynamicPlOffset: false } } });
    showPayerNamePL.mockReturnValue(true);

    renderApp();

    expect(screen.getByText('Payer Name')).toBeInTheDocument();
  });

  test('should render placeholder text when no dynamic field is set', () => {
    getStateSpy.mockReturnValue({ session: { user: DEFAULT_USER } });
    showDynamicFields.mockReturnValue(true);
    showPayerNamePL.mockReturnValue(true);

    renderApp();

    expect(screen.getByText('Dynamic Field')).toBeInTheDocument();
  });

  test('should render fields when dynamic field is set', () => {
    getStateSpy.mockReturnValue({ session: { user: DEFAULT_USER } });
    showDynamicFields.mockReturnValue(true);
    showPayerNamePL.mockReturnValue(true);

    renderApp({ dynamicFields: DYNAMIC_FIELDS });

    expect(screen.getByText('Account Number')).toBeInTheDocument();
  });
});
