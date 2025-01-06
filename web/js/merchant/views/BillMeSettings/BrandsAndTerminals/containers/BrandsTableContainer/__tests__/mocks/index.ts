export const BRAND_BY_ID_RESPONSE = {
  id: '123',
  name: 'Test Brand Name',
  description: 'Test Brand Description',
  logo: 'test_image_url',
};

export const BRANDS_RESPONSE = {
  brands: [BRAND_BY_ID_RESPONSE],
  total: 10,
  offset: 0,
  limit: 10,
};

export const BRANDS_FILTER_PAYLOAD = {
  limit: 25,
  offset: 0,
  searchTerm: '',
};
