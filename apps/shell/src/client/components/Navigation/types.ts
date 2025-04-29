import { PRODUCT_ALIAS_MAP } from './constants';

export type ProductAlias = (typeof PRODUCT_ALIAS_MAP)[keyof typeof PRODUCT_ALIAS_MAP];
