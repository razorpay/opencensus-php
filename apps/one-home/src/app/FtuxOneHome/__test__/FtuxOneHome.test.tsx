import React from 'react';
import { customRender, fireEvent, screen } from '@apps/one-home/src/services/test/test-utils';
import FtuxOneHome from '../FtuxOneHome'; // Update with correct path
import { getItem, setItem } from 'common/utils/localStorage';

// Mock localStorage functions
jest.mock('common/utils/localStorage', () => ({
  getItem: jest.fn(),
  setItem: jest.fn(),
}));

jest.mock('@libs/shared-utils', () => {
  const originalModule = jest.requireActual('@libs/shared-utils');
  return {
    ...originalModule,
    analyticsTrack: jest.fn(),
  };
});

describe('FtuxOneHome Component', () => {
  beforeEach(() => {
    jest.clearAllMocks(); // Reset mocks before each test
  });

  test('should render consent modal when isOneHomeConsentAccepted is false', () => {
    (getItem as jest.Mock).mockReturnValue('false');

    customRender(<FtuxOneHome />);

    expect(screen.getByText(/Introducing the new, all-in-one/i)).toBeInTheDocument();
    expect(screen.getByText(/Razorpay Home/i)).toBeInTheDocument();
    expect(
      screen.getByText(
        `The one place for you to keep track of everything on Razorpay. Don't worry, we have ensured that only the relevant folks have access to data.`,
      ),
    ).toBeInTheDocument();
  });

  test('should not render any modal when isOneHomeConsentAccepted is true', () => {
    (getItem as jest.Mock).mockReturnValue('true');

    customRender(<FtuxOneHome />);

    expect(screen.queryByText(/Introducing the new, all-in-one/i)).not.toBeInTheDocument();
  });

  test('should open opt-out modal when clicking Opt-out', () => {
    (getItem as jest.Mock).mockReturnValue('false');

    customRender(<FtuxOneHome />);
    fireEvent.click(screen.getByText(/I want to opt out/i));
    expect(
      screen.getByText(`Are you sure that you do not want to access the Razorpay Home?`),
    ).toBeInTheDocument();
    expect(screen.getByText(/Go Back/i)).toBeInTheDocument();
    fireEvent.click(screen.getByText(/Go back/i));
  });

  test('should go back to consent modal when clicking Go Back', () => {
    (getItem as jest.Mock).mockReturnValue('false');

    customRender(<FtuxOneHome />);
    fireEvent.click(screen.getByText(/I want to opt out/i));

    fireEvent.click(screen.getByText(/Go Back/i)); // Click Go Back

    expect(screen.getByText(/Introducing the new, all-in-one/i)).toBeInTheDocument();
    expect(screen.getByText(/Razorpay Home/i)).toBeInTheDocument();
    expect(
      screen.getByText(
        `The one place for you to keep track of everything on Razorpay. Don't worry, we have ensured that only the relevant folks have access to data.`,
      ),
    ).toBeInTheDocument();
  });

  test('should update consent and close modal when clicking Proceed', () => {
    (getItem as jest.Mock).mockReturnValue('false');

    customRender(<FtuxOneHome />);
    fireEvent.click(screen.getByText('Proceed'));

    expect(setItem).toHaveBeenCalledWith('isOneHomeConsentAccepted', 'true');
    expect(screen.queryByText(/Introducing the new, all-in-one/i)).not.toBeInTheDocument();
  });
});
