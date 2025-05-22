import React, { useRef, useState } from 'react';
import {
  Box,
  FilePlusIcon,
  Text,
  Spinner,
  Table,
  TableCell,
  TableHeader,
  TableHeaderRow,
  TableBody,
  TableHeaderCell,
  TablePagination,
  Badge,
  TableRow,
  Link,
  DeleteIcon,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { NavLink } from 'react-router-dom';
import {
  getProgramContentSections,
  getProgramDenominationSections,
} from 'merchant/views/GCMS/Programs/constants';
import { fetchProgramById } from 'merchant/views/GCMS/Programs/queries';
import Wrapper from 'merchant/views/GCMS/shared/Wrapper';
import { RESELLERS_STATUS } from 'merchant/views/GCMS/shared/constants';

import useFetchLinkedResellers from 'merchant/views/GCMS/shared/hooks/useFetchLinkedResellers';
import LinkingResellerModal from '../shared/LinkingResellerModal';
import { trackResellerDetailsPageClicked } from 'merchant/views/GCMS/Resellers/events';
import { LinkedResellerProps } from './types';

const merchant_name = {
  title: 'Reseller Name',
  value: (item) => <Text>{item.reseller_name}</Text>,
};
const reseller_id = {
  title: 'Reseller ID',
  value: (item) => (
    <NavLink
      key={item.reseller_id}
      to={`/gcms/resellers/${item.reseller_id}`}
      state={{ prevPath: location?.pathname }}
      onClick={() => {
        trackResellerDetailsPageClicked({
          resellerId: item.reseller_id,
          resellerName: item.reseller_name,
        });
      }}
    >
      {item.reseller_id}
    </NavLink>
  ),
};
const discount = {
  title: 'Discount',
  value: (item) => <Text weight="semibold">{item.default_discount}</Text>,
};

const status = {
  title: 'Status',
  value: (item) => (
    <Badge color={RESELLERS_STATUS[item.status].color}>{RESELLERS_STATUS[item.status].label}</Badge>
  ),
};
const action = {
  title: 'Action',
  value: (item) => (
    <Link variant="button" onClick={() => {}} icon={DeleteIcon}>
      Delete
    </Link>
  ),
};

const resellerListColumns = [reseller_id, merchant_name, discount, status];

const LinkedResellers = ({
  program,
  mode,
  merchantId,
  isOpen,
  closeModal,
  selectedTab,
}: LinkedResellerProps) => {
  const pageNumber = useRef(0);
  const { isLoading, resellers, skip, handleNext, handlePrev, handlePageSizeChange, refetch } =
    useFetchLinkedResellers({
      mode,
      merchantId,
      programId: program.id,
    });

  const tableData = {
    nodes: resellers?.items ?? [],
  };

  function handlePageChange({ page }) {
    if (page > pageNumber.current) {
      handleNext();
    } else {
      handlePrev();
    }
    pageNumber.current = page;
  }

  const isEmptyData = !resellers || resellers?.items?.length === 0;

  if (!isLoading && isEmptyData) {
    return (
      <Box
        width="100%"
        height="100%"
        minHeight="200px"
        display="flex"
        justifyContent="center"
        alignItems="center"
      >
        <Box
          borderRadius="medium"
          backgroundColor="surface.background.gray.subtle"
          display="inline-flex"
          flexDirection="column"
          elevation="midRaised"
          alignItems="center"
          padding="16px"
          marginY="20px"
        >
          <FilePlusIcon color="surface.icon.gray.normal" size="large" />
          <Text weight="semibold" marginBottom="spacing.3" marginTop="spacing.3" size="large">
            No reseller added
          </Text>
          <Text weight="regular" size="small" textAlign="center">
            <i>
              To add resellers, click on add reseller
              <br />
              button
            </i>
          </Text>
        </Box>
        {isOpen && selectedTab === 'resellers' && (
          <LinkingResellerModal closeModal={closeModal} program={program} refetchQuery={refetch} />
        )}
      </Box>
    );
  }

  return (
    <Box marginTop="20px">
      <Table
        data={tableData}
        isLoading={isLoading}
        pagination={
          <TablePagination
            defaultPageSize={25}
            onPageChange={handlePageChange}
            showPageSizePicker
            onPageSizeChange={handlePageSizeChange}
          />
        }
      >
        {(orderItems) => {
          return (
            <>
              <TableHeader>
                <TableHeaderRow>
                  {resellerListColumns.map(({ title }) => (
                    <TableHeaderCell key={title}>{title}</TableHeaderCell>
                  ))}
                </TableHeaderRow>
              </TableHeader>
              <TableBody>
                {orderItems.map((order, index) => (
                  <TableRow key={index} item={order}>
                    {resellerListColumns.map(({ title, value }) => (
                      <TableCell key={title}>{value(order)}</TableCell>
                    ))}
                  </TableRow>
                ))}
              </TableBody>
            </>
          );
        }}
      </Table>
      {isOpen && selectedTab === 'resellers' && (
        <LinkingResellerModal closeModal={closeModal} refetchQuery={refetch} program={program} />
      )}
    </Box>
  );
};

export default connect((state) => ({
  mode: state.session?.mode,
  merchantId: state.session?.user?.current,
}))(LinkedResellers);
