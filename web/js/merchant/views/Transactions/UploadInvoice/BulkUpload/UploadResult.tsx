import React, { useContext, useEffect, useState, useRef } from 'react';
import { modalContext } from './ModalContext';

//components
import UploadTab from './UploadTab';

//helpers
import { BatchError, ModalContextType, UploadResultProps } from './types';
import { getProgressMessage, getProgressPercentage, uploadFiles } from './utils';

const UploadResult: React.FC<UploadResultProps> = ({ refreshList }) => {
  const { files, clientErrors, isUploading, setIsUploading } = useContext(
    modalContext,
  ) as ModalContextType;

  const mounted = useRef(true);

  const [fileIndex, setFileIndex] = useState(0);
  const [failedUploads, setFailedUploads] = useState<Array<BatchError>>([]);

  const startUpload = async () => {
    setIsUploading(true);
    const batchFails: Array<BatchError> = await uploadFiles(files, setFileIndex, mounted);
    if (batchFails.length < files.length) refreshList();
    setFailedUploads(batchFails);
    setIsUploading(false);
  };

  useEffect(() => {
    if (files.length > 0) startUpload();
    return () => {
      mounted.current = false;
    };
  }, []); // eslint-disable-line

  return (
    <div className="upload-result-container">
      <h2 className="upload-result--title">Upload details</h2>
      <UploadTab
        tabDescription="Total Invoices"
        invoiceCount={files.length + clientErrors.length}
      />
      <UploadTab
        tabDescription="Successful Uploads"
        invoiceCount={files.length - failedUploads.length}
        tabType="Success"
        progressPercent={getProgressPercentage(fileIndex, files, clientErrors)}
        showLoader={isUploading}
        progressMessage={getProgressMessage(fileIndex, files, clientErrors)}
      />
      <UploadTab
        tabDescription="Errors"
        invoiceCount={failedUploads.length + clientErrors.length}
        tabType="Error"
        showLoader={isUploading}
        errors={[...clientErrors, ...failedUploads]}
      />
    </div>
  );
};

export default UploadResult;
