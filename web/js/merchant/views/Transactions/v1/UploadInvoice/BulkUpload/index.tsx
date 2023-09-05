import React from 'react';
import ModalProvider from './ModalContext';

//component
import ModalContainer from './ModalContainer';

//types
import { BulkUploadModalProps } from './types';

//styles
import './BulkUpload.styl';

const BulkUploadModal: React.FC<BulkUploadModalProps> = ({ refreshList }) => {
  return (
    <ModalProvider>
      <ModalContainer refreshList={refreshList} />
    </ModalProvider>
  );
};

export default BulkUploadModal;
