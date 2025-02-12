import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, fireEvent } from 'test-utils';
import SettlementsListFilter from 'merchant/views/Settlements/Settlements/components/ListFilter';
import { waitFor } from '@testing-library/react';
import { props } from 'merchant/views/Settlements/Settlements/components/__test__/mocks/fixtures/ListFilter';
import { useStore } from 'shell/commonStore';

jest.mock('shell/commonStore', () => ({
  ...jest.requireActual('shell/commonStore'),
  useStore: jest.fn(),
}));

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useLocation: () => ({ pathname: '/settlements' }),
}));

describe('ListFilter.js', () => {
  beforeAll(() => {
    useStore.mockReturnValue({ session: { user: {} } });
  });

  const App = (appProps) => {
    return <SettlementsListFilter {...appProps} />;
  };

  test('should render input fields', () => {
    render(<App {...props} />);
    expect(screen.queryByText('Settlement Id')).toBeInTheDocument();
    expect(screen.queryByText('Count')).toBeInTheDocument();
  });

  test(`should render form CTA's`, () => {
    render(<App {...props} />);
    expect(screen.queryByText('Search')).toBeInTheDocument();
    expect(screen.queryByText('Clear')).toBeInTheDocument();
  });

  test(`should render payment provider field`, () => {
    render(<App {...props} />);
    expect(screen.queryByText('Payment Provider')).toBeInTheDocument();
  });

  test(`should render PayU payment provider`, async () => {
    render(<App {...props} />);
    expect(screen.queryByText('Payment Provider')).toBeInTheDocument();
    const providerDropdown = screen.getAllByRole('textbox')[1];
    fireEvent.focus(providerDropdown);
    await waitFor(() => {
      expect(screen.queryByText('Payu')).toBeInTheDocument();
    });
  });
});
