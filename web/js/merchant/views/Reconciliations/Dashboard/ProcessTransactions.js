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
  Dropdown,
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  DownloadIcon,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableRow,
  TableCell,
  TableBody,
  ActionListItemText,
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

import ExportReportModal from './ExportReportModal';

export default function ProcessTransactions({ activeProcess }) {
  const [detailsList, setDetailsList] = useState([]);
  const [paginationData, setPaginationData] = useState(null);
  const [currentPage, setCurrentPage] = useState(0);
  const [filter, setFilter] = useState(null);
  const [startEndDates, setStartEndDates] = useState({
    startDate: moment().subtract(7, 'days').startOf('day').unix(),
    endDate: moment().endOf('day').unix(),
  });
  const [runsList, setRunsList] = useState([]);
  const [activeRun, setActiveRun] = useState('all');
  const [isOpen, setIsOpen] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);

  const fetchRuns = async () => {
    const raw = {
      filter: {
        merchant_process_id: [activeProcess?.id],
      },
      sort_key: '',
      page: 0,
      offset: 0,
    };
    const res = await merchantFetch({
      url: `recon-saas/recon_run`,
      mode: 'live',
      method: 'POST',
      data: raw,
    });
    if (res?.status_code === 200) {
      const { items } = res.data;
      setRunsList(items);
    }
  };

  const fetchDetails = async (props = {}) => {
    try {
      setError(false);
      setLoading(true);
      const body = {
        merchant_process_id: activeProcess?.id,
        filters: [],
        page_size: 10,
        from_date: startEndDates.startDate,
        to_date: startEndDates.endDate,
        ...props,
      };
      if (filter) {
        body.filters.push({ key: 'recon_status', value: filter });
      }
      const res = await merchantFetch({
        url: `recon-saas/recon_process/records`,
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
      setLoading(false);
    }
  };

  const fetchRunSpecificDetails = async (props = {}) => {
    if (activeRun === 'all') {
      return;
    }
    try {
      setError(false);
      setLoading(true);
      const runId = activeRun;
      const body = {
        filters: [
          {
            key: FILE_WORKFLOW_KEY,
            value: runId,
          },
        ],
        page_size: 10,
        from_date: startEndDates?.startDate,
        to_date: startEndDates?.endDate,
        ...props,
      };
      if (filter) {
        const from = startEndDates?.startDate;
        const to = startEndDates?.endDate;
        const res = await merchantFetch({
          url: `recon-saas/recon_output/stats/${activeRun}?from_date=${from}&to_date=${to}`,
          mode: 'live',
          method: 'get',
        });
        if (res?.status_code === 200) {
          const stats = res.data;
          let ruleIds = [];
          if (filter === 'Reconciled') {
            ruleIds = stats?.Reconciled?.rules;
          } else if (filter === 'Unreconciled') {
            ruleIds = stats?.Unreconciled?.rules;
          }
          body.filters.push({ key: 'rule_id', value: ruleIds });
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
      setLoading(false);
    }
  };

  const callRespectiveFetchApi = (props) => {
    if (activeRun === 'all') {
      fetchDetails(props);
    } else {
      fetchRunSpecificDetails(props);
    }
  };

  useEffect(() => {
    callRespectiveFetchApi();
  }, [filter, startEndDates, activeRun]);

  useEffect(() => {
    fetchRuns();
  }, []);

  const handlePagination = (type) => {
    switch (type) {
      case 'prev':
        currentPage > 0 && callRespectiveFetchApi({ first_id: paginationData.first_id });
        break;
      case 'next':
        paginationData?.has_more && callRespectiveFetchApi({ last_id: paginationData.last_id });
        break;
      default:
    }
  };

  const handleRunChange = ({ values }) => {
    setCurrentPage(0);
    setActiveRun(values[0]);
  };

  const handleDateChange = (startDate, endDate) => {
    setCurrentPage(0);
    setStartEndDates({ startDate: startDate.unix(), endDate: endDate.unix() });
  };

  const handleFilterChange = ({ value }) => {
    setCurrentPage(0);
    setFilter(value);
  };

  return (
    <Box padding="spacing.4" testID="recon-process-transactions">
      <Box
        display="flex"
        marginBottom="spacing.4"
        alignItems="center"
        justifyContent="space-between"
      >
        <Box display="flex" alignItems="center">
          <BladeDropdownWrapper>
            <Dropdown marginRight="spacing.4">
              <SelectInput value={activeRun} prefix="Run: " onChange={handleRunChange} />
              <DropdownOverlay>
                <ActionList>
                  <ActionListItem title="All" value="all" />
                  {runsList.map((run) => (
                    <ActionListItem
                      key={run?.id}
                      title={run?.id}
                      value={run?.id}
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
              onDatesChange={handleDateChange}
              presets={dateRangePresets}
              allowSingleDaySelect
            />
          </div>
        </Box>
        <Button icon={DownloadIcon} iconPosition="left" onClick={() => setIsOpen(true)}>
          Export View
        </Button>
      </Box>
      <Box display="flex" marginBottom="spacing.4" alignItems="center">
        <RadioGroup
          name="list-record-type"
          onChange={handleFilterChange}
          defaultValue=""
          isDisabled={loading}
        >
          <Box display="flex">
            <RadioSelect value="" title="All Records" />
            <RadioSelect value="Reconciled" title="Matched" />
            <RadioSelect value="Unreconciled" title="Unmatched" />
          </Box>
        </RadioGroup>
      </Box>
      <RenderErrorLoadingOrChild
        isError={error}
        isLoading={loading || !paginationData?.cols || !(detailsList?.length >= 0)}
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
      {isOpen && (
        <ExportReportModal
          isOpen={isOpen}
          setIsOpen={setIsOpen}
          filters={{ type: filter, startEndDates }}
          merchantProcessId={activeProcess?.id}
        />
      )}
    </Box>
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
