import { FileItem } from '../../types/fileUpload';

export const formatBytes = (bytes) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes as number) / Math.log(k));
  return `${parseFloat((bytes / k ** i).toFixed(2))} ${sizes[i]}`;
};

export const processFilesForModularSave = (files: FileItem[]) => {
  return files.map((file) => ({
    file_store_id: file.fileStoreId,
    name: file.name,
    size: file.size,
  }));
};
