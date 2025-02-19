// @ts-nocheck
import { test as base, expect } from '@playwright/test';
import { http } from 'msw';
import type { MockServiceWorker } from 'playwright-msw';
import { createWorkerFixture } from 'playwright-msw';
import handlers from './handlers';
import { pushSRData } from '@dashboard/shared-utils/e2e/utils/common';

const test = base.extend<{
  worker: MockServiceWorker;
  http: typeof http;
}>({
  worker: createWorkerFixture(handlers, {
    graphqlUrl: `*/graph`,
  }),
  http,
  page: async ({ page }, use, testInfo) => {
    await use(page);
    await pushSRData({ testInfo });
  },
});

export { test, expect };
