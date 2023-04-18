import { rest } from 'msw';
import {
  downtime_mock_response,
  downtime_mock_response_with_no_downtime,
  previous_downtimes_mock,
} from './mockResponses';

type ongoingDowntimesHandlerType = {
  isSuccess?: boolean;
  downtimeExists?: boolean;
};

export const ongoingDowntimesHandler = ({
  isSuccess = true,
  downtimeExists = true,
}: ongoingDowntimesHandlerType) => {
  if (!isSuccess) {
    return rest.get('*/merchant/api/*/payments/downtimes/ongoing', (req, res, ctx) => {
      return res(ctx.status(500), ctx.json({}), ctx.delay(50));
    });
  }
  return rest.get('*/merchant/api/*/payments/downtimes/ongoing', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json(downtimeExists ? downtime_mock_response : downtime_mock_response_with_no_downtime),
      ctx.delay(50),
    );
  });
};

export const resolvedDowntimesHandler = ({ isSuccess = true }: { isSuccess?: boolean }) => {
  if (!isSuccess) {
    return rest.get('*/merchant/api/live/payments/downtimes/resolved', (req, res, ctx) => {
      return res(ctx.status(500), ctx.json({}), ctx.delay(50));
    });
  }
  return rest.get('*/merchant/api/live/payments/downtimes/resolved', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(previous_downtimes_mock), ctx.delay(50));
  });
};
