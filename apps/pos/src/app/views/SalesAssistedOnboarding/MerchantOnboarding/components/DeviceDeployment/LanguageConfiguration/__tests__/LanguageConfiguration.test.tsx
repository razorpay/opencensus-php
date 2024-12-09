import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import LanguageConfigurationContainer from '../LanguageConfigurationContainer';
import { getModularConfig, updateModularConfig } from '../mocks/handlers';
import { render, screen, server, waitFor } from 'apps/pos/src/services/test/test-utils';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    id: 'abc_123',
    step: 'deviceDeployment',
    component: 'languageConfiguration',
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

const queryClient = new QueryClient();
const renderApp = () => {
  render(
    <QueryClientProvider client={queryClient}>
      <LanguageConfigurationContainer />
    </QueryClientProvider>,
  );
};

describe('Test POS Language Configuration screen', () => {
  jest.setTimeout(30000);
  afterEach(() => {
    jest.clearAllMocks();
    queryClient.clear();
  });

  test('should not render Language Configuration page if modular config data is not available', async () => {
    server.use(getModularConfig({ type: 'failure' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/Something went wrong. Please try again./i)).toBeInTheDocument();
    });
  });

  test('should render Language Configuration page heading correctly when modular config is available', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/Preferred Language/i)).toBeInTheDocument();
    });
  });

  test('should render Language Radio options and CTA', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/English/i)).toBeInTheDocument();
      expect(screen.getByText(/Hindi/i)).toBeInTheDocument();
      expect(screen.getByText(/Confirm Language/i)).toBeInTheDocument();
    });
  });

  test('Selecting a langauge and submitting will render spinner', async () => {
    server.use(getModularConfig({ type: 'success' }), updateModularConfig({ type: 'success' }));
    renderApp();

    await waitFor(() => {
      const EnglishOption = screen.getByText(/English/i);
      EnglishOption.click();
    });
    const confirmLanguageButton = screen.getByRole('button', { name: /Confirm Language/i });
    await waitFor(() => {
      expect(confirmLanguageButton).not.toBeDisabled();
      confirmLanguageButton.click();
    });

    await waitFor(() => {
      expect(screen.getByRole('progressbar')).toBeInTheDocument();
    });
  });
});
