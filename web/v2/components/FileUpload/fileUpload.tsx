import React, { useEffect, useState } from 'react';
import Button from '@razorpay/blade/src/atoms/Button';
import Text from '@razorpay/blade/src/atoms/Text';
import Size from '@razorpay/blade/src/atoms/Size';
import Space from '@razorpay/blade/src/atoms/Space';
import View from '@razorpay/blade/src/atoms/View';
import Flex from '@razorpay/blade/src/atoms/Flex';
import ProgressBarContinuous from '../ProgressBar/ProgressBarContinuous';
import UploadIcon from './FileUploadIcon.svg';
import CheckedIcon from './CheckedIcon.svg';
import { DashedButton, UploadedBox, FileNameContainer } from './Styled';
export interface FileUploadPropsT {
  fileNameProp: string;
  onFileUpload: (e: any, name: string) => void;
  onRemove: (fileName: any) => void;
  progress: number;
  accept: Array<string>;
  name: string;
  error: string;
}
const fileTypesMap = {
  csv: 'text/csv',
  xlsx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', //new excel format
  pdf: 'application/pdf',
  xls: 'application/vnd.ms-excel', //Old microsoft excel sheets.
  image: 'image/*',
  jpg: 'image/jpeg',
  png: 'image/png',
  xml: 'text/xml',
};
const Uploaded = ({ fileName, progress, onCancel }) => {
  return (
    <Size height={6}>
      <Space padding={[1, 1.5]}>
        <UploadedBox>
          <Flex justifyContent="space-between">
            <View>
              <Flex>
                <FileNameContainer>
                  <Space margin={[0, 1, 0, 0]}>
                    <View>
                      <img src={CheckedIcon} style={{ display: 'inline' }} />
                    </View>
                  </Space>
                  <Size height={2}>
                    <Text size="small" color="shade.980">
                      {fileName}
                    </Text>
                  </Size>
                </FileNameContainer>
              </Flex>
              <Space margin={[0, 0, 0, 0.625]}>
                <View>
                  <Button
                    variant="tertiary"
                    size="xsmall"
                    variantColor="shade"
                    icon="close"
                    onClick={onCancel}
                  />
                </View>
              </Space>
            </View>
          </Flex>
          <ProgressBarContinuous percentDone={progress} />
        </UploadedBox>
      </Space>
    </Size>
  );
};

const UploadButton = ({ onChange, accept, name }) => {
  return (
    <Size height={6}>
      <DashedButton data-testid="upload-button">
        <img src={UploadIcon} />
        <input
          data-testid="upload-input"
          name={name}
          type="file"
          onChange={onChange}
          accept={accept && accept.map((fileType) => fileTypesMap[fileType])}
          disabled={!name}
        />
      </DashedButton>
    </Size>
  );
};
const FileUpload: React.FC<FileUploadPropsT> = ({
  onFileUpload,
  onRemove,
  progress,
  accept,
  fileNameProp = '',
  name = '',
  error = '',
}) => {
  const [fileName, setFileName] = useState('');
  const [_progress, setProgress] = useState(progress);

  useEffect(() => {
    if (fileNameProp) {
      setTimeout(() => {
        setFileName('File Uploaded');
        setProgress(100);
      }, 500);
    } else if (!fileNameProp) {
      setFileName('');
      setProgress(0);
    }
  }, [fileNameProp]);

  useEffect(() => {
    if (error && progress > 100) {
      setProgress(100);
    }
  }, [error]);

  const onChange = (e) => {
    if (!name) return;
    setFileName(e.currentTarget.files[0].name);
    if (progress > 100) setTimeout(() => setProgress(100), 500);
    onFileUpload(e, name);
  };

  const onCancel = () => {
    if (error) {
      setFileName('');
      setProgress(0);
    }
    onRemove(name);
  };

  return fileName ? (
    <Uploaded fileName={fileName} progress={_progress} onCancel={onCancel} />
  ) : (
    <UploadButton onChange={onChange} accept={accept} name={name} />
  );
};

export default FileUpload;
