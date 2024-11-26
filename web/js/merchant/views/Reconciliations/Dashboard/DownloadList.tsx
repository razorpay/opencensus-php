import React, { useEffect, useState } from 'react';
import {
  Box,
  DownloadIcon,
  Heading,
  Table,
  TableBody,
  TableHeader,
  TableHeaderCell,
  TableHeaderRow,
  TableRow,
  TableCell,
  Text,
  Link,
  Badge,
  Button,
  ArrowLeftIcon,
  ArrowRightIcon,
} from '@razorpay/blade/components';
import { useQuery, useMutation } from '@tanstack/react-query';
import {
  FetchDownloadListResponse,
  DownloadFileResponse,
} from 'merchant/views/Reconciliations/Dashboard/types';
import moment from 'moment';
import { connect } from 'react-redux';
import { compose } from 'redux';

import ErrorBoundary, { Teams } from 'common/new-ui/ErrorBoundary';
import { fetchDownloadList, downloadReportFile } from 'merchant/views/Reconciliations/api';
import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';
import { showNotification } from 'merchant_common/reducers/notifications';

const statusBadgeColor: Record<string, 'positive' | 'notice' | 'negative'> = {
  Ready: 'positive',
  'In Progress': 'notice',
  'Not Started': 'negative',
};

const triggerTypeBadgeColor: Record<string, 'positive' | 'information'> = {
  auto: 'positive',
  manual: 'information',
};

const downloadTableData = [
  { label: 'Config Id', propertyName: 'id', textAlign: 'left' },
  { label: 'Config Name', propertyName: 'config_name', textAlign: 'left' },
  { label: 'File Name', propertyName: 'file_name', textAlign: 'left' },
  { label: 'Format', propertyName: 'format', textAlign: 'left' },
  { label: 'Email', propertyName: 'emails', textAlign: 'left' },
  { label: 'Type', propertyName: 'trigger_type', textAlign: 'center' },
  { label: 'Status', propertyName: 'status', textAlign: 'center' },
  { label: 'Created At', propertyName: 'created_at', textAlign: 'left' },
  { label: 'Updated At', propertyName: 'updated_at', textAlign: 'left' },
  { label: 'Download', propertyName: 'download', textAlign: 'right' },
];

const DownloadList: React.FC = () => {
  const [currentPage, setCurrentPage] = useState<number>(1);
  const [paginationPageId, setPaginationPageId] = useState<{
    firstId: string | null;
    lastId: string | null;
  }>({
    firstId: null,
    lastId: null,
  });

  const {
    data: downloadListData,
    isLoading,
    isError,
  } = useQuery<FetchDownloadListResponse>({
    queryKey: ['downloadList', currentPage],
    queryFn: () =>
      fetchDownloadList({
        currentPage,
        first_id: paginationPageId.firstId,
        last_id: paginationPageId.lastId,
      }),
    enabled: Boolean(
      currentPage === 1 ||
        (currentPage > 1 && (paginationPageId.firstId || paginationPageId.lastId)),
    ),
  });

  const {
    mutate: downloadFileMutation,
    data: downloadFileData,
    isError: isErrorDownloadFile,
    isSuccess: isSuccessDownloadFile,
  } = useMutation<DownloadFileResponse, Error, { reportId: string }>({
    mutationFn: ({ reportId }) => downloadReportFile({ reportId }),
  });

  const handlePagination = (type) => {
    if (type === 'prev' && currentPage > 1) {
      setCurrentPage((prevPage) => prevPage - 1);
      if (downloadListData?.data?.first_id) {
        setPaginationPageId({
          firstId: downloadListData.data.first_id,
          lastId: null,
        });
      }
    } else if (type === 'next' && downloadListData?.data?.has_more) {
      setCurrentPage((prevPage) => prevPage + 1);
      if (downloadListData?.data?.last_id) {
        setPaginationPageId({
          firstId: null,
          lastId: downloadListData.data.last_id,
        });
      }
    }
  };

  useEffect(() => {
    if (isSuccessDownloadFile && downloadFileData) {
      window.open(downloadFileData.data.signed_url, '_blank');
    }
  }, [isSuccessDownloadFile, downloadFileData]);

  useEffect(() => {
    if (isErrorDownloadFile) {
      showNotification({
        type: 'error',
        message: `Unable to download the file.`,
      });
    }
  }, [isErrorDownloadFile]);

  const renderDownloadListTableCell = (item, propertyName) => {
    const value = item[propertyName];

    switch (propertyName) {
      case 'file_name':
        return <Text>{value || 'No File'}</Text>;
      case 'emails':
        return <Text>{Array.isArray(value) ? value.join(', ') : ''}</Text>;
      case 'trigger_type':
        return value ? (
          <Badge size="large" color={triggerTypeBadgeColor[value.toLowerCase()]} marginX="auto">
            {value}
          </Badge>
        ) : null;
      case 'status':
        return (
          <Badge size="large" color={statusBadgeColor[value] || 'information'} marginX="auto">
            {value}
          </Badge>
        );
      case 'created_at':
      case 'updated_at':
        return <Text>{moment.unix(value).format('DD MMM YYYY')}</Text>;
      case 'download':
        return (
          <Link
            isDisabled={item.status.toLowerCase() !== 'ready'}
            onClick={(e) => {
              e.preventDefault();
              downloadFileMutation({ reportId: item.id });
            }}
            icon={DownloadIcon}
            variant="button"
          >
            Download File
          </Link>
        );
      default:
        return <Text>{value}</Text>;
    }
  };

  return (
    <ErrorBoundary team={Teams.RECON_SAAS} resetOnProps>
      <Box>
        <Box paddingY="spacing.6" display="flex" flexDirection="column" gap="spacing.5">
          <Box display="flex" justifyContent="space-between" alignItems="center">
            <Heading weight="semibold" color="surface.text.gray.normal">
              Download Reports
            </Heading>
          </Box>
          <RenderErrorLoadingOrChild isError={isError} isLoading={isLoading}>
            <Box overflow="auto">
              {downloadListData?.data?.items?.length ? (
                <Table
                  data={{ nodes: downloadListData.data.items }}
                  gridTemplateColumns="repeat(4, 1fr) 1.5fr repeat(5, 1fr)"
                  isLoading={isLoading}
                >
                  {(tabledata) => (
                    <>
                      <TableHeader>
                        <TableHeaderRow>
                          {downloadTableData.map((header) => (
                            <TableHeaderCell key={header.label}>
                              <Text
                                {...(header.textAlign === 'center'
                                  ? { marginX: 'auto' }
                                  : header.textAlign === 'right'
                                  ? { marginLeft: 'auto' }
                                  : {})}
                              >
                                {header.label}
                              </Text>
                            </TableHeaderCell>
                          ))}
                        </TableHeaderRow>
                      </TableHeader>
                      <TableBody>
                        {tabledata.map((tableItem) => (
                          <TableRow key={tableItem.id} item={tableItem}>
                            {downloadTableData.map(({ propertyName }) => (
                              <TableCell key={propertyName}>
                                {renderDownloadListTableCell(tableItem, propertyName)}
                              </TableCell>
                            ))}
                          </TableRow>
                        ))}
                      </TableBody>
                    </>
                  )}
                </Table>
              ) : null}
            </Box>
            <Box display="flex" justifyContent="flex-end" alignItems="center" marginTop="spacing.4">
              <Text marginRight="spacing.4">Showing Page: {currentPage}</Text>
              <Button
                icon={ArrowLeftIcon}
                marginRight="spacing.4"
                isDisabled={currentPage === 1}
                onClick={() => handlePagination('prev')}
              >
                Previous
              </Button>
              <Button
                icon={ArrowRightIcon}
                isDisabled={!downloadListData?.data?.has_more}
                onClick={() => handlePagination('next')}
              >
                Next
              </Button>
            </Box>
          </RenderErrorLoadingOrChild>
        </Box>
      </Box>
    </ErrorBoundary>
  );
};

export default compose(
  connect(null, {
    showNotification,
  }),
)(DownloadList);
