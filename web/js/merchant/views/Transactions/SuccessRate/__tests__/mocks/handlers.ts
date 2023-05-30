import { rest } from 'msw';
import {
  SrPayload,
  getSrResponse,
  FAILED_SR_ERROR_RESPONSE,
  DOWNTIME_MOCK_RESPONSE,
  MERCHANT_ERROR_RESPONSE_OVERALL_SUCCESS,
} from './fixtures';

type SrApiHandler = {
  isSuccess?: boolean;
  body?: unknown;
};

export const srApiHandler = ({ isSuccess = true }: SrApiHandler): unknown =>
  rest.post('*/merchant/api/*/success-rate/merchant/sr', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json(isSuccess ? getSrResponse(req.body as SrPayload) : FAILED_SR_ERROR_RESPONSE),
      ctx.delay(50),
    );
  });

export const ongoingDowntimesHandler = ({ isSuccess = true }: SrApiHandler): unknown =>
  rest.get('*/merchant/api/*/payments/downtimes/ongoing', (_, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json(
        isSuccess
          ? DOWNTIME_MOCK_RESPONSE
          : {
              ...DOWNTIME_MOCK_RESPONSE,
              status_code: 500,
              success: false,
            },
      ),
      ctx.delay(50),
    );
  });

export const resolvedDowntimesHandler = ({
  isSuccess = true,
}: Omit<SrApiHandler, 'body'>): unknown =>
  rest.get('*/merchant/api/*/payments/downtimes/resolved', (_, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json(
        isSuccess
          ? DOWNTIME_MOCK_RESPONSE
          : {
              ...DOWNTIME_MOCK_RESPONSE,
              status_code: 500,
              success: false,
            },
      ),
      ctx.delay(50),
    );
  });

export const errorApiHandler = ({ isSuccess = true }: SrApiHandler): unknown =>
  rest.post('*/merchant/api/*/success-rate/merchant/error', (_, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json(isSuccess ? MERCHANT_ERROR_RESPONSE_OVERALL_SUCCESS : FAILED_SR_ERROR_RESPONSE),
      ctx.delay(50),
    );
  });
