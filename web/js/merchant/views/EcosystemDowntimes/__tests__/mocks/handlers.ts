import { rest } from 'msw';
import {
  downtime_mock_response,
  downtime_mock_response_with_no_downtime,
  failed_downtime_response,
  failed_sr_mock_response,
  previous_downtimes_mock,
  sr_mock_response,
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
      return res(ctx.status(200), ctx.json(failed_downtime_response), ctx.delay(50));
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
    return rest.get('*/merchant/api/*/payments/downtimes/resolved', (req, res, ctx) => {
      return res(ctx.status(200), ctx.json(failed_downtime_response), ctx.delay(50));
    });
  }
  return rest.get('*/merchant/api/*/payments/downtimes/resolved', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(previous_downtimes_mock), ctx.delay(50));
  });
};

export const successRateHandler = ({
  isSuccess = true,
  isNoPayments = false,
}: {
  isSuccess?: boolean;
  isNoPayments?: boolean;
}) => {
  if (!isSuccess) {
    return rest.post('*/merchant/api/*/success-rate/merchant/sr', (req, res, ctx) => {
      return res(ctx.status(200), ctx.json(failed_sr_mock_response), ctx.delay(50));
    });
  }

  const mock = sr_mock_response;

  if (isNoPayments) {
    mock.data = {
      total: 0,
      sr: 0,
      successful: 0,
    };
  }

  return rest.post('*/merchant/api/*/success-rate/merchant/sr', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(mock), ctx.delay(50));
  });
};
