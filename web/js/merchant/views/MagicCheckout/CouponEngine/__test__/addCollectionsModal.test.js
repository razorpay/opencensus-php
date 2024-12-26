import { render, screen, userEvent, waitFor } from 'test-utils';
import { storeWithInitialState } from 'merchant/store';

import AddCollectionsModal from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/AddProductCollection/AddCollectionModal';

describe('AddCollectionsModal', () => {
  test('should render correctly', () => {
    render(<AddCollectionsModal />);

    expect(screen.getByText('Choose collection')).toBeInTheDocument();
    expect(screen.queryAllByPlaceholderText('Search collections')).toHaveLength(1);
    expect(screen.getByText('Cancel')).toBeInTheDocument();
    expect(screen.getByText('Confirm')).toBeInTheDocument();
  });

  test('should render collections list correctly', async () => {
    render(<AddCollectionsModal />);

    await waitFor(() => {
      expect(screen.getByText('test1')).toBeInTheDocument();
      expect(screen.getByText('test2')).toBeInTheDocument();
    });
  });

  test('should render collections list correctly - RCOD flow', async () => {
    const initState = {
      magicCheckout: {
        dashboard_view: 'rcod',
      },
    };
    render(<AddCollectionsModal />, {
      reduxStore: storeWithInitialState({ ...initState }),
    });

    await waitFor(() => {
      expect(screen.getByText('test1-rcod')).toBeInTheDocument();
      expect(screen.getByText('test2-rcod')).toBeInTheDocument();
    });
  });

  test('should not render & enforce selection restriction on products purchased widget in case of freebie coupon', async () => {
    // Arrange: Render the component with necessary props
    render(<AddCollectionsModal couponName="freebie_item" widgetName="productsPurchased" />);

    // Assert initial state: No restriction message should be shown, and items should be rendered
    await waitFor(() => {
      expect(screen.queryByText('Maximum 1 selections allowed')).not.toBeInTheDocument();
      expect(screen.getByText('test1')).toBeInTheDocument();
      expect(screen.getByText('test2')).toBeInTheDocument();
    });

    // Assert: There should be two checkboxes rendered
    const checkboxes = screen.getAllByRole('checkbox');
    expect(checkboxes).toHaveLength(2);

    // Assert: Initially, no checkboxes should be disabled
    expect(document.getElementsByClassName('Input--disabled').length).toBe(0);

    // Act: Simulate clicking on the first checkbox
    await userEvent.click(checkboxes[0]);

    // Assert: No checkboxes should become disabled after the click
    await waitFor(() => {
      expect(document.getElementsByClassName('Input--disabled').length).toBe(0);
    });
  });

  test('should not render & enforce selection restriction on discount offered widget incase of freebie coupon', async () => {
    // Arrange: Render the component with necessary props
    render(<AddCollectionsModal couponName="freebie_item" widgetName="discountOffered" />);

    // Assert initial state: No restriction message should be shown, and items should be rendered
    await waitFor(() => {
      expect(screen.queryByText('Maximum 1 selections allowed')).not.toBeInTheDocument();
      expect(screen.getByText('test1')).toBeInTheDocument();
      expect(screen.getByText('test2')).toBeInTheDocument();
    });

    // Assert: There should be two checkboxes rendered
    const checkboxes = document.querySelectorAll("input[type='checkbox']");
    expect(checkboxes.length).toBe(2);

    // Assert: Initially, no checkboxes should be disabled
    expect(document.getElementsByClassName('Input--disabled').length).toBe(0);

    // Act: Simulate clicking on the first checkbox
    userEvent.click(checkboxes[0]);

    // Assert: No checkboxes should become disabled after the click
    await waitFor(() => {
      expect(document.getElementsByClassName('Input--disabled').length).toBe(0);
    });
  });
});
