import React, { useState, useEffect } from 'react';
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
} from '@razorpay/blade/components';
import moment from 'moment';

import DateRangePicker from 'common/ui/DateRangePicker';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  dateRangePresets,
  FILE_WORKFLOW_KEY,
} from 'merchant/views/Reconciliations/Dashboard/constants';
import {
  BladeDropdownWrapper,
  RenderErrorLoadingOrChild,
} from 'merchant/views/Reconciliations/commonComponents';

export default function Detail({ fileWorkflowId, closeDetail, openDetail, activeProcess }) {
  const [detailsList, setDetailsList] = useState([]);
  const [stats, setStats] = useState(null);
  const [paginationData, setPaginationData] = useState(null);
  const [currentPage, setCurrentPage] = useState(0);
  const [filter, setFilter] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [runsList, setRunsList] = useState([]);
  const [startEndDates, setStartEndDates] = useState({
    startDate: moment().subtract(7, 'days').startOf('day').unix(),
    endDate: moment().endOf('day').unix(),
  });
  const [error, setError] = useState(false);

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
    if (activeProcess?.id) {
      fetchRunsPayload.filter.merchant_process_id = [activeProcess.id];
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

  const fetchDetails = async (props = {}) => {
    try {
      setError(false);
      setIsLoading(true);
      const body = {
        filters: [
          {
            key: FILE_WORKFLOW_KEY,
            value: fileWorkflowId,
          },
        ],
        page_size: 10,
        from_date: startEndDates.startDate,
        to_date: startEndDates.endDate,
        ...props,
      };
      if (filter) {
        const filters = filter.split(',');
        if (filters.length > 0) {
          body.filters.push({ key: 'rule_id', value: filters });
        }
      }
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
  };

  const fetchStats = async () => {
    const from = startEndDates.startDate;
    const to = startEndDates.endDate;
    const res = await merchantFetch({
      url: `recon-saas/recon_output/stats/${fileWorkflowId}?from_date=${from}&to_date=${to}`,
      mode: 'live',
      method: 'get',
    });
    if (res?.status_code === 200) {
      setStats(res.data);
    }
  };

  useEffect(() => {
    fetchRuns();
  }, []);

  useEffect(() => {
    fetchDetails();
  }, [filter]);

  useEffect(() => {
    setPaginationData(null);
    if (filter === null) {
      fetchDetails();
      fetchStats();
    } else {
      setFilter(null);
      fetchStats();
    }
  }, [fileWorkflowId, startEndDates]);

  const handlePagination = (type) => {
    switch (type) {
      case 'prev':
        currentPage > 0 && fetchDetails({ first_id: paginationData.first_id });
        break;
      case 'next':
        paginationData?.has_more && fetchDetails({ last_id: paginationData.last_id });
        break;
      default:
    }
  };

  const handleRunChange = ({ values }) => {
    openDetail(values[0]);
  };

  const handleRadioChange = ({ value }) => {
    setCurrentPage(0);
    setFilter(value);
  };

  const goBack = () => {
    setCurrentPage(0);
    setPaginationData(null);
    setRunsList([]);
    closeDetail();
  };

  return (
    <>
      <Box
        display="flex"
        alignItems="center"
        marginTop="spacing.6"
        paddingX="spacing.6"
        justifyContent="space-between"
      >
        <Box display="flex" alignItems="center">
          <Link icon={ArrowLeftIcon} iconPosition="left" onClick={goBack}>
            Go Back
          </Link>
          <Divider orientation="vertical" marginX="spacing.6" />
          <BladeDropdownWrapper>
            <Dropdown value={fileWorkflowId} marginRight="spacing.4">
              <SelectInput value={fileWorkflowId} prefix="Run: " onChange={handleRunChange} />
              <DropdownOverlay>
                <ActionList>
                  {Array.isArray(runsList) &&
                    runsList.map((run) => (
                      <ActionListItem key={run.id} title={run.id} value={run.id} />
                    ))}
                </ActionList>
              </DropdownOverlay>
            </Dropdown>
          </BladeDropdownWrapper>
          <div className="date-range-container">
            <DateRangePicker
              onDatesChange={(startDate, endDate) => {
                setStartEndDates({ startDate: startDate.unix(), endDate: endDate.unix() });
              }}
              presets={dateRangePresets}
              allowSingleDaySelect
            />
          </div>
        </Box>
      </Box>
      <Card margin="spacing.6">
        <CardBody>
          <Box padding="spacing.4">
            <Box display="flex" marginBottom="spacing.4" alignItems="center">
              {stats ? (
                <RadioGroup name="list-record-type" onChange={handleRadioChange} defaultValue="">
                  <Box display="flex">
                    <RadioSelect
                      value=""
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
