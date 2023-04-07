import store from 'merchant/store';
import Timeline from 'merchant/views/Settlements/v3/components/Timeline';
import React from 'react';
import { render, screen } from 'test-utils';
import moment from 'moment';
import { titleCase } from 'common/utils/rzp-utils';

const globalStore = store.getState();

const getInitialState = ({ settlement = {} }) => {
  return {
    ...globalStore,
    session: {
      ...globalStore.session,
      user: {
        merchant: {
          currency: 'INR',
        },
      },
    },
    settlement: {
      ...globalStore.settlement,
      settlement: {
        ...globalStore.settlement.settlement,
        amount: 23886,
        created_at: 1678077015,
        entity: 'settlement',
        fees: 0,
        id: 'setl_JCVHSjHRi9QHto',
        status: 'processed',
        tax: 0,
        utr: 'cg2mpl08cfbf3p7nghfg',
        ...settlement,
      },
    },
  };
};

const renderApp = ({ initialState }) =>
  render(<Timeline />, {
    initialState,
  });

describe('Timeline', () => {
  const verifyCreatedTimeline = (timestamp) => {
    expect(screen.getByText('Created')).toBeInTheDocument();
    expect(screen.getByText(moment.unix(timestamp).format('llll'))).toBeInTheDocument();
  };

  test('should render timeline header', () => {
    const initialState = getInitialState({ settlement: {} });
    renderApp({ initialState });
    expect(screen.getByText('Timeline')).toBeInTheDocument();
  });

  test('should render status and created time when status is created', () => {
    const initialState = getInitialState({
      settlement: {
        status: 'created',
        created_at: 1678077015,
      },
    });
    renderApp({ initialState });
    verifyCreatedTimeline(1678077015);
  });

  test.each([
    { status: 'failed', createdAt: 1678077015 },
    { status: 'processed', createdAt: 1678057615 },
  ])('should render %s status timeline', ({ status, createdAt }) => {
    const initialState = getInitialState({
      settlement: {
        status,
        created_at: createdAt,
      },
    });
    renderApp({ initialState });
    verifyCreatedTimeline(createdAt);
    expect(screen.getByText(titleCase(status))).toBeInTheDocument();
  });
});
