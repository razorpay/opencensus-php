import { APIResponse } from './common';

export type FileUploadSuccessResponse = {
  file_id: string;
};

export type FileUploadErrorResponse = {
  code: string;
  description: string;
  internal_error_code: string;
};

export type SalesAssitedOnboardingDocs = 'custom_pricing_doc';

export type FileUploadApiResponse = APIResponse<FileUploadSuccessResponse, FileUploadErrorResponse>;

export type FileUploadAPIArgs = {
  file: File;
  name: string;
  userId: string;
};

export interface FileItem {
  fileStoreId: string;
  name: string;
  size: number;
}

export interface FileUploadSignedUrlResponse {
  signed_url: string;
  name: string;
  type: string;
  size: number;
}

export type FileUploadSignedUrlApiResponse = APIResponse<
  FileUploadSignedUrlResponse,
  FileUploadErrorResponse
>;
