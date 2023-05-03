import store from 'merchant/store';
import React, { useState } from 'react';
import { render, screen, userEvent } from 'test-utils';
import SearchBar from 'merchant/components/HeaderNav/UniversalSearch/components/SearchBar';

const globalStore = store.getState();

const getInitialState = ({ isMobile = false }): store => ({
  ...globalStore,
  app: {
    ...globalStore.app,
    isMobileResolution: isMobile,
  },
});

const defaultProps = {
  isDeviceInBreakpoint: true,
  isFtuxVisible: true,
};

const App = (props) => {
  const [searchQuery, setSearch] = useState('');
  const [isShow, setFocussed] = useState(false);
  return (
    <SearchBar
      {...props}
      searchQuery={searchQuery}
      setSearch={setSearch}
      show={isShow}
      setFocussed={setFocussed}
    />
  );
};

const renderApp = ({ initialState, props }: { initialState: store; props?: Record<string, any> }) =>
  render(<App {...defaultProps} {...props} />, {
    initialState,
  });

const getInput = () => screen.getByRole('textbox', { name: '' });

const fillInput = async (query) => {
  const input = getInput();
  await userEvent.type(input, query);
};

describe('SearchBar', () => {
  test('should render search input bar', () => {
    const initialState = getInitialState({});
    renderApp({ initialState });
    const input = getInput();
    expect(input).toHaveAttribute('placeholder', 'Search payment products, settings, and more');
    expect(input).toHaveAttribute('name', 'search');
  });

  test('should update value when merchant enters query', async () => {
    const initialState = getInitialState({});
    const query = 'how to update bank';
    renderApp({ initialState });
    await fillInput(query);
    expect(screen.getByTestId('search-close')).toBeInTheDocument();
    expect(getInput()).toHaveAttribute('value', query);
  });

  test('should clear the field on close button click', async () => {
    const initialState = getInitialState({});
    const query = 'how to update bank';
    renderApp({ initialState, props: { isDeviceInBreakpoint: false } });
    await fillInput(query);
    await userEvent.click(screen.getByTestId('search-close'));
    expect(getInput()).toHaveAttribute('value', '');
  });
});
