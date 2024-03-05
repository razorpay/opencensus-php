import { Config } from './types';
import noSearchResult from 'apps/self-serve/src/assets/no-search-result.svg';
import { Page } from 'apps/self-serve/src/App/Transactions/v2/common/types';

const subtitle = 'Search using different keywords or time duration';

const { PAYMENTS, FAILED_PAYMENTS, REFUNDS } = Page;

export const config = (page: Page): Config => {
  switch (page) {
    case FAILED_PAYMENTS:
      return {
        image: {
          src: noSearchResult,
          alt: 'no failed payments',
        },
        title: 'No failed payment in selected duration',
        subtitle,
      };
    case REFUNDS:
      return {
        image: {
          src: noSearchResult,
          alt: 'no refunds',
        },
        title: 'No refund in selected duration',
        subtitle,
      };
    case PAYMENTS:
    default:
      return {
        image: {
          src: noSearchResult,
          alt: 'no payments',
        },
        title: 'No payment in selected duration',
        subtitle,
      };
  }
};
