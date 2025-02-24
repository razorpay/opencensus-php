import React from 'react';
import { render, screen } from 'test-utils';
import NewInvitation from '../components/NewInvitation';

jest.mock('@razorpay/i18nify-js', () => {
  const actualI18nify = jest.requireActual('@razorpay/i18nify-js');
  return {
    ...actualI18nify,
    getStates: jest.fn(() => ({
      MP: {
        cities: {
          Bhopal: { name: 'Bhopal' },
          Indore: { name: 'Indore' },
        },
        name: 'Madhya Pradesh',
      },
      DL: {
        cities: {
          'East Delhi': { name: 'East Delhi' },
          'South Delhi': { name: 'South Delhi' },
        },
        name: 'Delhi',
      },
    })),
  };
});

jest.mock('redux-form', () => {
  const formValues = {
    role: 'partner_agent',
    email: 'test@example.com',
    sender_name: 'Test Sender',
    'metadata.state': 'Delhi',
    'metadata.city': 'East Delhi',
  };

  const actualReduxForm = jest.requireActual('redux-form');

  return {
    ...actualReduxForm,
    reduxForm: () => (Component) => Component,
    Field: ({ name, component: Component, validate, ...props }) => {
      return (
        <Component
          placeholder={name}
          input={{
            name,
            defaultValue: '',
          }}
          meta={{
            touched: false,
            error: '',
            warning: '',
          }}
          {...props}
        />
      );
    },
    formValueSelector: () => (state, field) => formValues[field],
  };
});

const mockProps = {
  selectedRole: 'partner_agent',
  selectedState: 'Delhi',
  splitz: {
    isExperimentEvaluated: () => true,
    evaluateExperiment: jest.fn(),
    bulkEvaluateExperiments: () => Promise.resolve(),
    isInitialized: true,
    abExperiments: {},
    activeDashboard: 'partner' as const,
  },
  isHandlingPosPartnerAgent: true,
  isRenderedFromPartnerRoute: false,
  initialize: jest.fn(),
  handleSubmit: jest.fn(),
  visibleFields: {
    email: true,
    role: true,
  },
  defaults: {
    role: 'partner_agent',
    email: '',
    metadata: {
      state: 'Madhya Pradesh',
      city: 'South Delhi',
      team: 'Enterprise',
      hiring_manager: '',
      bu_head: '',
      zone: '',
      name: '',
      mobile: '',
    },
  },
};
function renderApp(props) {
  return render(<NewInvitation {...mockProps} {...props} />, {
    initialState: {
      session: {
        user: {
          user: {
            email: 'omnitest@gmail.com',
            contact_mobile: '987763437438',
          },
        },
      },
    },
  });
}

describe('NewInvitation', () => {
  test('should render pos agent form when role is partner_agent', () => {
    renderApp({});
    expect(screen.getByText(/agent name/i)).toBeInTheDocument();
    expect(screen.getByText(/hiring manager/i)).toBeInTheDocument();
    expect(screen.getByText(/business unit head/i)).toBeInTheDocument();
    expect(screen.getByText(/phone number/i)).toBeInTheDocument();
    expect(screen.getByText(/zone/i)).toBeInTheDocument();
    expect(screen.getByText(/team/i)).toBeInTheDocument();
    expect(screen.getByText(/state/i)).toBeInTheDocument();
    expect(screen.getByText(/city/i)).toBeInTheDocument();
    expect(screen.getByText(/Can only access pos sales dashboard/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /submit/i })).toBeEnabled();
  });
});
