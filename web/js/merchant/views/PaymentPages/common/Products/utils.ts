import {
  IPaymentPagesCategory,
  IPaymentPagesProduct,
  PaymentPagesStorefrontType,
} from 'merchant/reducers/paymentPages/storefront';
import { numberFormatRegex, rupeesToPaise } from 'common/utils/rzp-utils';
import { ModeTypes } from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/Storefront/types';
import { isEmail, isPhone } from 'common/utils/validators';

export const PRODUCT_STATUS = {
  OUT_OF_STOCK: 'out_of_stock',
  IN_STOCK: 'in_stock',
  UNLIMITED: 'unlimited',
  INACTIVE: 'inactive',
};

type _ProductStatusKeys = keyof typeof PRODUCT_STATUS;
export type ProductStatusKeys = (typeof PRODUCT_STATUS)[_ProductStatusKeys];

export const emptyProduct: IPaymentPagesProduct = {
  product_name: '',
  amount: '',
  discounted_amount: '',
  units: '',
  images: [],
  category: null,
  description: '',
  id: '', // generated before saving
  status: PRODUCT_STATUS.IN_STOCK,
};

type ProductKeysType = keyof IPaymentPagesProduct;
type IPaymentPageProductError = Record<ProductKeysType, string>;
export const emptyProductErrors: IPaymentPageProductError = {
  product_name: '',
  amount: '',
  discounted_amount: '',
  units: '',
  images: '',
  category: '',
  description: '',
  id: '', // unused
  status: '', // unused
};

interface validateResponse {
  errors: IPaymentPageProductError;
  isValid: boolean;
}

export const validateProduct = (product: IPaymentPagesProduct): validateResponse => {
  const errors = { ...emptyProductErrors };
  const { product_name, amount, discounted_amount, units } = product;
  let isValid = true;

  if (!product_name) {
    errors.product_name = 'Product name is required';
    isValid = false;
  }
  if (!amount) {
    errors.amount = 'Price is required';
    isValid = false;
  }
  if (discounted_amount) {
    if (!amount) {
      errors.discounted_amount = 'Discounted price requires a selling price';
      isValid = false;
    }
    if (amount && Number(discounted_amount) > Number(amount)) {
      errors.discounted_amount = 'Discounted price must be lesser than selling price';
      isValid = false;
    }
  }
  if (units) {
    if (units.length > 9) {
      errors.units = 'Stock cannot be greater than 9 digits';
      isValid = false;
    }
  }
  return { errors, isValid };
};

type PaymentPagesProductKeys = keyof IPaymentPagesProduct;

export const numericFields: PaymentPagesProductKeys[] = ['units'];
export const decimalFields: PaymentPagesProductKeys[] = ['amount', 'discounted_amount'];
export const numericRegex = new RegExp(/^[0-9]*$/);
export const numberWith2Digits = new RegExp(/^[0-9]*(\.[0-9]{0,2})?$/);

export const formatTextAmountField = (number: string): string => {
  // TODO: add multiple currency support in future
  const result = Number(number).toFixed(2).replace(numberFormatRegex, '$1,');
  return `₹${result}`;
};

export const generateProductRequest = (product: Omit<IPaymentPagesProduct, 'status'>) => {
  const { product_name, description, amount, discounted_amount, units, images, category } = product;

  const _amount = rupeesToPaise(amount);
  const _discounted_amount = discounted_amount ? rupeesToPaise(discounted_amount) : undefined;
  const { status, units: _units } = getStatusAndUnitsInformation(units);
  return {
    product_name,
    description,
    amount: _amount,
    discounted_amount: _discounted_amount,
    currency: 'INR',
    status,
    units: _units,
    // meta_data: {
    //   this: 'is',
    //   me: 'sanjib',
    //   hey: {
    //     what: 'is up',
    //   },
    // },
    images,
    categories: category
      ? [
          {
            id: category,
          },
        ]
      : undefined,
  };
};

function getStatusAndUnitsInformation(units: string) {
  let status;
  if (units === '') {
    return {
      status: PRODUCT_STATUS.UNLIMITED,
      units: undefined,
    };
  }

  const _units = Number(units);
  if (_units >= 1) {
    status = PRODUCT_STATUS.IN_STOCK;
  }
  if (_units === 0) {
    status = PRODUCT_STATUS.OUT_OF_STOCK;
  }
  return {
    status,
    units: _units,
  };
}

export const validateStorefront = (storefront: PaymentPagesStorefrontType) => {
  let isValid = true;
  let error = '';

  if (!storefront.entity.title) {
    isValid = false;
    error = 'Storefront name cannot be empty!';
  }
  if (!storefront.entity.contactEmail) {
    isValid = false;
    error = 'Contact email cannot be empty!';
  }
  if (!storefront.entity.contactPhone) {
    isValid = false;
    error = 'Contact phone cannot be empty!';
  }
  if (storefront.entity.products.length === 0) {
    isValid = false;
    error = 'Please add a product to your store!';
  }
  return {
    isValid,
    error,
  };
};

export const generateStorefrontRequest = (
  storefront: PaymentPagesStorefrontType,
  mode: ModeTypes,
) => {
  const {
    entity: { title, products, contactEmail, contactPhone, expire_by, settings, slug },
  } = storefront;
  //TODO: manual receipt is not enabled, hardcoding enbablement for now
  settings.enable_receipt = '1';

  return {
    title,
    // title: 'Cherry',
    // description: 'Description of the Chocolate',
    currency: 'INR',
    // expire_by: 1671779592,
    type: 'store',
    // meta_data: null,
    support_email: contactEmail,
    support_contact: contactPhone,
    // support_email: 'test@gmail.com',
    // support_contact: '12345565',
    mode,
    // notes: {
    //   key1: 'Select your favourite Chocolate',
    // },
    line_items: products.map((item, i) => ({
      description: item.description,
      status: 'active',
      catalog_id: item.id,
      position: i + 1,
      entity_type: 'price',
      mandatory: true,
    })),
    expire_by,
    slug: slug || undefined,
    // if settings is empty, send any valid key with empty string value, as this key cannot be sent as undefined
    settings: Object.keys(settings).length
      ? settings
      : {
          payment_success_message: '',
        },
  };
};

export const validateCategory = (
  allCategories: IPaymentPagesCategory[],
  categoryName: string,
): { error: string; isValid: boolean } => {
  let error = '';
  let isValid = true;

  if (categoryName) {
    const isAlreadyPresent = allCategories.find(
      (item) => item.name?.trim().toLocaleLowerCase() === categoryName.trim().toLocaleLowerCase(),
    );

    if (isAlreadyPresent) {
      error = 'This category already exists';
      isValid = false;
    }
  } else {
    error = 'Please enter Category name';
    isValid = false;
  }

  return { error, isValid };
};

export const validateContactDetails = ({
  contactEmail,
  contactPhone,
}: {
  contactEmail: string;
  contactPhone: string;
}): { errors: { contactEmail: string; contactPhone: string }; isValid: boolean } => {
  const errors = {
    contactEmail: '',
    contactPhone: '',
  };
  let isValid = true;

  if (!(contactPhone && isPhone(contactPhone))) {
    errors.contactPhone = 'Please enter correct Phone no.';
    isValid = false;
  }

  if (!(contactEmail && isEmail(contactEmail))) {
    errors.contactEmail = 'Please enter correct email id';
    isValid = false;
  }

  return { errors, isValid };
};
