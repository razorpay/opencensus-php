import { merchantFetch } from './ajax';

export const downloadFromUFH = (fileId, accountId) =>
  merchantFetch({
    url: `ufh/file/${fileId}/get-signed-url`,
    ...(!!accountId && { accountId }),
  }).then(response => {
    if (response.success) {
      window.location = response.data.signed_url;
    }
    return response;
  });
