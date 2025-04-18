import { rest } from 'msw';

interface RequestBody {
  source_id: string;
}

const uploadFileToPresignedUrl = () => {
  return rest.put<RequestBody>('https://mock-s3-url.com/upload', async (req, res, ctx) => {
    const { source_id } = await req.body;

    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          source_id,
        },
      }),
      ctx.delay(50),
    );
  });
};

export { uploadFileToPresignedUrl };
