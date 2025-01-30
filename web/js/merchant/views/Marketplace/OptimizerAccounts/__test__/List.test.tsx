import React from 'react';
import { render } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import OptimizerAccountsList from 'merchant/views/Marketplace/OptimizerAccounts/List';

import * as allFetch from 'merchant/utils/ajax';

jest.mock('merchant/views/Marketplace/OptimizerAccounts/components/OptimizerLinkAccount', () => ({
  OptimizerLinkAccount: jest.fn().mockReturnValue(<div>OptimizerLinkAccount Modal</div>),
}));

describe('Optimizer Accounts -> OptimizerAccountsList', () => {
  beforeEach(() => {
    jest
      .spyOn(allFetch, 'merchantFetch')
      .mockReturnValue(Promise.resolve({ success: true, isError: false, data: undefined }));
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  const initialState = {
    navigator: {
      terminalProviders: [],
    },
  };

  const MOCK_PROPS = {};

  const App = ({ initialState, props }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <OptimizerAccountsList {...props} />
      </Provider>
    );
  };

  const renderApp = (props = MOCK_PROPS) =>
    render(<App initialState={initialState} props={props} />);

  test('should render without errors', () => {
    expect(() => renderApp()).not.toThrowError();
  });

  test('should render document link and button', () => {
    const { getByRole } = renderApp();
    expect(getByRole('heading', { name: 'Optimizer accounts' })).toBeInTheDocument();
    const documentationLink = getByRole('link', { name: 'Documentation' });
    expect(documentationLink).toBeInTheDocument();
    expect(documentationLink).toHaveAttribute(
      'href',
      'https://razorpay.com/docs/payments/optimizer',
    );
    expect(documentationLink).toHaveAttribute('target', '_blank');
    expect(documentationLink).toHaveAttribute('rel', 'noopener noreferer');
    expect(getByRole('button', { name: '+ Link Account' })).toBeInTheDocument();
  });
});
