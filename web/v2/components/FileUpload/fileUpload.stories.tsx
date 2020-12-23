import React, { useState } from 'react';
import FileUpload, { FileUploadPropsT } from './fileUpload';
export default {
  title: 'Onboarding/file upload',
  Component: FileUpload,
};
export const FileUploadComponent: React.FC<FileUploadPropsT> = () => {
  const [progress, setProgress] = useState(0);
  const [files, setFiles] = React.useState<Array<[]>>([]);
  const [name, setName] = useState('');
  const onFileUpload = (e) => {
    setFiles([...files, e.currentTarget.files[0]]);
    setTimeout(() => {
      setProgress(100);
      setName('File Uploaded');
    }, 1000);
  };
  const removeFile = (fileName) => {
    if (files.length) {
      const filterFiles = files.filter((file: any) => file.name !== fileName);
      setFiles(filterFiles);
      setName('');
      setProgress(0);
    }
  };
  return (
    <FileUpload
      fileNameProp={name}
      onFileUpload={onFileUpload}
      onRemove={removeFile}
      progress={progress}
      accept={['pdf', 'image']}
      name=""
      error=""
    />
  );
};
