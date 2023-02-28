import { rest } from 'msw';

export const fetchScheduleHandler = () =>
  rest.get('*/merchant/api/:mode/schedule_tasks/settlement', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: [
          {
            method: null,
            type: 'settlement',
            name: 'its_new',
            period: 'daily',
            interval: 1,
            anchor: null,
            hour: [13],
            delay: 2,
            international: 0,
            is_early_settlement_schedule: false,
          },
          {
            method: null,
            type: 'settlement',
            name: 'Basic T7',
            period: 'daily',
            interval: 1,
            anchor: null,
            hour: [13],
            delay: 7,
            international: 1,
            is_early_settlement_schedule: false,
          },
        ],
      }),
      ctx.delay(50),
    );
  });

export const updateInternationalStatusHandler = (response) => {
  return rest.patch('*/merchant/api/:mode/merchant/international', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json(
        response
          ? response
          : {
              status_code: 200,
              success: true,
              data: {
                international: !!req.body.international,
              },
            },
      ),
      ctx.delay(50),
    );
  });
};
