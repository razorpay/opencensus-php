import React from 'react';
import { render, userEvent, screen } from 'test-utils';
import { MultiAccountBody, MultiAccountFooter } from '../components/MultiAccountBodyFooter';
import * as analytics from '../analytics';

// Mock getUser and getFirstUppercaseChar
jest.mock('merchant/store', () => {
  const actual = jest.requireActual('merchant/store');
  return {
    ...actual,
    getUser: () => ({
      user: {
        name: 'Anshul',
        email: 'anshul@example.com',
        contact_mobile: '9876543210',
      },
    }),
    storeWithInitialState: jest.fn((initialState) =>
      actual.storeWithInitialState(initialState)
    ),
  };
});


jest.mock('merchant/views/CompanyRegistration/utils', () => ({
  getFirstUppercaseChar: (str: string) => str?.[0]?.toUpperCase() ?? '',
}));

jest.mock('../analytics', () => ({
  trackEventOnCreateAccountFormField: jest.fn(),
}));

describe('MultiAccountBody', () => {
  const mockSetSelected = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });
  const renderComponent = ({ selected }) =>
    render(
        <MultiAccountBody selected={selected} setSelected={mockSetSelected} />
    );
  test('renders primary card with user info', () => {
    renderComponent({ selected: 'primary' });
    expect(screen.getByText('9876543210')).toBeInTheDocument();
    expect(screen.getByText('anshul@example.com')).toBeInTheDocument();
    expect(screen.getByText('Use new login credentials')).toBeInTheDocument();
  });

  test('clicking primary card sets selected and tracks event', async () => {
    renderComponent({ selected: '' });
    await userEvent.click(screen.getByText('9876543210'));

    expect(mockSetSelected).toHaveBeenCalledWith('primary');
    expect(analytics.trackEventOnCreateAccountFormField).toHaveBeenCalledWith(false);
  });

  test('clicking secondary card sets selected and tracks event',async () => {
    renderComponent({ selected: '' });
    await userEvent.click(screen.getByText('Use new login credentials'));

    expect(mockSetSelected).toHaveBeenCalledWith('secondary');
    expect(analytics.trackEventOnCreateAccountFormField).toHaveBeenCalledWith(true);
  });
});

describe('MultiAccountFooter', () => {
  const renderComponent = ({ isLoading, mockHandle }) =>
    render(
        <MultiAccountFooter isLoading={isLoading} handleUserAction={mockHandle} />
    );
  test('renders button and triggers handleUserAction', async () => {
    const mockHandle = jest.fn();
    renderComponent({ isLoading: false, mockHandle });

    const button = screen.getByRole('button', { name: /proceed/i });
    expect(button).toBeInTheDocument();
    expect(button).not.toBeDisabled();

    await userEvent.click(button);
    expect(mockHandle).toHaveBeenCalled();
  });

  test('button shows loading state when isLoading is true', () => {
    const mockHandle = jest.fn();
    renderComponent({ isLoading: true, mockHandle });

    const button = screen.getByRole('button');
    expect(button).toBeDisabled(); // isLoading disables the button
  });
});