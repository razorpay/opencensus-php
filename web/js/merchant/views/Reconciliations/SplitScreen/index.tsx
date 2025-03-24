import React, { useEffect, useState } from 'react';
import {
  Box,
  Card,
  CardBody,
  Link,
  ArrowLeftIcon,
  Divider,
  DatePicker,
  Dropdown,
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  DownloadIcon,
  Button,
  FilterIcon,
  Tooltip,
  ActionListItemText,
} from '@razorpay/blade/components';
import { useMutation, useQuery } from '@tanstack/react-query';
import moment from 'moment';
import { useNavigate, useParams } from 'react-router-dom';

import ErrorBoundary, { Teams } from 'common/new-ui/ErrorBoundary';
import {
  DashboardTabs,
  ProcessTabs,
  RECON_DASHBOARD_BASEURL,
} from 'merchant/views/Reconciliations/Dashboard/constants';
import DataGrid from 'merchant/views/Reconciliations/SplitScreen/DataGrid';
import FilterModal from 'merchant/views/Reconciliations/SplitScreen/FilterModal';
import SplitScreenDrawer from 'merchant/views/Reconciliations/SplitScreen/SplitScreenDrawer';
import { ReconDivider } from 'merchant/views/Reconciliations/SplitScreen/style';
import { useDrag } from 'merchant/views/Reconciliations/SplitScreen/usedrag';
import { dateRangePreset } from 'merchant/views/Reconciliations/SplitScreen/utils';
import {
  fetchProcessRunList,
  fetchReconProcessDetail,
  downloadSplitScreenReport,
} from 'merchant/views/Reconciliations/api';
import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';
import { showNotification } from 'merchant_common/reducers/notifications';

import { connect } from 'react-redux';
import { compose } from 'redux';

import type { SplitScreenProps, IData, DownloadReportParams } from 'merchant/views/Reconciliations/SplitScreen/types';

const SplitScreen: React.FC<SplitScreenProps> = ({ showNotification }) => {
  const navigate = useNavigate();
  const { runId, processId } = useParams();

  const { dynamicWidth, handleDragMouseDown } = useDrag({
    throttleDelay: 100,
  });

  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [isFilterModalOpen, setIsFilterModalOpen] = useState(false);

  const [clickedRow, setClickedRow] = useState<IData | null>(null);

  const [dateRange, setDateRange] = useState({
    startDate: '',
    endDate: '',
  });

  const [reconFilter, setReconFilter] = useState<Record<string, string>>({
    'Recon Status': '',
    'Recon Remarks': '',
  });

  const [reconPairSources, setReconPairSources] = useState<string[]>([]);

  const { data: processRunList } = useQuery({
    queryKey: ['processRunList', processId],
    queryFn: () =>
      fetchProcessRunList({
        processId,
      }),
  });

  const {
    data: reconProcessData,
    isLoading: isLoadingForReconProcess,
    isError: isErrorForReconProcess,
    isSuccess: isSuccessForReconProcess,
  } = useQuery({
    queryKey: ['reconProcess', processId],
    queryFn: () => fetchReconProcessDetail({ processId }),
  });
  
  const {
    mutate: downloadReportMutation,
    isLoading: isLoadingForDownloadReport,
  } = useMutation({
    mutationFn: ({ runId, sources, fromDate, toDate, filter }: DownloadReportParams) => 
      downloadSplitScreenReport({ runId, sources, fromDate, toDate, filter }),
    onError: () => {
      showNotification({
        type: 'error',
        message: `Unable to download report. Please try again later.`,
      });
    },
    onSuccess: () => {
      showNotification({
        type: 'success',
        message: `Report is being generated. Please check in the download section after some time.`,
      });
      navigate(`${RECON_DASHBOARD_BASEURL}/${DashboardTabs.DOWNLOADS}`);
    }
  });
  
  const goBack = () => {
    navigate(-1);
  };

  const handleRunListChange = ({ event }) => {
    const currentValue = event.values[0];
    if (currentValue) {
      navigate(
        `${RECON_DASHBOARD_BASEURL}/${DashboardTabs.PROCESSES}/${processId}/${ProcessTabs.SPLIT}/${currentValue}`,
      );
    }
  };

  useEffect(() => {
    if (isSuccessForReconProcess && reconProcessData?.data?.merchant_sources.length) {
      const pairs = reconProcessData?.data?.merchant_sources.slice(0, 2).map((source) => source.id);
      if (pairs.length) {
        setReconPairSources(pairs);
      }
    }
  }, [isSuccessForReconProcess]);

  return (
    <ErrorBoundary team={Teams.RECON_SAAS} resetOnProps>
      <Box
        display="flex"
        gap="spacing.4"
        alignItems="end"
        justifyContent="start"
        padding="spacing.6"
      >
        <Link icon={ArrowLeftIcon} iconPosition="left" onClick={goBack}>
          Go Back
        </Link>
        <Divider orientation="vertical" />
        <Box
          display="grid"
          gridTemplateColumns="1.75fr 2.25fr 0.25fr 1fr"
          justifyContent="start"
          alignItems="end"
          gap="spacing.4"
        >
          <Dropdown selectionType="single">
            <SelectInput
              label="Run ID"
              labelPosition="top"
              placeholder="Run ID"
              name="run_id"
              onChange={(event) => handleRunListChange({ event })}
              value={runId}
            />
            <DropdownOverlay>
              <ActionList>
                {processRunList && processRunList?.data?.items?.length
                  ? processRunList.data.items.map((run) => {
                      return (
                        <ActionListItem
                          key={run.id}
                          title={run.id}
                          value={run.id}
                          trailing={
                            <ActionListItemText>
                              {moment(run.updated_at * 1000).format('ll')}
                            </ActionListItemText>
                          }
                        />
                      );
                    })
                  : null}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
          <DatePicker
            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
            // @ts-ignore
            label={{
              end: 'End Date',
              start: 'Start Date',
            }}
            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
            // @ts-ignore
            selectionType="range"
            value={[dateRange.startDate, dateRange.endDate]}
            onChange={(date) => {
              setDateRange({
                startDate: date[0],
                endDate: date[1],
              });
            }}
            p
            presets={dateRangePreset}
          />
          <Tooltip content="Advance Filters" placement="bottom">
            <Button
              icon={FilterIcon}
              variant="tertiary"
              onClick={() => setIsFilterModalOpen(true)}
              accessibilityLabel="Advance Filters"
            />
          </Tooltip>
          <Button
            variant="primary"
            icon={DownloadIcon}
            iconPosition="left"
            isLoading={isLoadingForDownloadReport}
            onClick={() =>
              downloadReportMutation({
                runId,
                sources: reconPairSources,
                fromDate: dateRange.startDate ? `${moment(dateRange.startDate).unix()}` : '0',
                toDate: dateRange.endDate ? `${moment(dateRange.endDate).unix()}` : '0',
                filter: reconFilter,
              })
            }
          >
            Export View
          </Button>
        </Box>
      </Box>
      <Box width="100%">
        <Card padding="spacing.0" marginX="spacing.6" marginY="spacing.2">
          <CardBody>
            <RenderErrorLoadingOrChild
              isError={isErrorForReconProcess}
              isLoading={isLoadingForReconProcess}
            >
              <Box display="flex" gap="spacing.1" paddingX="spacing.4">
                <Box overflow="hidden" paddingY="spacing.4" width={`${dynamicWidth}%`}>
                  <DataGrid
                    reconFilter={reconFilter}
                    dateRange={dateRange}
                    sourceId={reconPairSources[0]}
                    setIsDrawerOpen={setIsDrawerOpen}
                    // setSelectedRows={setSelectedRows}
                    setClickedRow={setClickedRow}
                    merchantSources={reconProcessData?.data?.merchant_sources || []}
                    setReconPairSources={setReconPairSources}
                  />
                </Box>
                <ReconDivider onMouseDown={handleDragMouseDown} />
                <Box overflow="hidden" paddingY="spacing.4" width={`${100 - dynamicWidth}%`}>
                  <DataGrid
                    reconFilter={reconFilter}
                    dateRange={dateRange}
                    sourceId={reconPairSources[1]}
                    setIsDrawerOpen={setIsDrawerOpen}
                    // setSelectedRows={setSelectedRows}
                    setClickedRow={setClickedRow}
                    merchantSources={reconProcessData?.data?.merchant_sources || []}
                    setReconPairSources={setReconPairSources}
                  />
                </Box>
              </Box>
            </RenderErrorLoadingOrChild>
          </CardBody>
        </Card>
      </Box>
      <SplitScreenDrawer
        isDrawerOpen={isDrawerOpen}
        setIsDrawerOpen={setIsDrawerOpen}
        recordId={clickedRow?._id ? clickedRow._id.toString() : ''}
      />
      <FilterModal
        isFilterModalOpen={isFilterModalOpen}
        setIsFilterModalOpen={setIsFilterModalOpen}
        reconFilter={reconFilter}
        setReconFilter={setReconFilter}
      />
    </ErrorBoundary>
  );
};

export default compose(connect(null, { showNotification }))(SplitScreen);
