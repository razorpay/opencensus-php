import { IPaymentPagesProduct } from 'merchant/reducers/paymentPages/storefront';
import { convertUnitsBasedOnStatus } from 'merchant/reducers/paymentPages/transformer';
import { ICategories } from 'merchant/reducers/paymentPages/types';
import { formatTextAmountField } from 'merchant/views/PaymentPages/common/Products/utils';
import { FlipOptions, ICheckbox, IHostedPagesProduct, PixelCrop } from './types';
import ProductPlaceholderImage from 'assets/payment_pages/product-image-placeholder.png';
import { SOCIAL_HANDLES } from 'merchant/views/PaymentPages/PaymentPages/constants';

// export const _product: IHostedPagesProduct = {
//   id: 'ppi_KBkFIK490VlplY',
//   name: 'Shoes 2',
//   description: 'Comfortable shoes',
//   images: [
//     'https://s3.ap-south-1.amazonaws.com/rzp-prod-merchant-assets/payment-link/description/kbkepqwxq0qyko',
//   ],
//   selling_price: 25000,
//   discounted_price: 20000,
//   stock: 20,
//   stock_available: 20,
//   stock_sold: 0,
//   status: 'active',
//   category: 'Category 1',
// };

export const _livePreviewResponse = {
  key_id: 'rzp_test_invalid',
  is_test_mode: false,
  environment: 'production',
  merchant: {
    id: 'invalid_mid',
    name: '',
    image: '',
    brand_color: 'rgb(138, 68, 68)',
    brand_text_color: '#000000',
    support_details: {
      support_email: '',
      support_mobile: '',
    },
  },
  store: {
    id: 'store_invalid_id',
    currency: 'INR',
    title: 'Preview store',
    storefront_v1_enabled: false,
    description: '',
    slug: 'my-store1123',
    status: 'active',
    store_url: 'https://stores.razorpay.com/my-store1123',
    notes: [],
    created_at: 1649755007,
    updated_at: 1661841247,
    settings: [],
    products: [],
  },
  base_url: 'https://api.razorpay.com',
  checkout_id: 'pl_invalid_id',
  keyless_header: 'api_v1:/invalid_id',
};

export const sampleProduct: IPaymentPagesProduct = {
  id: 'sample_product',
  product_name: 'Sample Product',
  amount: '2000',
  discounted_amount: '1000',
  units: '100',
  images: [],
  category: null,
  description: 'Sample product description',
  status: 'in_stock',
};

export function convertToHostedPagesProduct(
  products: IPaymentPagesProduct[],
  allCategories: ICategories,
): IHostedPagesProduct[] {
  return products.map((item) => {
    const categories: ICategories = [];
    if (item.category) {
      const category = allCategories.find((c) => c.id === item.category);
      if (category) {
        categories.push(category);
      }
    }
    return {
      id: item.id,
      name: item.product_name,
      description: item.description,
      images: item.images ? item.images.map((img) => img.original) : [],
      // convert to paisa
      selling_price: Number(item.amount) * 100,
      discounted_price: item.discounted_amount ? Number(item.discounted_amount) * 100 : undefined,
      stock: Number(convertUnitsBasedOnStatus(Number(item.units), item.status)),
      status: item.status,
      categories,
      //unused keys
      stock_available: 0,
      stock_sold: 0,
    };
  });
}

export function getStorefrontHostedPagesFormat(
  merchantData,
  isStorefrontV1Enabled,
  title,
  products,
) {
  const livePreviewResponse = {
    ..._livePreviewResponse,
    merchant: {
      ..._livePreviewResponse.merchant,
      ...merchantData,
    },
    store: {
      ..._livePreviewResponse.store,
      storefront_v1_enabled: isStorefrontV1Enabled,
      title,
      products,
    },
  };
  return livePreviewResponse;
}

export const generateCheckboxesFromAllProducts = (
  allProducts: IPaymentPagesProduct[],
  products: IPaymentPagesProduct[],
): ICheckbox[] => {
  return allProducts.map((item) => {
    // check if a products is added in the current storefront
    const isFound = products.find((product) => product.id === item.id);
    return {
      product_name: item.product_name,
      id: item.id,
      checked: !!isFound,
      disabled: !!isFound,
      images: item.images,
      amount: formatTextAmountField(item.amount),
      discounted_amount: item.discounted_amount
        ? formatTextAmountField(item.discounted_amount)
        : item.discounted_amount,
    };
  });
};

export function getPrimaryImage(images: IPaymentPagesProduct['images']): string {
  return (images[0] && images[0].original) || ProductPlaceholderImage;
}

export const createImage = (url: string): Promise<HTMLImageElement> =>
  new Promise((resolve, reject) => {
    const image = new Image();
    image.addEventListener('load', () => resolve(image));
    image.addEventListener('error', (error) => reject(error));
    image.setAttribute('crossOrigin', 'anonymous');
    image.src = url;
  });

export const getRadianAngle = (degreeValue: number): number => (degreeValue * Math.PI) / 180;

export const rotateSize = (
  width: number,
  height: number,
  rotation: number,
): { width: number; height: number } => {
  const rotRad = getRadianAngle(rotation);

  return {
    width: Math.abs(Math.cos(rotRad) * width) + Math.abs(Math.sin(rotRad) * height),
    height: Math.abs(Math.sin(rotRad) * width) + Math.abs(Math.cos(rotRad) * height),
  };
};

export const getCroppedImg = async (
  imageSrc: string,
  pixelCrop: PixelCrop,
  rotation: number = 0,
  flip: FlipOptions = { horizontal: false, vertical: false },
): Promise<File | null> => {
  const image = await createImage(imageSrc);
  const canvas = document.createElement('canvas');
  const ctx = canvas.getContext('2d');

  if (!ctx) {
    return null;
  }

  const rotRad = getRadianAngle(rotation);

  const { width: bBoxWidth, height: bBoxHeight } = rotateSize(image.width, image.height, rotation);

  canvas.width = bBoxWidth;
  canvas.height = bBoxHeight;

  ctx.translate(bBoxWidth / 2, bBoxHeight / 2);
  ctx.rotate(rotRad);
  ctx.scale(flip.horizontal ? -1 : 1, flip.vertical ? -1 : 1);
  ctx.translate(-image.width / 2, -image.height / 2);

  ctx.drawImage(image, 0, 0);

  const croppedCanvas = document.createElement('canvas');
  const croppedCtx = croppedCanvas.getContext('2d');

  if (!croppedCtx) {
    return null;
  }

  croppedCanvas.width = pixelCrop.width;
  croppedCanvas.height = pixelCrop.height;

  croppedCtx.drawImage(
    canvas,
    pixelCrop.x,
    pixelCrop.y,
    pixelCrop.width,
    pixelCrop.height,
    0,
    0,
    pixelCrop.width,
    pixelCrop.height,
  );

  // Return as a Blob URL
  return new Promise((resolve, reject) => {
    croppedCanvas.toBlob((blob) => {
      if (!blob) {
        reject(new Error('Canvas is empty or could not create a blob'));
        return;
      }
      resolve(new File([blob], 'cropped.jpeg', { type: 'image/jpeg' }));
    }, 'image/jpeg');
  });
};

export const getSocialHandleSrc = (name) => {
  const handle = SOCIAL_HANDLES.find((handle) => handle.name === name);
  return handle ? handle.src : '';
};