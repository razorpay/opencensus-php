import { render, screen, waitFor } from 'test-utils';

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
});
