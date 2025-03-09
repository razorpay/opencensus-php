import React, { useState, useEffect, useCallback } from 'react';
import {
  Box,
  Radio,
  RadioGroup,
  Divider,
  Text,
  ArrowLeftIcon,
  ArrowRightIcon,
  Button,
  Link,
  Card,
  CardBody,
  Dropdown,
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableRow,
  TableCell,
  TableBody,
  ActionListItemText,
  ToastContainer,
} from '@razorpay/blade/components';
import moment from 'moment';
import { useNavigate, useParams } from 'react-router-dom';

import DateRangePicker from 'common/ui/DateRangePicker';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { getStartDateFromDiff } from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  dateRangePresets,
  FILE_WORKFLOW_KEY,
  DashboardTabs,
  ProcessTabs,
  RULE_ID,
  RECON_DASHBOARD_BASEURL,
} from 'merchant/views/Reconciliations/Dashboard/constants';
import {
  BladeDropdownWrapper,
  RenderErrorLoadingOrChild,
} from 'merchant/views/Reconciliations/commonComponents';
import { ReconScreens } from 'merchant/views/Reconciliations/const';
import { useCalendarRange, useReconTracking } from 'merchant/views/Reconciliations/hooks';

export default function RunDetail() {
  const [detailsList, setDetailsList] = useState([]);
  const [stats, setStats] = useState(null);
  const [paginationData, setPaginationData] = useState(null);
  const [currentPage, setCurrentPage] = useState(0);
  const [filter, setFilter] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [runsList, setRunsList] = useState([]);
  const [error, setError] = useState(false);
  const { dateRange, handleRangeChange } = useCalendarRange();
  const [selectdRunId, setSelectedRunId] = useState();
  const [runChangeCounter, setRunChangeCounter] = useState(1);

  const navigate = useNavigate();
  const { runId: fileWorkflowId, processId: activeProcessId } = useParams();

  const fetchRuns = async (props = {}) => {
    const fetchRunsPayload = {
      filter: {
        product_id: [],
        type: [],
        status: [],
      },
      sort_key: '',
      page: 0,
      offset: 0,
      page_size: 50,
      ...props,
    };
    if (activeProcessId) {
      fetchRunsPayload.filter.merchant_process_id = [activeProcessId];
    }
    const res = await merchantFetch({
      url: `recon-saas/recon_run`,
      mode: 'live',
      method: 'POST',
      data: fetchRunsPayload,
    });
    if (res?.status_code === 200) {
      const { items } = res.data;
      setRunsList(items);
      if (props?.first_id) {
        setCurrentPage(currentPage - 1);
      } else if (props?.last_id) {
        setCurrentPage(currentPage + 1);
      }
    }
  };

  const fetchDetails = useCallback(
    async (props = {}) => {
      try {
        setError(false);
        setIsLoading(true);
        const fetchDetailsFilter = [
          {
            key: FILE_WORKFLOW_KEY,
            value: fileWorkflowId,
          },
        ];
        if (filter) {
          const filters = filter.split(',');
          if (filters.length > 0) {
            fetchDetailsFilter.push({ key: RULE_ID, value: filters });
          }
        }
        const body = {
          filters: fetchDetailsFilter,
          page_size: 10,
          from_date: dateRange?.startDate?.unix(),
          to_date: dateRange?.endDate?.unix(),
          ...props,
        };

        const res = await merchantFetch({
          url: `recon-saas/recon_output/list`,
          mode: 'live',
          method: 'POST',
          data: body,
        });

        if (res?.status_code === 200) {
          const { items, ...pageData } = res.data;
          if (Array.isArray(items)) {
            setDetailsList(items);
          }
          setPaginationData(pageData);
          if (props?.first_id) {
            setCurrentPage(currentPage - 1);
          } else if (props?.last_id) {
            setCurrentPage(currentPage + 1);
          }
        } else {
          setError(true);
        }
      } catch (error) {
        setError(true);
      } finally {
        setIsLoading(false);
      }
    },
    [fileWorkflowId, dateRange, filter, currentPage],
  );

  const fetchStats = useCallback(async () => {
    const from = dateRange.startDate.unix();
    const to = dateRange.endDate.unix();
    const res = await merchantFetch({
      url: `recon-saas/recon_output/stats/${fileWorkflowId}?from_date=${from}&to_date=${to}`,
      mode: 'live',
      method: 'get',
    });
    if (res?.status_code === 200) {
      setStats(res.data);
    }
  }, [dateRange, fileWorkflowId]);

  useReconTracking({
    objectName: 'recon run detail',
    screen: ReconScreens.RunDetailView,
    properties: {
      ...(activeProcessId ? { activeProcessId } : {}),
      runId: fileWorkflowId,
    },
  });

  const handlePagination = (type) => {
    switch (type) {
      case 'prev':
        currentPage > 0 && fetchDetails({ first_id: paginationData.first_id });
        break;
      case 'next':
        paginationData?.has_more && fetchDetails({ last_id: paginationData.last_id });
        break;
      default:
        break;
    }
  };

  const handleRunListChange = (event) => {
    const currentValue = event.values[0];
    if (currentValue) {
      setRunChangeCounter((prevCount) => prevCount + 1);
      setSelectedRunId(currentValue);
      navigate(
        activeProcessId
          ? `${RECON_DASHBOARD_BASEURL}/${DashboardTabs.PROCESSES}/${activeProcessId}/${ProcessTabs.RUNS}/${currentValue}`
          : `${RECON_DASHBOARD_BASEURL}/${DashboardTabs.RUNS}/${currentValue}`,
      );
    }
  };

  const handleRadioChange = ({ value }) => {
    setCurrentPage(0);
    setFilter(value);
  };

  const goBack = () => {
    navigate(runChangeCounter * -1);
    setCurrentPage(0);
    setPaginationData(null);
    setRunsList([]);
  };

  const handlePresetChange = ({ value }) => {
    const dateObj = {
      startDate: getStartDateFromDiff(value, moment()),
      endDate: moment().endOf('day'),
    };
    analyticsTrackWithUserInfo({
      screen: ReconScreens.RunDetailView,
      objectName: 'recon date range',
      actionName: 'selected',
      properties: {
        ...(activeProcessId ? { activeProcessId } : {}),
        startDate: dateObj.startDate.format('lll'),
        endDate: dateObj.endDate.format('lll'),
        runId: fileWorkflowId,
      },
    });
    handleRangeChange(dateObj);
  };

  useEffect(() => {
    if (fileWorkflowId) {
      setSelectedRunId(fileWorkflowId);
      fetchRuns();
      fetchDetails();
    }
  }, [fileWorkflowId]);

  useEffect(() => {
    setPaginationData(null);
    fetchDetails();
    fetchStats();
  }, [filter]);

  return (
    <>
      <Box display="flex" alignItems="center" padding="spacing.6" justifyContent="space-between">
        <Box display="flex" alignItems="center">
          <Link icon={ArrowLeftIcon} iconPosition="left" onClick={goBack}>
            Go Back
          </Link>
          <Divider orientation="vertical" marginX="spacing.6" />
          <BladeDropdownWrapper>
            <Dropdown value={fileWorkflowId} marginRight="spacing.4">
              <SelectInput value={selectdRunId} prefix="Run: " onChange={handleRunListChange} />
              <DropdownOverlay>
                <ActionList>
                  {Array.isArray(runsList) &&
                    runsList.map((run) => (
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
                    ))}
                </ActionList>
              </DropdownOverlay>
            </Dropdown>
          </BladeDropdownWrapper>
          <div className="date-range-container">
            <DateRangePicker
              presets={dateRangePresets}
              allowSingleDaySelect
              onClose={handleRangeChange}
              onSelectPreset={handlePresetChange}
            />
          </div>
        </Box>
      </Box>
      <Card margin="spacing.6">
        <CardBody>
          <Box padding="spacing.4">
            <Box display="flex" marginBottom="spacing.4" alignItems="center">
              {stats ? (
                <RadioGroup
                  name="list-record-type"
                  onChange={handleRadioChange}
                  defaultValue={filter}
                >
                  <Box display="flex">
                    <RadioSelect
                      value={null}
                      title="All Records"
                      subTitle={`${stats?.total?.count || 0} records`}
                    />
                    <RadioSelect
                      value={stats?.Reconciled?.rules?.join(',')}
                      title="Matched"
                      subTitle={`${stats?.Reconciled?.count || 0} records`}
                    />
                    <RadioSelect
                      value={stats?.Unreconciled?.rules?.join(',')}
                      title="Unmatched"
                      subTitle={`${stats?.Unreconciled?.count || 0} records`}
                    />
                  </Box>
                </RadioGroup>
              ) : null}
            </Box>
            <RenderErrorLoadingOrChild
              isError={error}
              isLoading={isLoading || !paginationData?.cols}
            >
              <Table
                data={{ nodes: detailsList }}
                gridTemplateColumns={`repeat(${paginationData?.cols?.length},minmax(auto, 1fr))`}
              >
                {(tableData) => (
                  <>
                    <TableHeader>
                      <TableHeaderRow>
                        {paginationData?.cols.map((column) => (
                          <TableHeaderCell key={column}>{column}</TableHeaderCell>
                        ))}
                      </TableHeaderRow>
                    </TableHeader>
                    <TableBody>
                      {tableData.map((tableItem, index) => (
                        <TableRow key={index} item={tableItem}>
                          {paginationData.cols.map((key) => (
                            <TableCell key={key}>{tableItem[key]}</TableCell>
                          ))}
                        </TableRow>
                      ))}
                    </TableBody>
                  </>
                )}
              </Table>
            </RenderErrorLoadingOrChild>
            <Box display="flex" justifyContent="flex-end" alignItems="center" marginTop="spacing.4">
              <Text marginRight="spacing.4">Showing Page: {currentPage + 1}</Text>
              <Button
                icon={ArrowLeftIcon}
                marginRight="spacing.4"
                isDisabled={currentPage === 0}
                onClick={() => handlePagination('prev')}
              >
                Previous
              </Button>
              <Button
                icon={ArrowRightIcon}
                isDisabled={!paginationData?.has_more || detailsList.length === 0}
                onClick={() => handlePagination('next')}
              >
                Next
              </Button>
            </Box>
          </Box>
        </CardBody>
      </Card>
      <ToastContainer />
    </>
  );
}

const RadioSelect = ({ value, title, subTitle }) => (
  <Box backgroundColor="surface.background.gray.subtle" padding="spacing.3" marginRight="spacing.4">
    <Radio value={value}>
      <Box display="flex">
        <Text weight="semibold">{title}</Text>
        <Divider orientation="vertical" marginX="spacing.2" />
        <Text>{subTitle}</Text>
      </Box>
    </Radio>
  </Box>
);
