import React from 'react';
import 'react-dates/initialize';
import { render, checkIfComponentIsEmpty, screen, userEvent } from 'test-utils';
import {
  App,
  org,
  user,
  renderPager,
  EasterEggApp,
  EmptyComponentApp,
} from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/List';
import track from 'merchant/views/PaymentLinks/PaymentLinks/track';

jest.spyOn(track, 'onReminderSettingClick').mockImplementation(() => {});
jest.spyOn(track, 'onDocumentClick').mockImplementation(() => {});
jest.spyOn(track, 'searchCount').mockImplementation(() => {});

describe('PaymentLink - List Component', () => {
  /*
   * @param {*} props = {}
   * @return <New /> component file
   */
  beforeAll(() => {
    window.scrollTo = jest.fn();
    window.rzpQ = {
      component: jest.fn(),
      onbr: () => {},
      paymentLinks: () => ({
        interaction: jest.fn(),
        success: jest.fn(),
      }),
    };
  });

  afterEach(() => {
    track.onDocumentClick.mockClear();
    track.onReminderSettingClick.mockClear();
    track.searchCount.mockClear();
  });

  const renderApp = (props = {}) => {
    return render(<App {...props} tracking={{ track: jest.fn() }} />, {
      initialState: { session: { user, org } },
    });
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

  test('should have reminder settings', async () => {
    renderApp();
    const reminderBtn = screen.getByText(/reminder settings/i);
    expect(reminderBtn).toBeInTheDocument();
    await userEvent.click(reminderBtn);
    expect(track.onReminderSettingClick).toHaveBeenCalled();
  });

  test('should have Documentation clickable link', async () => {
    renderApp();
    const documentationBtn = screen.getAllByText(/Documentation/i)[0];
    expect(documentationBtn).toBeInTheDocument();
    await userEvent.click(documentationBtn);
    expect(track.onDocumentClick).toHaveBeenCalled();
  });

  test('should have restart tour flow', async () => {
    renderApp();
    const tourBtn = screen.getByText('Need help? Take a tour');
    expect(tourBtn).toBeInTheDocument();
    await userEvent.click(tourBtn);
    expect(screen.getByText(/Restart the Tour?/i)).toBeInTheDocument();
  });

  test('should have filters', async () => {
    renderApp();
    expect(await screen.findByText(/payment link status/i)).toBeInTheDocument();
    expect(await screen.findByText(/customer contact/i)).toBeInTheDocument();
    expect(await screen.findByText(/customer email/i)).toBeInTheDocument();
    expect(await screen.findByText(/notes/i)).toBeInTheDocument();
    expect(await screen.findByText(/payment link status/i)).toBeInTheDocument();
    expect(await screen.findByText(/\bcount\b/i)).toBeInTheDocument();
  });

  test('should have Search filters Button Available', () => {
    renderApp();
    const searchBtn = screen.getByText('Search');
    const clearBtn = screen.getByText('Clear');
    expect(searchBtn).toBeInTheDocument();
    expect(clearBtn).toBeInTheDocument();
  });
});
