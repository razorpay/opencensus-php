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
} from '@razorpay/blade/components';
import moment from 'moment';

import DateRangePicker from 'common/ui/DateRangePicker';
import TableBody from 'common/ui/TableBody';
import { merchantFetch } from 'merchant/utils/ajax';
import { dateRangePresets } from 'merchant/views/Reconciliations/Dashboard/constants';
import { Loader } from 'merchant/views/Reconciliations/commonComponents';

export default function Detail({ fileWorkflowId, closeDetail, openDetail }) {
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

  const fetchRuns = async (props = {}) => {
    const raw = {
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
    setIsLoading(true);
    const res = await merchantFetch({
      url: `recon-saas/recon_run`,
      mode: 'live',
      method: 'POST',
      data: raw,
    });
    if (res?.status_code === 200) {
      const { items, ...pageData } = res.data;
      setRunsList(items);
      setPaginationData(pageData);
      setIsLoading(false);
      if (props?.first_id) {
        setCurrentPage(currentPage - 1);
      } else if (props?.last_id) {
        setCurrentPage(currentPage + 1);
      }
    }
  };

  const fetchDetails = async (props = {}) => {
    setIsLoading(true);
    const body = {
      filters: [
        {
          key: 'file_detail_workflow_id',
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
    }
    setIsLoading(false);
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
    fetchStats();
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
          <Link icon={ArrowLeftIcon} iconPosition="left" onClick={closeDetail}>
            Go Back
          </Link>
          <Divider orientation="vertical" marginX="spacing.6" />
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
          <div className="date-range-container">
            <DateRangePicker
              onDatesChange={(startDate, endDate) => {
                setStartEndDates({ startDate: startDate.unix(), endDate: endDate.unix() });
              }}
              presets={dateRangePresets}
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
                      subTitle={`${stats?.Reconciled?.count} records`}
                    />
                    <RadioSelect
                      value={stats?.Unreconciled?.rules?.join(',')}
                      title="Unmatched"
                      subTitle={`${stats?.Unreconciled?.count} records`}
                    />
                  </Box>
                </RadioGroup>
              ) : null}
            </Box>
            {!isLoading && paginationData?.cols && detailsList?.length >= 0 ? (
              <div className="table-responsive">
                <table className="table table-hover">
                  <thead>
                    <tr>
                      {paginationData?.cols.map((column) => {
                        return (
                          <th key={column} style={{ background: '#324664', color: '#fff' }}>
                            {column}
                          </th>
                        );
                      })}
                    </tr>
                  </thead>
                  <TableBody colSpan={4} rows={detailsList}>
                    {detailsList?.map((item, index) => (
                      <tr key={index}>
                        {paginationData.cols.map((key) => (
                          <td key={key}>{item[key]}</td>
                        ))}
                      </tr>
                    ))}
                  </TableBody>
                </table>
              </div>
            ) : (
              <Loader />
            )}
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
  <Box backgroundColor="brand.gray.300.lowContrast" padding="spacing.3" marginRight="spacing.4">
    <Radio value={value}>
      <Box display="flex">
        <Text weight="bold">{title}</Text>
        <Divider orientation="vertical" marginX="spacing.2" />
        <Text>{subTitle}</Text>
      </Box>
    </Radio>
  </Box>
);
