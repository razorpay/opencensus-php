import { Box, Spinner, Text, ProgressBar } from '@razorpay/blade/components';
import FileUploaded from 'assets/reconciliations/file-uploaded.svg';
import FileUploading from 'assets/reconciliations/file-uploading.svg';

export const Loader = () => (
  <Box display="flex" justifyContent="center" alignItems="center" height="200px">
    <Spinner size="large" />
  </Box>
);

export const FileUploadStatus = ({ fileData }) =>
  fileData?.isUploading || fileData?.isUploaded ? (
    <>
      <Box
        paddingX="spacing.6"
        paddingY="spacing.4"
        display="flex"
        alignItems="center"
        marginTop="spacing.4"
        elevation="midRaised"
        borderWidth="thick"
        borderColor="surface.border.gray.subtle"
        borderRadius="medium"
      >
        <img src={fileData?.isUploaded ? FileUploaded : FileUploading} alt="File Uploading" />
        <Text marginLeft="spacing.4">{fileData?.fileName || ''}</Text>
        {fileData.error ? <Text>Error Occured</Text> : null}
      </Box>
      {fileData?.isUploading ? <ProgressBar isIndeterminate label="" /> : null}
    </>
  ) : null;
