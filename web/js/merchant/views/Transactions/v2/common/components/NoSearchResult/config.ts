import noSearchResult from 'assets/transactions/no-search-result.svg';
import { Config } from './types';
import { Page } from 'merchant/views/Transactions/v2/common/types';

const subtitle = 'Search using different keywords or time duration';

const { PAYMENTS, FAILED_PAYMENTS, REFUNDS } = Page;

export const config = (page: Page): Config => {
  switch (page) {
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
  }
};
