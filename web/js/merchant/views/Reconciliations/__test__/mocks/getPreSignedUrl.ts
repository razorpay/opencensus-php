import { rest } from 'msw';

interface RequestBody {
  source_id: string;
}

const getPreSignedUrl = () =>
  rest.post<RequestBody>('recon-saas/file_detail/config/get_upload_url', async (req, res, ctx) => {
    const { source_id } = await req.body;
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          source_id,
          upload_url: 'https://mock-s3-url.com/upload',
          upload_path: '/mock-path',
        },
      }),
      ctx.delay(50),
    );
  });

export { getPreSignedUrl };
