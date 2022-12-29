import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, checkIfComponentIsEmpty, screen } from 'test-utils';
import {
  App,
  org,
  user,
  renderPager,
  EasterEggApp,
  EmptyComponentApp,
} from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/List';

describe('PaymentLink - List Component', () => {
  /*
   * @param {*} props = {}
   * @return <New /> component file
   */
  beforeAll(() => {
    window.rzpQ = {
      onbr: () => {},
    };
  });

  const renderApp = (props = {}) => {
    return render(<App {...props} />, { initialState: { session: { user, org } } });
  };

  test('should render EasterEggApp component without errors', () => {
    render(<EasterEggApp />);
    checkIfComponentIsEmpty();
    expect(EasterEggApp).not.toThrowError();
  });

  test('pager component to be defined', () => {
    expect(renderPager).toBeDefined();
  });

  test('should render EmptyComponentApp component without errors', () => {
    expect(EmptyComponentApp).toBeDefined();
    render(<EmptyComponentApp />);
    expect(screen.getByText('There are no payment links yet!!')).toBeInTheDocument();
    expect(screen.getByText('Start creating new links now.')).toBeInTheDocument();
  });

  test('List component to be defined', () => {
    expect(App).toBeDefined();
  });

  test('should have Create Payment Link CTA', () => {
    renderApp();
    expect(screen.getByText('Create Payment Link')).toBeInTheDocument();
  });

  test('should have Search filters Button Available', () => {
    renderApp();
    expect(screen.getByText('Search')).toBeInTheDocument();
    expect(screen.getByText('Clear')).toBeInTheDocument();
    expect(screen.getByText('Show All Filters')).toBeInTheDocument();
  });

  test('should have reminder settings', () => {
    renderApp();
    expect(screen.getByText(/reminder settings/i)).toBeInTheDocument();
  });

  test('should have filters', async () => {
    renderApp();
    expect(await screen.findByText(/payment link status/i)).toBeInTheDocument();
    expect(await screen.findByText(/customer contact/i)).toBeInTheDocument();
    expect(await screen.findByText(/customer email/i)).toBeInTheDocument();
    expect(await screen.findByText(/notes/i)).toBeInTheDocument();
    expect(await screen.findByText(/payment link status/i)).toBeInTheDocument();
    expect(await screen.findByText(/count/i)).toBeInTheDocument();
  });
});
