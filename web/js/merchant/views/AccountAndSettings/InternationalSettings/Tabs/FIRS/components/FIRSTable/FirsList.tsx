import React from 'react';
import {
  Badge,
  Text,
  Link,
  ChevronRightIcon,
  Box,
  Tooltip,
  TooltipInteractiveWrapper,
  InfoIcon,
} from '@razorpay/blade/components';

import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import EmptyListContainer from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/FIRSTable/EmptyListContainer';
import {
  PopupType,
  monthList,
} from 'merchant/views/AccountAndSettings/InternationalSettings/constants';
import useFirsContext from 'merchant/views/AccountAndSettings/InternationalSettings/hooks/useFirsContext';
import {
  getFirsDetails,
  isRecentMonth,
} from 'merchant/views/AccountAndSettings/InternationalSettings/utils';

const FirsList = (): React.ReactElement => {
  const { listYear, firsData, isLoading, popupData, isRequestFirsEnabled, setPopupData } =
    useFirsContext();
  const { isOpen: isPopupOpen } = popupData;
  const data = firsData[listYear] ?? {};

  const onClick = (month: string, type: string) => {
    setPopupData({
      type,
      month,
      year: listYear,
      isOpen: true,
    });
  };

  const getCallToAction = (month: string): JSX.Element => {
    const firsFiles = data[month] ?? [];
    const { fileCount, hasTransactions, isFirsRequested } = getFirsDetails(firsFiles);

    // Download CTA for files
    if (fileCount > 0) {
      return (
        <Link
          icon={ChevronRightIcon}
          iconPosition="right"
          variant="button"
          onClick={() => onClick(month, PopupType.DOWNLOAD_FIRS)}
        >
          Download FIRS files ({fileCount})
        </Link>
      );
    }

    // Message when no transactions exist
    if (!hasTransactions) {
      return <Text color="surface.text.gray.muted">No international payments</Text>;
    }

    //Status when FIRS is in requested state
    if (isFirsRequested) {
      return (
        <Tooltip
          content="Razorpay statements may take up to 2 hours to get generated"
          onOpenChange={function noRefCheck() {}}
          placement="top"
        >
          <TooltipInteractiveWrapper>
            <Badge size="large" icon={InfoIcon} color="notice">
              FIRS Requested
            </Badge>
          </TooltipInteractiveWrapper>
        </Tooltip>
      );
    }

    // Message when no FIRS files are generated yet
    return (
      <Box display="flex" flexDirection="row">
        <Text marginRight="spacing.2" color="surface.text.gray.muted">
          No FIRS generated yet
        </Text>
        {isRequestFirsEnabled && (
          <Link
            iconPosition="right"
            variant="button"
            onClick={() => onClick(month, PopupType.NO_FIRS)}
          >
            Why?
          </Link>
        )}
      </Box>
    );
  };

  return (
    <div className="table-responsive">
      <table className="table">
        <thead>
          <tr>
            <th style={{ width: '120px' }}> </th>
            <th style={{ width: '70px' }}>Year</th>
            <th>Month</th>
            <th>Generated FIRS</th>
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading && !isPopupOpen}
          colSpan={4}
          rows={Object.keys(data)}
          emptyTableMsg={<EmptyListContainer />}
        >
          {monthList
            .slice()
            .reverse()
            .map((month) => {
              if (!data[month]) return null;
              return (
                <EntityItemRow key={month}>
                  <td>
                    {isRecentMonth(month, listYear) && (
                      <Badge
                        emphasis="intense"
                        size="medium"
                        marginLeft="spacing.5"
                        color="positive"
                      >
                        New
                      </Badge>
                    )}
                  </td>
                  <td>
                    <Text>{listYear}</Text>
                  </td>
                  <td>
                    <Text>{month}</Text>
                  </td>
                  <td>{getCallToAction(month)}</td>
                </EntityItemRow>
              );
            })}
        </TableBody>
      </table>
    </div>
  );
};

export default FirsList;
