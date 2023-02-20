import ListComponent from 'merchant/views/PaymentPages/Products/List';

const defaultProps = {
  loading: false,
  products: [],
};

export const List = (props) => {
  return <ListComponent {...defaultProps} {...props} />;
};

export const LIST_WITH_UNLIMITED_STOCK_PRODUCT = [
  {
    id: 'ctl_KzEahSp7jvcIZR',
    merchant_id: 'HdpjbKuhdREqSV',
    product_name: 'ProductA',
    description: 'proda',
    currency: 'INR',
    status: 'unlimited',
    meta_data: {},
    mode: 'test',
    created_at: 1672646264,
    updated_at: 1672646264,
    deleted_at: null,
    amount: 100,
    discounted_amount: 0,
    units: 0,
    sku_id: '',
    images: [
      {
        id: 'img_KzEahTfkqw0Kf6',
        original: 'https://someimageurl.com/a.jpg',
        title: '',
        description: '',
        small: 'https://someimageurl.com/a.jpg',
        medium: 'https://someimageurl.com/a.jpg',
        large: 'https://someimageurl.com/a.jpg',
      },
    ],
    categories: [],
  },
];

export const LIST_WITH_OUT_OF_STOCK_PRODUCT = [
  {
    id: 'ctl_KzEahSp7jvcIZR',
    merchant_id: 'HdpjbKuhdREqSV',
    product_name: 'ProductA',
    description: 'proda',
    currency: 'INR',
    status: 'out_of_stock',
    meta_data: {},
    mode: 'test',
    created_at: 1672646264,
    updated_at: 1672646264,
    deleted_at: null,
    amount: 100,
    discounted_amount: 0,
    units: 0,
    sku_id: '',
    images: [
      {
        id: 'img_KzEahTfkqw0Kf6',
        original: 'https://someimageurl.com/a.jpg',
        title: '',
        description: '',
        small: 'https://someimageurl.com/a.jpg',
        medium: 'https://someimageurl.com/a.jpg',
        large: 'https://someimageurl.com/a.jpg',
      },
    ],
    categories: [],
  },
];

export const LIST_WITH_LIMITED_STOCK_PRODUCT = [
  {
    id: 'ctl_KzEahSp7jvcIZR',
    merchant_id: 'HdpjbKuhdREqSV',
    product_name: 'ProductA',
    description: 'proda',
    currency: 'INR',
    status: 'in_stock',
    meta_data: {},
    mode: 'test',
    created_at: 1672646264,
    updated_at: 1672646264,
    deleted_at: null,
    amount: 100,
    discounted_amount: 0,
    units: 10,
    sku_id: '',
    images: [
      {
        id: 'img_KzEahTfkqw0Kf6',
        original: 'https://someimageurl.com/a.jpg',
        title: '',
        description: '',
        small: 'https://someimageurl.com/a.jpg',
        medium: 'https://someimageurl.com/a.jpg',
        large: 'https://someimageurl.com/a.jpg',
      },
    ],
    categories: [],
  },
];
