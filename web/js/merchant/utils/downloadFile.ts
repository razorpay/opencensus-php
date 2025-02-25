import { merchantFetch } from './ajax';

/**
 * Downloads a file from UFH using a fileId and an optional accountId.
 * 
 * @param {string} fileId - The ID of the file to download.
 * @param {string | undefined} accountId - The account ID (optional).
 * @returns {Promise<any>} - Returns a promise that resolves with the fetch response.
 * 
 * @example
 * downloadFromUFH('file123', 'account456').then(response => {
 *   if (response.success) {
 *     console.log('File download started');
 *   }
 * });
 */
export const downloadFromUFH = (fileId: string, accountId?: string): Promise<any> =>
  merchantFetch({
    url: `ufh/file/${fileId}/get-signed-url`,
    ...(!!accountId && { accountId }),
  }).then(response => {
    if (response.success) {
      window.location.href = response.data.signed_url;
    }
    return response;
  });
