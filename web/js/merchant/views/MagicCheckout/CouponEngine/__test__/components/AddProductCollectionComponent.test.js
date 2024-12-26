import AddProductCollectionComponent from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/AddProductCollection/AddProductCollectionComponent';
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';
import { render, screen, waitFor } from 'test-utils';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: jest.fn(),
}));

const DISCOUNT_OFFERED_CONTEXT_WITH_PRODUCT_SELECTION = {
  widgetsData: {
    discountOffered: {
      discountApplicableTo: 'products',
      discountedItemsList: ['gid://shopify/ProductVariant/43566201340127'],
      discountedItemsDisplayList: [
        {
          product_id: 'gid://shopify/Product/7882888544479',
          product_name: 'Wheat Daliya | Highr fiber',
          product_image_url:
            'https://cdn.shopify.com/s/files/1/0663/8416/7135/products/WheatDaliya.jpg?v=1677993061',
          variants: ['gid://shopify/ProductVariant/43566201340127'],
        },
      ],
    },
  },
  setWidgetsData: jest.mock(),
  errorStates: {
    discountOffered: null,
  },
  setErrorStates: jest.mock(),
};

const DISCOUNT_OFFERED_CONTEXT_WITHOUT_PRODUCT = {
  widgetsData: {
    discountOffered: {
      discountApplicableTo: 'products',
      discountedItemsList: [],
      discountedItemsDisplayList: [],
    },
  },
  setWidgetsData: jest.mock(),
  errorStates: {
    discountOffered: null,
  },
  setErrorStates: jest.mock(),
};

describe('freebie coupon', () => {
  beforeAll(() => {
    // Mocking `useParams` for the first test block
    require('react-router-dom').useParams.mockReturnValue({ couponName: 'freebie_item' });
  });
  test('Should render callout & enforce selection restrictions after selecting a freebie product variant', async () => {
    render(
      <ModalContext.Provider value={DISCOUNT_OFFERED_CONTEXT_WITH_PRODUCT_SELECTION}>
        <AddProductCollectionComponent stateObject="discountOffered" />
      </ModalContext.Provider>,
    );
    await waitFor(() => {
      expect(screen.getByText('Applies to')).toBeInTheDocument();
      // A Free product is already added
      expect(screen.getByText(/Wheat Daliya/i)).toBeInTheDocument();
      // we should always callout selection restriction incase of freebie
      expect(screen.getByText('Maximum 1 items allowed')).toBeInTheDocument();
      // Add Products should not be rendered incase of selection restriction is active
      expect(screen.queryAllByText(/Add Products/i).length).toBe(0);
    });
  });
  test('Should render callout but should not enforce selection restrictions if no freebie item is selected', async () => {
    render(
      <ModalContext.Provider value={DISCOUNT_OFFERED_CONTEXT_WITHOUT_PRODUCT}>
        <AddProductCollectionComponent stateObject="discountOffered" />
      </ModalContext.Provider>,
    );
    await waitFor(() => {
      expect(screen.getByText('Applies to')).toBeInTheDocument();
      // A Free product is not added
      expect(screen.queryByText(/Wheat Daliya/i)).not.toBeInTheDocument();
      // we should always callout selection restriction incase of freebie
      expect(screen.getByText('Maximum 1 items allowed')).toBeInTheDocument();
      // Add Products should be rendered
      expect(screen.queryByText(/Add Products/i)).toBeInTheDocument();
    });
  });
});

describe('BxGy coupon', () => {
  beforeAll(() => {
    // Mocking `useParams` for the first test block
    require('react-router-dom').useParams.mockReturnValue({ couponName: 'buyx_gety' });
  });

  test('Should not render callout & should not enforce selection restrictions before selecting a product variant', async () => {
    render(
      <ModalContext.Provider value={DISCOUNT_OFFERED_CONTEXT_WITHOUT_PRODUCT}>
        <AddProductCollectionComponent stateObject="discountOffered" />
      </ModalContext.Provider>,
    );
    await waitFor(() => {
      expect(screen.getByText('Applies to')).toBeInTheDocument();
      // Products radio button is selected
      expect(document.querySelectorAll('input[checked=""]').length).toBe(1);
      expect(document.querySelector('input[checked=""]').value).toBe('products');
      // A product is already added
      expect(screen.queryByText(/Wheat Daliya/i)).not.toBeInTheDocument();
      // Selection restrictions should not be rendered
      expect(screen.queryByText('Maximum 1 items allowed')).not.toBeInTheDocument();
      // Add Products should be rendered even if an item is already added
      expect(screen.queryByText(/Add Products/i)).toBeInTheDocument();
    });
  });

  test('Should not render callout & should not enforce selection restrictions after selecting a product variant', async () => {
    render(
      <ModalContext.Provider value={DISCOUNT_OFFERED_CONTEXT_WITH_PRODUCT_SELECTION}>
        <AddProductCollectionComponent stateObject="discountOffered" />
      </ModalContext.Provider>,
    );
    await waitFor(() => {
      expect(screen.getByText('Applies to')).toBeInTheDocument();
      // Products radio button is selected
      expect(document.querySelectorAll('input[checked=""]').length).toBe(1);
      expect(document.querySelector('input[checked=""]').value).toBe('products');
      // A product is already added
      expect(screen.getByText(/Wheat Daliya/i)).toBeInTheDocument();
      // Selection restrictions should not be rendered
      expect(screen.queryByText('Maximum 1 items allowed')).not.toBeInTheDocument();
      // Add Products should be rendered even if an item is already added
      expect(screen.queryByText(/Add Products/i)).toBeInTheDocument();
    });
  });
});
