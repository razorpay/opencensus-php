import React from 'react';
import ModalProvider from './ModalContext';

//component
import ModalContainer from './ModalContainer';

//types
import { BulkUploadModalProps } from './types';

//styles
import './BulkUpload.styl';

const BulkUploadModal: React.FC<BulkUploadModalProps> = (props) => {
  return (
    <ModalProvider>
      <ModalContainer {...props} />
    </ModalProvider>
  );
};

export default BulkUploadModal;
