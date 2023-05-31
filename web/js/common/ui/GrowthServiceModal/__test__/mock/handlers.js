import { rest } from 'msw';
import { templateId } from './modalData';

const fetchGSModalHandler = ({
  responseStatus = 200,
  apiLevelStatus = 200,
  gsLevelStatus = 200,
  delay = 0,
  modalData,
  shouldReturnEmptyData = false,
} = {}) =>
  rest.get(`*/template/${templateId}`, (req, res, ctx) => {
    const response = shouldReturnEmptyData
      ? {}
      : {
          template: {
            id: templateId,
            channel_id: 'HTdu8cC7FJEIHC',
            asset: 'MODAL',
            name: 'modal',
            description: 'modal',
            status: 'DRAFT',
            created_by: 'shivam.bhalla@razorpay.com',
            created_at: '2023-01-30T10:29:18Z',
            updated_at: '2023-02-09T09:59:19Z',
            data: {
              ...modalData,
            },
          },
        };

    return res(
      ctx.status(responseStatus),
      ctx.json({
        status_code: apiLevelStatus,
        success: true,
        data: {
          status_code: gsLevelStatus,
          response,
        },
      }),
      ctx.delay(delay),
    );
  });

export { fetchGSModalHandler };
