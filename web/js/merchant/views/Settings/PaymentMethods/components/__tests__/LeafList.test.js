import LeafList from 'merchant/views/Settings/PaymentMethods/components/LeafList';
import { LEAF_LIST_TESTS } from 'merchant/views/Settings/PaymentMethods/components/__tests__/mocks/fixtures/leafList';
import { render, screen } from 'test-utils';

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
