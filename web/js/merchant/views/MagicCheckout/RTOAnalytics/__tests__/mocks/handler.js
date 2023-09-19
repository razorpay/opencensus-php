import { rest } from 'msw';

export const costSavedWidgetHandlers = [
  rest.post('*/merchant/api/test/1cc/rto_prediction_service/dashboard', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        status_code: 200,
        data: JSON.parse(`{
          "data": [
            {
              "name": "Cost Saving",
              "aggregation_type": "weekly",
              "updated_at": "1666302630"
            },
          ]
        }`),
      }),
      ctx.delay(50),
    );
  }),
  rest.post('*/merchant/api/test/1cc/rto_prediction_service/dashboard', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        status_code: 200,
        data: JSON.parse(`
          {
            "data": [
              {
                "name": "Cost Saving",
                "aggregation_type": "weekly",
                "updated_at": "1686168666",
                "cost_saving": [
                    {
                        "shipping_charges": 75,
                        "period": "1684108800"
                    },
                    {
                        "shipping_charges": 75,
                        "period": "1685318400"
                    },
                    {
                        "shipping_charges": 75,
                        "period": "1685923200"
                    }
                ]
              }
            ]
          }
        `),
      }),
      ctx.delay(50),
    );
  }),
];

export const rtoRateHandlers = [
  rest.post('*/merchant/api/test/1cc/rto_prediction_service/dashboard', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        status_code: 200,
        data: JSON.parse(`{
          "data": [
            {
              "name": "RTO Rate",
              "aggregation_type": "weekly",
              "updated_at": "1666302630"
            },
          ]
        }`),
      }),
      ctx.delay(50),
    );
  }),
  rest.post('*/merchant/api/test/1cc/rto_prediction_service/dashboard', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        status_code: 200,
        data: JSON.parse(`{
          "data": [
            {
              "name": "RTO Rate",
              "aggregation_type": "weekly",
              "updated_at": "1666302630",
              "rto_rate": [
                {
                "cod_rto_rate": 9,
                "prepaid_rto_rate": 4,
                "total_rto_rate": 5,
                "period": "1666656000"
                },
                {
                  "cod_rto_rate": 7,
                  "prepaid_rto_rate": 7,
                  "total_rto_rate": 6,
                  "period": "1666742400"
                },
                {
                  "cod_rto_rate": 8,
                  "prepaid_rto_rate": 9,
                  "total_rto_rate": 7,
                  "period": "1667088000"
                }
              ]
            }
          ]
        }`),
      }),
      ctx.delay(50),
    );
  }),
];

export const preAndPostMagicRTORateHandlers = [
  rest.post('*/merchant/api/test/1cc/rto_prediction_service/dashboard', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        status_code: 200,
        data: JSON.parse(`{
          "data": [
            {
              "name": "RTO rate",
              "aggregation_type": "monthly",
              "updated_at": "1694071500",
              "premagic_rto_rate": {
                "rto_rate": 27.23,
                "reduction_percentage": -27.23
              }
            }
          ]
        }`),
      }),
      ctx.delay(50),
    );
  }),
  rest.post('*/merchant/api/test/1cc/rto_prediction_service/dashboard', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        status_code: 200,
        data: JSON.parse(`{
          "data": [
            {
              "name": "RTO rate",
              "aggregation_type": "monthly",
              "updated_at": "1694071500",
              "rto_rate": [
                {
                  "prepaid_rto_rate": 67,
                  "total_rto_rate": 27,
                  "cod_rto_rate": 5,
                  "period": 1688169600
                },
                {
                  "prepaid_rto_rate": 67,
                  "total_rto_rate": 37,
                  "cod_rto_rate": 5,
                  "period": 1693526400
                }
              ],
              "premagic_rto_rate": {
                "rto_rate": 27.23,
                "reduction_percentage": 27.23
              }
            }
          ]
        }`),
      }),
      ctx.delay(50),
    );
  }),
];
