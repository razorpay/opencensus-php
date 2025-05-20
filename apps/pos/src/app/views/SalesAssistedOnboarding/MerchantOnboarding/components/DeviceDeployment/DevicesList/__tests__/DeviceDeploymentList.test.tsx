import React from 'react';
import DeviceDeploymentList from '../DeviceDeploymentListContainer';
import { getModularConfig, updateModularConfig } from '../mocks/handlers';
import { render, screen, server, waitFor } from 'apps/pos/src/services/test/test-utils';
import { isKycQualifiedEkyc } from 'apps/pos/src/app/utils/deviceDeployment';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    id: 'P5IFHHN6RTRXDe',
    step: 'deviceDeployment',
    component: 'deviceDeploymentList',
  }),
}));

jest.mock('apps/pos/src/bootstrap/Store/index', () => ({
  __esModule: true,
  default: jest.fn(() => ({
    workflowProduct: 'PARTNER_ASSISTED_ONBOARDING',
    isPosEkycAgent: true,
    setWorkflowProduct: jest.fn(),
    setIsPosEkycAgent: jest.fn(),
  })),
}));

const renderApp = () => {
  render(<DeviceDeploymentList />);
};

describe('Test POS Language Configuration screen', () => {
  jest.setTimeout(30000);
  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should not render Device Deployment list page if modular config data is not available', async () => {
    server.use(getModularConfig({ type: 'failure' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/Something went wrong. Please try again./i)).toBeInTheDocument();
    });
  });

  test('should render Device Deployment list page if modular config data is available', async () => {
    server.use(getModularConfig({ type: 'success' }), updateModularConfig({ type: 'success' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/Choose Devices to Deploy/i)).toBeInTheDocument();
      expect(screen.getByText(/All Devices/i)).toBeInTheDocument();
      expect(screen.getByText(/Deployed Devices/i)).toBeInTheDocument();
    });
  });
});

describe('isKycQualifiedEkyc function', () => {
  test('should return true for "ACTIVATED"', () => {
    const result = isKycQualifiedEkyc('ACTIVATED');
    expect(result).toBe(true);
  });

  test('should return true for "KYC_QUALIFIED_STB"', () => {
    const result = isKycQualifiedEkyc('KYC_QUALIFIED_STB');
    expect(result).toBe(true);
  });

  test('should return false for "INACTIVE"', () => {
    const result = isKycQualifiedEkyc('INACTIVE');
    expect(result).toBe(false);
  });

  test('should return false for "null"', () => {
    const result = isKycQualifiedEkyc(null);
    expect(result).toBe(false);
  });

  test('should return false for empty string', () => {
    const result = isKycQualifiedEkyc('');
    expect(result).toBe(false);
  });

  test('should return false for a value not in the array', () => {
    const result = isKycQualifiedEkyc('SOME_OTHER_STATUS');
    expect(result).toBe(false);
  });
});
