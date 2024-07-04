import { useSplitzService } from 'common/splitz';
import LeafList from 'merchant/views/Settings/PaymentMethods/components/LeafList';
import { LEAF_LIST_TESTS } from 'merchant/views/Settings/PaymentMethods/components/__tests__/mocks/fixtures/leafList';
import { render, screen } from 'test-utils';

jest.mock('common/splitz', () => ({
  useSplitzService: jest.fn(() => ({
    abExperiments: {
      recurring_instrument_requester: {
        variables: {
          result: 'off',
        },
      },
    },
  })),
}));

describe('Test LeafList component', () => {
  test('should not render money saver export account instrument when international is false', () => {
    render(<LeafList />, {
      initialState: LEAF_LIST_TESTS[0].input.initialState,
    });

    expect(screen.queryByText(LEAF_LIST_TESTS[0].output.header)).not.toBeInTheDocument();
  });

  test('should render money saver export account instrument when international is true', () => {
    render(<LeafList />, {
      initialState: LEAF_LIST_TESTS[1].input.initialState,
    });

    expect(screen.getByText(LEAF_LIST_TESTS[1].output.header)).toBeInTheDocument();
  });
});

describe('Test LeafList component for recurring methods', () => {
  test('should render upi along with upi autopay instrument', () => {
    useSplitzService.mockImplementation(() => ({
      abExperiments: {
        recurring_instrument_requester: {
          variables: {
            result: 'on',
          },
        },
      },
    }));
    render(<LeafList />, {
      initialState: LEAF_LIST_TESTS[2].input.initialState,
    });
    expect(screen.getAllByText('UPI')).toHaveLength(2);
    expect(screen.getAllByText('UPI Autopay')).toHaveLength(2);
    expect(screen.getByText('Documentation')).toBeInTheDocument();
  });

  test('should render cards along with recurring cards instrument', () => {
    useSplitzService.mockImplementation(() => ({
      abExperiments: {
        recurring_instrument_requester: {
          variables: {
            result: 'on',
          },
        },
      },
    }));
    render(<LeafList />, {
      initialState: LEAF_LIST_TESTS[3].input.initialState,
    });
    expect(screen.getByText('Domestic Cards')).toBeInTheDocument();
    expect(screen.getAllByText('Cards Recurring')).toHaveLength(2);
    expect(screen.getByText('Visa Cards')).toBeInTheDocument();
  });
});
