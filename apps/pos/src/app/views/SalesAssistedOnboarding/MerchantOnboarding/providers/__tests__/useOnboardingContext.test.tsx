import React from 'react';
import useOnboardingContext from '../useOnboardingContext';
import { MOCK_ONBOARDING_CONFIG } from './mocks/fixtures';
import { render, userEvent, waitFor } from 'apps/pos/src/services/test/test-utils';

jest.mock(
  'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/MerchantOnboardingConfig',
  () => ({
    ...jest.requireActual(
      'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/MerchantOnboardingConfig',
    ),
    ONBOARDING_STEPS: jest.requireActual('./mocks/fixtures').MOCK_ONBOARDING_CONFIG,
  }),
);

const mockNavigate = jest.fn();

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    id: 'abc_123',
    step: 'merchantRegistration',
    component: 'mobileNumberVerify',
  }),
  useNavigate: () => mockNavigate,
}));

const mockFunctions = {
  fistStepCallback: jest.fn(),
  componentConifgMockfn: jest.fn(),
  stepConfigMockFn: jest.fn(),
  getOnboardingProgressMock: jest.fn(),
};

const initProps = {
  stepSlug: undefined,
  stepConfig: undefined,
};

const TestApp = ({ stepSlug, stepConfig }) => {
  const { handlers, values } = useOnboardingContext();
  const { merchantId, step, component } = values;
  const {
    getStepConfigStepSlug,
    getComponentConfigFromStep,
    handleStepClick,
    getFirstComponentOfStep,
    handleProceedToNextComponent,
    getOnboardingProgress,
  } = handlers;

  const handleGetComponentConfig = () => {
    const config = getComponentConfigFromStep();
    mockFunctions.componentConifgMockfn(config);
  };

  const handleStepConfig = () => {
    const config = getStepConfigStepSlug(stepSlug);
    mockFunctions.stepConfigMockFn(config);
  };

  const handleGetFirstStep = () => {
    const firstStep = getFirstComponentOfStep();
    mockFunctions.fistStepCallback(firstStep);
  };

  const handleGetOnboardingProgress = () => {
    const progress = getOnboardingProgress();
    mockFunctions.getOnboardingProgressMock(progress);
  };

  const handleProceedToNextComponentFn = () => {
    handleProceedToNextComponent({
      __typeName: 'custom_routing',
      routerConditions: {
        mobileNumberVerify: true,
      },
    });
  };

  return (
    <React.Fragment>
      <h1>{step}</h1>
      <h1>{component}</h1>
      <h1>{merchantId}</h1>
      <div>
        <button onClick={() => handleProceedToNextComponent()}>Proceed</button>
        <button onClick={handleProceedToNextComponentFn}>Route to MobileNumberVerify</button>
        <button onClick={() => handleGetFirstStep()}>Get First Step</button>
        <button onClick={() => handleStepClick({ step: stepConfig })}>Step click</button>
        <button onClick={() => handleStepConfig()}>Get Step Config</button>
        <button onClick={() => handleGetComponentConfig()}>Get Component Config</button>
        <button onClick={handleGetOnboardingProgress}>Get Onboarding Progress</button>
      </div>
    </React.Fragment>
  );
};

const location = {
  ...window.location,
  assign: jest.fn(),
};

describe('useOnboardingContext', () => {
  beforeEach(() => {
    Object.defineProperty(window, 'location', {
      value: location,
    });
  });

  test('should return correct constant values based on params', () => {
    const { getByText } = render(<TestApp {...initProps} />);
    expect(getByText('merchantRegistration')).toBeInTheDocument();
    expect(getByText('mobileNumberVerify')).toBeInTheDocument();
    expect(getByText('abc_123')).toBeInTheDocument();
  });

  test('should return correct step config', async () => {
    const { getByText } = render(<TestApp {...initProps} />);
    await userEvent.click(getByText('Get Step Config'));
    expect(mockFunctions.stepConfigMockFn).toHaveBeenCalledWith(
      expect.objectContaining({
        slug: 'merchantRegistration',
      }),
    );
  });

  test('should return null if step doesnt exsit and attempted for step config', async () => {
    const props = {
      ...initProps,
      stepSlug: 'someRandomStep',
    };
    const { getByText } = render(<TestApp {...props} />);
    await userEvent.click(getByText('Get Step Config'));
    expect(mockFunctions.stepConfigMockFn).toHaveBeenCalledWith(undefined);
  });

  test('should call custom callback on step click if custom callback exists', async () => {
    const props = {
      ...initProps,
      stepConfig: MOCK_ONBOARDING_CONFIG[1],
    };
    const { getByText } = render(<TestApp {...props} />);
    await userEvent.click(getByText('Step click'));
    expect(location.assign).toHaveBeenCalledWith('easy.razorpay.com');
  });

  test('should route to correct step on step click', async () => {
    const props = {
      ...initProps,
      stepConfig: MOCK_ONBOARDING_CONFIG[0],
    };
    const { getByText } = render(<TestApp {...props} />);
    await userEvent.click(getByText('Step click'));
    await waitFor(() => {
      expect(mockNavigate).toHaveBeenCalledWith('merchantRegistration');
    });
  });

  test('should return correct first component of a step', async () => {
    const { getByText } = render(<TestApp {...initProps} />);
    await userEvent.click(getByText('Get First Step'));
    expect(mockFunctions.fistStepCallback).toHaveBeenCalledWith('mobileNumberVerify');
  });

  test('should return correct component config', async () => {
    const { getByText } = render(<TestApp {...initProps} />);
    await userEvent.click(getByText('Get Component Config'));
    expect(mockFunctions.componentConifgMockfn).toHaveBeenCalledWith(
      expect.objectContaining({
        slug: 'mobileNumberVerify',
      }),
    );
  });

  test('should proceed to the next component if handleProceedToNextComponent is called', async () => {
    const { getByText } = render(<TestApp {...initProps} />);
    await userEvent.click(getByText('Proceed'));
    await waitFor(() => {
      expect(mockNavigate).toHaveBeenCalledWith(
        '/pos-sales/onboarding/abc_123/merchantRegistration/merchantKycRedirect',
      );
    });
  });

  test('should handle custom routing if handleProceedToNextComponent is called with CustomRouter', async () => {
    const { getByText } = render(<TestApp {...initProps} />);
    await userEvent.click(getByText('Route to MobileNumberVerify'));
    await waitFor(() => {
      expect(mockNavigate).toHaveBeenCalledWith(
        '/pos-sales/onboarding/abc_123/merchantRegistration/mobileNumberVerify',
      );
    });
  });

  test('should return onboarding progress when getOnboardingProgress is called', async () => {
    const { getByText } = render(<TestApp {...initProps} />);
    await userEvent.click(getByText('Get Onboarding Progress'));
    expect(mockFunctions.getOnboardingProgressMock).toHaveBeenCalledWith({
      totalCompletedSteps: 0,
      totalSteps: 3,
    });
  });
});
