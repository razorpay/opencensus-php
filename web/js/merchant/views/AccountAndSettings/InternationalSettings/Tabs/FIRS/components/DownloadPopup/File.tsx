import React, { useState } from 'react';
import {
  Box,
  Text,
  Button,
  Badge,
  Tooltip,
  TooltipInteractiveWrapper,
  DownloadIcon,
  InfoIcon,
  ReportsIcon,
} from '@razorpay/blade/components';

import {
  trackDownloadFirsClicked,
  trackDownloadFirsResponse,
} from 'merchant/views/AccountAndSettings/InternationalSettings/analytics';
import {
  FileStatus,
  FileName,
} from 'merchant/views/AccountAndSettings/InternationalSettings/constants';
import useFirsContext from 'merchant/views/AccountAndSettings/InternationalSettings/hooks/useFirsContext';
import { downloadFirsFile } from 'merchant/views/AccountAndSettings/InternationalSettings/services';
import { FilePropsT } from 'merchant/views/AccountAndSettings/InternationalSettings/typings';

const File = ({ file, month, year }: FilePropsT) => {
  const { file_status, document_type, id, order } = file;

  const [isDownloading, setIsDownloading] = useState(false);
  const { setError } = useFirsContext();

  const onDownload = async () => {
    try {
      setIsDownloading(true);
      trackDownloadFirsClicked(month, year, document_type);
      await downloadFirsFile(month, year, file.id);
      trackDownloadFirsResponse(month, year, document_type, 'Success');
    } catch (error) {
      if (typeof error === 'object') setError(error?.toString() ?? '');
      trackDownloadFirsResponse(month, year, document_type, 'Error');
    } finally {
      setIsDownloading(false);
    }
  };

  return (
    <Box
      display="flex"
      flexDirection="row"
      justifyContent={{ base: 'space-between' }}
      marginBottom="spacing.3"
      key={id}
    >
      <Box display="flex" alignItems={{ base: 'center' }}>
        <ReportsIcon color="feedback.icon.neutral.lowContrast" size="medium" />
        <Text marginLeft="spacing.3">
          {FileName[document_type]}
          {order ? ` ${order}` : ''} - {month} {year}
        </Text>
      </Box>
      {file_status === FileStatus.PROCESSING ? (
        <Tooltip
          content="Razorpay statements may take up to 2 hours to get generated"
          placement="left"
        >
          <TooltipInteractiveWrapper>
            <Badge variant="notice" size="large" icon={InfoIcon}>
              Requested
            </Badge>
          </TooltipInteractiveWrapper>
        </Tooltip>
      ) : (
        <Button
          variant="secondary"
          size="small"
          type="button"
          iconPosition="left"
          icon={DownloadIcon}
          onClick={onDownload}
          isLoading={isDownloading}
        >
          Download
        </Button>
      )}
    </Box>
  );
};

export default File;
