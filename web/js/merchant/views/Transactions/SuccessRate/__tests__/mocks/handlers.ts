import { rest } from 'msw';
import {
  SrPayload,
  getSrResponse,
  FAILED_SR_ERROR_RESPONSE,
  DOWNTIME_MOCK_RESPONSE,
  getErrorResponse,
  FailedResponse,
  SuccessSrResponse,
  DowntimeResponse,
  ErrorResponse,
} from './fixtures';

type SrApiHandler = {
  isSuccess?: boolean;
  body?: unknown;
};

export const srApiHandler = ({ isSuccess = true }: SrApiHandler) => {
  if (!isSuccess) {
    return rest.post<SrPayload, FailedResponse>(
      '*/merchant/api/*/success-rate/merchant/sr',
      (_, res, ctx) => {
        return res(ctx.status(500), ctx.json(FAILED_SR_ERROR_RESPONSE), ctx.delay(50));
      },
    );
  }
  return rest.post<SrPayload, SuccessSrResponse>(
    '*/merchant/api/*/success-rate/merchant/sr',
    (req, res, ctx) => {
      return res(ctx.status(200), ctx.json(getSrResponse(req.body as SrPayload)), ctx.delay(50));
    },
  );
};

export const ongoingDowntimesHandler = ({ isSuccess = true }: SrApiHandler) => {
  if (!isSuccess) {
    return rest.get<null, DowntimeResponse>(
      '*/merchant/api/*/payments/downtimes/ongoing',
      (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            ...DOWNTIME_MOCK_RESPONSE,
            status_code: 500,
            success: false,
          } as DowntimeResponse),
          ctx.delay(50),
        );
      },
    );
  }
  return rest.get<null, DowntimeResponse>(
    '*/merchant/api/*/payments/downtimes/ongoing',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json(DOWNTIME_MOCK_RESPONSE as DowntimeResponse),
        ctx.delay(50),
      );
    },
  );
};

export const resolvedDowntimesHandler = ({ isSuccess = true }: { isSuccess?: boolean }) => {
  if (!isSuccess) {
    return rest.get<null, DowntimeResponse>(
      '*/merchant/api/*/payments/downtimes/resolved',
      (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            ...DOWNTIME_MOCK_RESPONSE,
            status_code: 500,
            success: false,
          } as DowntimeResponse),
          ctx.delay(50),
        );
      },
    );
  }
  return rest.get<null, DowntimeResponse>(
    '*/merchant/api/*/payments/downtimes/resolved',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json(DOWNTIME_MOCK_RESPONSE as DowntimeResponse),
        ctx.delay(50),
      );
    },
  );
};

export const errorApiHandler = ({ isSuccess = true }: SrApiHandler) => {
  if (!isSuccess) {
    return rest.post<SrPayload, FailedResponse>(
      '*/merchant/api/*/success-rate/merchant/errror',
      (_, res, ctx) => {
        return res(ctx.status(200), ctx.json(FAILED_SR_ERROR_RESPONSE), ctx.delay(50));
      },
    );
  }
  return rest.post<SrPayload, ErrorResponse>(
    '*/merchant/api/*/success-rate/merchant/error',
    (req, res, ctx) => {
      return res(ctx.status(200), ctx.json(getErrorResponse(req.body as SrPayload)), ctx.delay(50));
    },
  );
};
