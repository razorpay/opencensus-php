import { PlayCircleIcon, Text } from '@razorpay/blade/components';
import FileUpload from 'merchant/components/File/Upload';
import { MAX_FILE_SIZE_LIMIT } from 'merchant/views/Account/constants';
import {
  FileUploadSectionInterface,
  FILE_CHANGE_ACTION,
  LOADING_STATE,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import React from 'react';
import { trackBankAccountUpdateEvent } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/utils/track';
import { FORM_DATA } from './data';
import {
  DetailList,
  DetailListItem,
  Dot,
  FileDescription,
  StyledFileUpload,
  StyledFileUploadSection,
  StyledWatchVideo,
} from './styled';

const handleWatchVideo = () => {
  trackBankAccountUpdateEvent({
    objectName: 'Sample Video CTA',
    actionName: 'Clicked',
  });
};

const FileUploadSection = ({
  activeTab,
  extras,
  onFileLimitFailure,
  handleFileChange,
  files,
}: FileUploadSectionInterface): JSX.Element => {
  const { description, document } = FORM_DATA[activeTab];
  const { acceptFiles } = extras;

  return (
    <StyledFileUploadSection>
      <FileDescription>
        <Text color="surface.text.gray.subtle">{description.title}</Text>
        {description.details && (
          <>
            <Text color="surface.text.gray.subtle">{description.details.title}</Text>
            <DetailList>
              {description.details.items.map((each, index) => (
                <DetailListItem key={`details-${index}`}>
                  <Dot />
                  <Text color="surface.text.gray.subtle">{each}</Text>
                </DetailListItem>
              ))}
            </DetailList>
          </>
        )}
        {description?.video && (
          <StyledWatchVideo
            target="_blank"
            rel="noopener noreferrer"
            href={description?.video?.link}
            onClick={handleWatchVideo}
          >
            <PlayCircleIcon color="interactive.icon.gray.normal" size="medium" />
            <Text weight="semibold" size="small">
              Watch sample video
            </Text>
          </StyledWatchVideo>
        )}
      </FileDescription>
      <StyledFileUpload>
        <FileUpload
          files={files}
          name={`bank-proof-${activeTab}`}
          maxSize={MAX_FILE_SIZE_LIMIT}
          hideLoader
          uploadSubtitle={document.subtitle}
          showOnlyFileSize
          hideMaxSize
          showFileSize={false}
          accept={acceptFiles}
          showAcceptInfo={false}
          onBiggerFileSize={onFileLimitFailure}
          onFileChange={(file) => {
            trackBankAccountUpdateEvent({
              objectName: 'Click to Upload',
              actionName: 'Clicked',
              properties: {
                proof_type:
                  activeTab === LOADING_STATE.UPLOAD_VERIFICATION_LETTER_DETAIL
                    ? 'bank letter'
                    : 'video',
              },
            });
            handleFileChange({ id: activeTab, type: FILE_CHANGE_ACTION.ADD, file });
          }}
          onCloseClick={() => handleFileChange({ id: activeTab, type: FILE_CHANGE_ACTION.REMOVE })}
        />
      </StyledFileUpload>
    </StyledFileUploadSection>
  );
};

export default FileUploadSection;
