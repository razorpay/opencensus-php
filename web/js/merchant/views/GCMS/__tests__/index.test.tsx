import React from 'react';
import { render, screen, waitFor, userEvent } from 'test-utils';
import GCMSContainer from '../index';
import { useLocation, MemoryRouter } from "react-router-dom";
import { GCMS_PATHS } from '../shared/constants';


// Mock all the tab components
jest.mock('../Programs', () => ({
  __esModule: true,
  default: () => <div>Programs Component</div>,
}));

jest.mock('../Programs/ProgramPage', () => ({
  __esModule: true,
  default: () => <div>Program Details Component</div>,
}));

jest.mock('../Resellers', () => ({
  __esModule: true,
  default: () => <div>Resellers Component</div>,
}));

jest.mock('../Orders', () => ({
  __esModule: true,
  default: () => <div>Orders Component</div>,
}));

jest.mock('../Funds', () => ({
  __esModule: true,
  default: () => <div>Funds Component</div>,
}));

jest.mock('../Reports', () => ({
  __esModule: true,
  default: () => <div>Reports Component</div>,
}));

jest.mock('../BatchActions', () => ({
  __esModule: true,
  default: () => <div>Batch Actions Component</div>,
}));

jest.mock('merchant/components/ShowWhen', () => ({
  RouteGuard: ({ children }) => children,
}));

jest.mock("react-router-dom", () => ({
  ...jest.requireActual("react-router-dom"),
  useLocation: jest.fn(() => (
    {
      pathname: "/",
      key: "valid-route",
    }
  )),
}));

describe('<GCMS />', () => {
  const mockState = {
    session: {
      mode: 'test',
      user: {
        current: 'test_merchant_id',
      },
    },
  };

  const renderWithRouter = (initialPath = '/gcms') => {
    return render(<GCMSContainer />, {
      initialState: mockState,
    });
  };

  test('renders GCMS container with tabs', () => {
    renderWithRouter();

    // Check if all tabs are rendered
    expect(screen.getByText('Programs')).toBeInTheDocument();
    expect(screen.getByText('Resellers')).toBeInTheDocument();
    expect(screen.getByText('Orders')).toBeInTheDocument();
    expect(screen.getByText('Funds')).toBeInTheDocument();
    expect(screen.getByText('Reports')).toBeInTheDocument();
    expect(screen.getByText('Batch Actions')).toBeInTheDocument();
  });

});
