import { FileUploadApiResponse, FileUploadSignedUrlApiResponse } from 'apps/pos/src/app/types/fileUpload';
import { salesFetch } from '.';

interface UploadFileToUFHProps {
  file: File;
  name: string;
  userId: string;
  merchantId: string;
}

export const uploadFileToUFH = ({
  name,
  file,
  userId,
  merchantId,
}: UploadFileToUFHProps): Promise<FileUploadApiResponse> => {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('document_type', name);
  formData.append('upload_only', '1');
  formData.append('merchant_id', merchantId);

  const CUSTOM_HEADERS = {
    'X-Dashboard-User-Id': userId,
  };

  return salesFetch({
    url: 'merchant/documents/upload',
    method: 'POST',
    data: formData,
    mode: 'live',
    headers: CUSTOM_HEADERS,
  });
};

export const getFile = (fileId: string): Promise<FileUploadSignedUrlApiResponse> => {
  return salesFetch({
    url: `merchant/document/url/${fileId}`,
    method: 'GET',
    mode: 'live',
  });
};
