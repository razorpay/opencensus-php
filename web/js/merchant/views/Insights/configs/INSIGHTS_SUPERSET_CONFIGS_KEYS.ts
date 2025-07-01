import { isProductionEnv } from 'common/utils/rzp-utils';

const isProd = isProductionEnv() || window.APP_ENV === 'canary';

export const INSIGHTS_SUPERSET_CONFIGS_KEYS = {
        'INSIGHTS_SUPERSET_URL': isProd ? 'https://superset-edge.razorpay.com' : 'https://superset.concierge.stage.razorpay.in' ,
        'INSIGHTS_SUCCESS_RATE_SUPERSET_OVERVIEW_ID':isProd ? 'f57f5de7-dca2-47e8-afae-9e7c671ff066': '27f23cb9-e01f-4376-9556-79630ef0d1af',
        'INSIGHTS_SUCCESS_RATE_SUPERSET_UPI_ID':isProd ? 'f3d1df97-7679-46d3-92b2-1d5838963299': '5cab776a-cb63-4e0d-a76d-0e41804646ea',
        'INSIGHTS_SUCCESS_RATE_SUPERSET_CARDS_ID':isProd ? 'ea4b1dd4-c593-49dc-826e-72711e5a6e0e': '4fbb7298-9002-4b4b-a793-2e1c62051341',
        'INSIGHTS_SUCCESS_RATE_SUPERSET_NETBANKING_ID':isProd ? 'c07a87c3-563d-4b51-9433-b29cf933dc88': '13524b78-5b48-40a5-8556-43ec496f6fa3',
        'INSIGHTS_SUCCESS_RATE_SUPERSET_WALLETS_ID':isProd ? 'a07274eb-4f5a-4318-8f9d-5dc822037924': 'b02bb061-eeed-4971-a4af-dae7ee01e697',
        'INSIGHTS_CHECKOUT_SUPERSET_MAGIC_ID':isProd ? '614fe49d-96db-46bf-a361-b37be5b8c218': 'd82a4f51-483c-4dbc-a606-300a8af436b6',
        'INSIGHTS_CHECKOUT_SUPERSET_MAGICX_ID':isProd ? '8018069a-faf3-48c5-810a-052e1f25e4d8': 'dfb1daf8-a6f0-460d-9a2d-fd9c13de55a3',
        'INSIGHTS_CHECKOUT_SUPERSET_EMANDATE_ID':isProd ? '6616dc73-589e-4640-96fe-77ce3fdbba66': '19702464-9a31-4515-a455-67ce490f7d99',
} as const;