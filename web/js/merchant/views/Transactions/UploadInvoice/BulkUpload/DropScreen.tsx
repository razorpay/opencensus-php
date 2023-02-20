import React, { useContext } from 'react';
import { modalContext } from './ModalContext';

//component
import Input from 'common/new-ui/Input';

//types
import { ModalContextType, DropScreenProps } from './types';
import { transfromDroppedFiles } from './utils';
import { UPLOAD_CONFIG } from './constants';

//skeleton content
const invoiceContent = () => (
  <ol>
    <li>Each file should be unique</li>
    <li>In case of duplicates, only the first file will be considered</li>
    <li>
      Include the exact <b>Invoice Number</b> in file name which was shared during payment creation,
      else the invoice will be rejected
    </li>
    <li>The number of files should not exceed 500</li>
  </ol>
);

const awbContent = () => (
  <ol>
    <li>Each file should be unique</li>
    <li>In case of duplicates, only the first file will be considered</li>
    <li>The number of files should not exceed 500</li>
    <li>
      The file name of the airway bill should be the following format:{' '}
      <b>AWBNumber_InvoiceNumber</b>
    </li>
    <ol type="a">
      <li>
        <b>AWBNumber</b> is the the AWB Number of the export for the respective payment id and
        invoice number.
      </li>
      <li>
        <b>InvoiceNumber</b> is the Invoice Number of the respective payment id.
      </li>
    </ol>
  </ol>
);

const DropScreen: React.FC<DropScreenProps> = ({ showNotification }) => {
  const { setFiles, setClientErrors, currentTab } = useContext(modalContext) as ModalContextType;

  const onChange = (files, isDropped) => {
    if (files.length > UPLOAD_CONFIG.maxFiles) {
      showNotification({
        type: 'error',
        message: `The number of files should not exceed ${UPLOAD_CONFIG.maxFiles}`,
      });
      return;
    }
    if (isDropped) {
      const { transformedFiles, clientErrors } = transfromDroppedFiles(files);
      setFiles(transformedFiles);
      setClientErrors(clientErrors);
    } else {
      const transformedFiles = Object.keys(files).map((key) => files[key]);
      setFiles(transformedFiles);
    }
  };

  return (
    <div className="drop-screen-container">
      <h2 className="drop-screen--title">Please note the following before proceeding futher:</h2>
      {currentTab === 0 ? invoiceContent() : awbContent()}
      <h3 className="drop-screen--notice">
        Please ensure you are uploading the correct {currentTab === 0 ? 'invoice ' : 'airway bill '}
        for reporting purposes to the regulator in India. The correctness and completeness of the
        invoice copy is responsibility of the seller.
      </h3>
      <Input.File
        maxSize={1048600} // 1MB
        _accept={['jpeg', 'png', 'pdf']}
        multi
        handleMultiUpload={onChange}
      />
    </div>
  );
};

export default DropScreen;
