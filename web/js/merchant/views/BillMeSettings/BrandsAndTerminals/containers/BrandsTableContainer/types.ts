import { BladeFile } from '@razorpay/blade/components';

import {
  BRAND_OPERATION_TYPE,
  INIT_BRAND_PAYLOAD,
} from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/constants';

export type Brand = {
  id: string;
  name: string;
  description: string | null;
  logo: string | null;
};

export type BrandsResponse = {
  storeBrands: {
    storeBrands: Brand[];
    limit: number;
    offset: number;
    total: number;
  };
};

export type BrandByIdResponse = {
  storeBrandById: Brand;
};

export type BrandSearchColumnType = 'BRAND_NAME';
export type OperationType = keyof typeof BRAND_OPERATION_TYPE;

export type BrandModalInfoType = {
  operationType: OperationType | null;
  selectedBrandId: string | null;
};

export type BrandPayloadType = {
  name: string;
  description: string | null;
  logo: BladeFile | null;
  documentId?: string;
};

export type ModifiedFieldsMapType = { [K in keyof typeof INIT_BRAND_PAYLOAD]?: boolean } & {
  documentId?: boolean;
};
