import { rest } from 'msw';
import { merchantTnCError, product, productError, paymentPageDetails } from './fixtures';
import { allProducts, store, transformedStore, payments } from './fixtures/storefront';

export const paymentPagesHandlers = [
  rest.get('*/merchant/api/test/merchant/*/tnc', (req, res, ctx) => {
    return res(
      ctx.status(400),
      ctx.json({
        status_code: 400,
        success: false,
        errors: ['The requested URL was not found on the server.', 'Status Code: 400'],
      }),
      ctx.delay(50),
    );
  }),
  // create product
  rest.post('*/merchant/api/*/stores/catalogs', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: product,
      }),
      ctx.delay(50),
    );
  }),
  // edit product
  rest.patch('*/merchant/api/*/stores/catalogs/*', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: product,
      }),
      ctx.delay(50),
    );
  }),
  rest.get('*/merchant/api/*/stores/catalogs', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          items: allProducts,
          page: 1,
          per_page_count: 500,
          total: allProducts.length,
          total_pages: 1,
        },
      }),
      ctx.delay(50),
    );
  }),
  rest.get('*/merchant/api/*/proxy/merchants/supportdetails', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          id: 'HmBFrApNqpExbw',
          type: 'support',
          email: 'abc@abc.com',
          phone: '12232323232',
          policy: null,
          url: 'https://google.com/1232332',
        },
      }),
      ctx.delay(50),
    );
  }),
  // fetch store
  rest.get('*/merchant/api/*/stores/:storeId', (req, res, ctx) => {
    const isTransformHeader = req.headers.get('X-Razorpay-NCA-Transform') === '1';
    const data = isTransformHeader ? transformedStore : store;
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data,
      }),
      ctx.delay(50),
    );
  }),
  rest.get('*/merchant/api/*/stores/*/payments', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          items: payments,
          page: 1,
          per_page_count: 25,
          total: payments.length,
          total_pages: 1,
        },
      }),
      ctx.delay(50),
    );
  }),
  rest.patch('*/merchant/api/*/stores/:storeId/activate', (req, res, ctx) => {
    const isTransformHeader = req.headers.get('X-Razorpay-NCA-Transform') === '1';
    const data = isTransformHeader ? transformedStore : store;
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          ...data,
          status: 'active',
          status_reason: null,
        },
      }),
      ctx.delay(50),
    );
  }),
  rest.patch('*/merchant/api/*/stores/:storeId/deactivate', (req, res, ctx) => {
    const isTransformHeader = req.headers.get('X-Razorpay-NCA-Transform') === '1';
    const data = isTransformHeader ? transformedStore : store;
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          ...data,
          status: 'inactive',
          status_reason: 'deactivated',
        },
      }),
      ctx.delay(50),
    );
  }),
  rest.get('*/merchant/api/*/payment_pages/pl_LpoFCooJAk0a2j/details', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: paymentPageDetails,
      }),
      ctx.delay(50),
    );
  }),
  rest.get('*/merchant/api/*/payment_pages/pl_parsingerrortest/details', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {},
      }),
      ctx.delay(50),
    );
  }),

  rest.get('*/merchant/api/*/payment_pages/pl_apierrortest/details', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 400,
        success: false,
        errors: merchantTnCError,
      }),
      ctx.delay(50),
    );
  }),
];

export const paymentPagesErrorHandlers = {
  merchantTnC: () =>
    rest.get('*/merchant/api/test/merchant/*/tnc', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 400,
          success: false,
          errors: merchantTnCError,
        }),
        ctx.delay(50),
      );
    }),
  storefrontCatalogCreate: () =>
    rest.post('*/merchant/api/*/stores/catalogs', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 400,
          success: false,
          errors: productError,
        }),
        ctx.delay(50),
      );
    }),
  storefrontCatalogEdit: () =>
    rest.patch('*/merchant/api/*/stores/catalogs/*', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 400,
          success: false,
          errors: productError,
        }),
        ctx.delay(50),
      );
    }),
  storefrontCatalogEditStock: () =>
    rest.patch('*/merchant/api/*/stores/catalogs/:catalogId', (req, res, ctx) => {
      const requestBody: any = req.body;
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            ...product,
            id: req.params.catalogId,
            units: requestBody.hasOwnProperty('units') ? requestBody.units : product.units,
            status: requestBody.hasOwnProperty('status') ? requestBody.status : product.status,
          },
        }),
        ctx.delay(50),
      );
    }),
  storefrontAllCatalog: () =>
    rest.get('*/merchant/api/*/stores/catalogs', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 400,
          success: false,
          errors: productError,
        }),
        ctx.delay(50),
      );
    }),
  storefrontAllCatalogEmpty: () =>
    rest.get('*/merchant/api/*/stores/catalogs', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            items: [],
            page: 1,
            per_page_count: 500,
            total: 0,
            total_pages: 1,
          },
        }),
        ctx.delay(50),
      );
    }),
  storefrontFetch: () =>
    rest.get('*/merchant/api/*/stores/:storeId', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 400,
          success: false,
          errors: ['The requested URL was not found on the server.', 'Status Code: 400'],
        }),
        ctx.delay(50),
      );
    }),
  fetchProductsSuccessHandler: (emptyList = false) =>
    rest.get('*/merchant/api/*/stores/catalogs', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            total: emptyList ? 0 : 1,
            total_pages: 1,
            page: 1,
            per_page_count: 25,
            items: emptyList
              ? []
              : [
                  {
                    id: 'ctl_KzEahSp7jvcIZR',
                    merchant_id: 'HdpjbKuhdREqSV',
                    product_name: 'PROD a',
                    description: 'prod a',
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
                ],
          },
        }),
        ctx.delay(50),
      );
    }),
};
