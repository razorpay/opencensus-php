import React, { useState } from 'react';
import { Box, Text, Link, Alert, ChevronDownIcon, ChevronUpIcon } from '@razorpay/blade/components';

import { useSplitzService } from 'common/splitz';
import { pluralize } from 'common/utils/rzp-utils';
import File from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/DownloadPopup/File';
import { trackRequestFirsButtonClick } from 'merchant/views/AccountAndSettings/InternationalSettings/analytics';
import { PopupType } from 'merchant/views/AccountAndSettings/InternationalSettings/constants';
import useFirsContext from 'merchant/views/AccountAndSettings/InternationalSettings/hooks/useFirsContext';
import { PopupDataType } from 'merchant/views/AccountAndSettings/InternationalSettings/typings';
import { getCategorizedFirsFiles } from 'merchant/views/AccountAndSettings/InternationalSettings/utils';
import { isExperimentEnabled } from 'common/splitz/utils';

const FirsFiles = (): React.ReactElement => {
  const [shouldShowAll, setShouldShowAll] = useState(false);

  const { popupData, firsData, isRequestFirsEnabled, setPopupData } = useFirsContext();
  const {
    abExperiments: { firs_messaging },
  } = useSplitzService();
  const customMessage = isExperimentEnabled(firs_messaging)
    ? firs_messaging?.variables?.message
    : null;

  const { month, year } = popupData;
  const { bankFirs, internalFirs, shouldShowRequestButton, isFirsRequestFailed } =
    getCategorizedFirsFiles(firsData[year]?.[month] ?? []);

  const onRequestFirs = () => {
    trackRequestFirsButtonClick(month, year);
    setPopupData((prev: PopupDataType) => ({
      ...prev,
      type: PopupType.INTERNAL_FIRS,
    }));
  };

  const toggleShowAll = () => {
    setShouldShowAll((prev) => !prev);
  };

  return (
    <Box display="flex" flex="1" flexDirection="column" marginTop="spacing.7">
      <Box marginBottom="spacing.7">
        <Box
          display="flex"
          flexDirection="row"
          justifyContent={{ base: 'space-between' }}
          borderWidth="none"
          borderBottomWidth="thin"
          borderBottomColor="surface.border.gray.muted"
          paddingBottom="spacing.3"
          marginBottom="spacing.4"
        >
          <Text color="surface.text.gray.muted">
            Bank FIRS ({bankFirs.length || 'No'} {pluralize('file', bankFirs.length)} available)
          </Text>
        </Box>
        <Box>
          {bankFirs.map((file, index) => {
            if (!shouldShowAll && index > 2) return null;
            return <File key={file.id} file={file} month={month} year={year} />;
          })}
          {bankFirs.length === 0 && (
            <Box display="flex" flexDirection="column">
              <Text size="medium" weight="regular" marginBottom="spacing.5">
                Bank FIRS is/are usually available for download after the 18th of the next month.
              </Text>
              {customMessage && (
                <Alert
                  marginTop="spacing.5"
                  color="notice"
                  isDismissible={false}
                  isFullWidth
                  description={customMessage as string}
                />
              )}
            </Box>
          )}
        </Box>
        {bankFirs.length > 3 && (
          <Box display="flex" justifyContent="center">
            <Link
              variant="button"
              onClick={toggleShowAll}
              marginRight="spacing.2"
              icon={shouldShowAll ? ChevronUpIcon : ChevronDownIcon}
              iconPosition="right"
            >
              Show {shouldShowAll ? 'less' : 'all'}
            </Link>
          </Box>
        )}
      </Box>
      {internalFirs.length > 0 && (
        <Box>
          <Box
            borderWidth="none"
            borderBottomWidth="thin"
            borderBottomColor="surface.border.gray.muted"
            paddingBottom="spacing.3"
            marginBottom="spacing.4"
          >
            <Text color="surface.text.gray.muted">
              Razorpay Statements ({internalFirs.length || 'No'}{' '}
              {pluralize('file', internalFirs.length)} available)
            </Text>
          </Box>
          <Box>
            {internalFirs.map((file) => (
              <File key={file.id} file={file} month={month} year={year} />
            ))}
          </Box>
        </Box>
      )}
      {shouldShowRequestButton && isRequestFirsEnabled && (
        <Box marginTop="spacing.7" display="flex" alignItems="flex-end" flex="1">
          <Text size="small">
            {isFirsRequestFailed
              ? `Your FIRS request for ${month} ${year} failed due to technical reasons.`
              : "Don't see bank FIRS above or find any international transactions missing? "}
            <Link variant="button" size="small" onClick={onRequestFirs}>
              Request for Razorpay statement
            </Link>
          </Text>
        </Box>
      )}
    </Box>
  );
};

export default FirsFiles;
