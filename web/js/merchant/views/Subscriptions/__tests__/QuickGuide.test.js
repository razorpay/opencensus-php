import React from 'react';
import { Provider } from 'react-redux';
import store, { storeWithInitialState } from 'merchant/store';
import QuickGuide, {
  getSubscriptionQuickGuideIsClosed,
} from 'merchant/views/Subscriptions/QuickGuide';
import { QuickGuideStep } from 'merchant/components/QuickGuide/QuickStepGuide';
import { render, screen } from 'test-utils';
import { getQuickGuideData } from 'merchant/views/Subscriptions/QuickGuide/data';

jest.mock('merchant/components/QuickGuide', () => ({
  ...jest.requireActual('merchant/components/QuickGuide'),
  __esModule: true,
  getQuickGuideIsClosedFromLocalStorage: jest.fn().mockReturnValueOnce(true).mockReturnValue(false),
}));

const globalState = store.getState();
const stateWithData = {
  ...globalState,
  subscriptions: {
    ...globalState.subscriptions,
    loading: false,
    items: [
      { id: 'sub_1', status: 'active' },
      { id: 'sub_2', status: 'active' },
    ],
  },
  plans: {
    ...globalState.plans,
    loading: false,
    items: [
      { id: 'plan_1', status: 'active' },
      { id: 'plan_2', status: 'active' },
    ],
  },
};

const quickGuideProps = (status) => {
  return {
    planStatus: status,
    subscriptionStatus: status,
    paymentStatus: status,
  };
};

const renderApp = (initialState = {}, ...rest) => {
  return render(
    <Provider store={storeWithInitialState(initialState)}>
      <QuickGuide {...quickGuideProps('done')} {...rest} />
    </Provider>,
  );
};

describe('Subscriptions QuickGuide', () => {
  const renderQuickGuideStep = (statusObj = {}) => {
    let activeState = {};
    const { planStatus, subscriptionStatus, paymentStatus, quickStepState } = statusObj;
    if (quickStepState === 'Plan') {
      activeState = { ...getQuickGuideData.plan(planStatus), status: planStatus };
    }
    if (quickStepState === 'Subscription') {
      activeState = {
        ...getQuickGuideData.subscription(subscriptionStatus),
        status: subscriptionStatus,
      };
    }
    if (quickStepState === 'Payments') {
      activeState = { ...getQuickGuideData.payment(paymentStatus), status: paymentStatus };
    }

    return render(<QuickGuideStep step="Subscription" feature="subscriptions" {...activeState} />);
  };

  test('Should match quick guide Plan content in "pending" state', () => {
    renderQuickGuideStep({
      planStatus: 'pending',
      quickStepState: 'Plan',
    });
    expect(screen.getByText('1. Create Plan')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Create your custom plans with different billing cycles and prices for your business.',
      ),
    ).toBeInTheDocument();
  });

  test('Should match quick guide Plan content in done state', () => {
    renderQuickGuideStep({
      planStatus: 'done',
      quickStepState: 'Plan',
    });
    expect(screen.getByText('1. Plan Created')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Create your custom plans with different billing cycles and prices for your business.',
      ),
    ).toBeInTheDocument();
  });

  test('Should match quick guide Subscription content in pending state', () => {
    renderQuickGuideStep({
      planStatus: 'pending',
      subscriptionStatus: 'pending',
      quickStepState: 'Subscription',
    });
    expect(screen.getByText('2. Create Subscription')).toBeInTheDocument();
    expect(
      screen.getByText('Create subscriptions for your customers to receive recurring payments'),
    ).toBeInTheDocument();
  });

  test('Should match quick guide Subscription content in done state', () => {
    renderQuickGuideStep({
      planStatus: 'done',
      subscriptionStatus: 'done',
      quickStepState: 'Subscription',
    });
    expect(screen.getByText('2. Subscription Created')).toBeInTheDocument();
    expect(
      screen.getByText('Create subscriptions for your customers to receive recurring payments'),
    ).toBeInTheDocument();
  });

  test('Should match quick guide Payments content in pending state', () => {
    renderQuickGuideStep({
      planStatus: 'pending',
      subscriptionStatus: 'pending',
      paymentStatus: 'pending',
      quickStepState: 'Payments',
    });
    expect(screen.getByText('3. Receive Payments')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Share subscription link with your customers to receive recurring payments.',
      ),
    ).toBeInTheDocument();
  });

  test('Should match quick guide Payments content in done state', () => {
    renderQuickGuideStep({
      planStatus: 'done',
      subscriptionStatus: 'done',
      paymentStatus: 'done',
      quickStepState: 'Payments',
    });
    expect(screen.getByText('3. Payments Received')).toBeInTheDocument();
    expect(screen.getByText('Check payments received under Transactions tab.')).toBeInTheDocument();
  });

  test('Should render GET STARTED link', () => {
    renderApp(stateWithData);
    expect(screen.getByText('GET STARTED')).toBeInTheDocument();
  });

  test('Subscription Quick Guide should be closed if its closed in localstorage', () => {
    const props = {
      subscriptions: {
        items: [{ id: 'subs_123' }],
      },
    };

    expect(getSubscriptionQuickGuideIsClosed(props)).toBe(true);
  });

  test('Subscription Quick Guide shouldnt be closed if its not closed in localstorage', () => {
    const props = {
      subscriptions: {
        items: [
          { id: 'subs_1', status: 'active' },
          { id: 'subs_2', status: 'created' },
          { id: 'subs_3', status: 'created' },
        ],
      },
    };

    expect(getSubscriptionQuickGuideIsClosed(props)).toBe(false);
    props.subscriptions.items[1].status = 'active';
    expect(getSubscriptionQuickGuideIsClosed(props)).toBe(true);
  });
});
