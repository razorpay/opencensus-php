import { FileUploadApiResponse, FileUploadSignedUrlApiResponse } from '../types/fileUpload';
import { salesFetch } from '.';

interface UploadFileToUFHProps {
  file: File;
  name: string;
  userId: string;
}

export const uploadFileToUFH = ({
  name,
  file,
  userId,
}: UploadFileToUFHProps): Promise<FileUploadApiResponse> => {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('type', 'sales_assisted_onboarding_doc');
  formData.append('name', name);

  const CUSTOM_HEADERS = {
    'X-Dashboard-User-Id': userId,
  };

  return salesFetch({
    url: 'ufh/files/upload',
    method: 'POST',
    data: formData,
    mode: 'live',
    headers: CUSTOM_HEADERS,
  });
};

export const getFile = (fileId: string): Promise<FileUploadSignedUrlApiResponse> => {
  const fileIdWithPrefix = `file_${fileId}`;
  return salesFetch({
    url: `ufh/file/${fileIdWithPrefix}/get-signed-url`,
    method: 'GET',
    mode: 'live',
  });
};
