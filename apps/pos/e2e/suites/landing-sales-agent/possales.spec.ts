// import { routes, getStorageStatePath } from '@dashboard/shared-utils/e2e/constants/paths';
// import { navigateTo } from '@dashboard/shared-utils/e2e/utils/common';
// import { test, expect } from '../../utils/test';
// import { waitForSalesAssistedScreenToLoad } from '../../utils';
// import { BASE_PATH } from '../../constants';
// import { queryMocks } from './mocks/handlers';
// import { salesOnboardedMerchantsMock } from './mocks/fixtures';

// NOTE: there is runtime error in utils/test when looking for 'msw' package, so commenting out the test

// test.describe.parallel('POS activation status @flow=pos-sales-assisted @project=payments', () => {
//   test.use({
//     storageState: getStorageStatePath(BASE_PATH).POS_SALES_AGENT,
//   });
//   test('should render sales dashboard view if logged in as sales agent @flow=pos-sales-assisted', async ({
//     page,
//     worker,
//   }) => {
//     await worker.use(queryMocks.SalesOnboardedMerchants);

//     await navigateTo(page, routes.DASHBOARD);
//     await waitForSalesAssistedScreenToLoad({ page });
//     await expect(page.getByText('POS Sales Dashboard')).toBeVisible();
//     await page.waitForSelector('text=Merchant Details');

//     const merchants = salesOnboardedMerchantsMock.merchants;

//     merchants.forEach(async (merchant) => {
//       await expect(page.getByText(merchant.merchantId)).toBeVisible();
//     });
//   });
// });
