import {
  ProductStatusKeys,
  PRODUCT_STATUS,
} from 'merchant/views/PaymentPages/common/Products/utils';
import { IPaymentPagesProduct, PaymentPagesStorefrontType } from './storefront';
import { IBannerImage, ICatalog, ILineItem, IStorefrontResponse } from './types';
import { decodeHTMLEntities } from 'common/utils/rzp-utils';
import { MAX_ENABLED_BANNERS } from 'merchant/views/PaymentPages/PaymentPages/constants';

export const transformCatalog = (catalog: ICatalog): IPaymentPagesProduct => {
  return {
    id: catalog.id,
    product_name: decodeHTMLEntities(catalog.product_name),
    description: decodeHTMLEntities(catalog.description),
    // convert paise to rupees (number to string conversion)
    // this value will be used while editing a price field
    // we will format the number field in readable format, while displaying it
    amount: String(catalog.amount / 100),
    discounted_amount: catalog.discounted_amount ? String(catalog.discounted_amount / 100) : '',
    units: convertUnitsBasedOnStatus(catalog.units, catalog.status),
    images: catalog.images,
    // use first category id for v1 release, modify when we add 1 to many mapping
    category: catalog.categories[0]?.id ? catalog.categories[0].id : null,
    status: catalog.status,
  };
};

export const transformLineItem = (lineItem: ILineItem): IPaymentPagesProduct => {
  const { catalog } = lineItem;

  // currently we aren't transforming anything other key in line_items
  return transformCatalog(catalog);
};

export const transformBannerImage = (banner_images: IBannerImage[] = []) => {
  const enabledCount = banner_images.filter((item) => item.enabled).length;
  return banner_images.map((item) => {
    return {
      ...item,
      isSwitchEnabled: item.enabled ? true : enabledCount === MAX_ENABLED_BANNERS ? false : true,
    };
  });
};

export const transformStorefront = (
  response: IStorefrontResponse,
): PaymentPagesStorefrontType['entity'] => {
  return {
    title: response.title,
    banner_images: transformBannerImage(response.banner_images),
    slug: response.slug || '',
    contactPhone: response.support_contact,
    contactEmail: response.support_email,
    terms: response?.terms || '',
    products: response.line_items
      ? response.line_items.map((line_item) => transformLineItem(line_item))
      : [],
    shortUrl: response.short_url,
    expire_by: response.expire_by,
    settings: response.configs || {},
  };
};

export function convertUnitsBasedOnStatus(units: number, status: ProductStatusKeys): string {
  if (status === PRODUCT_STATUS.OUT_OF_STOCK) {
    return '0';
  }
  if (status === PRODUCT_STATUS.IN_STOCK) {
    return String(units);
  }
  if (status === PRODUCT_STATUS.UNLIMITED) {
    return '';
  }
  // not handling inactive, as it ideally won't come in storefront APIs, can alternatively send empty string here
  return String(units);
}
