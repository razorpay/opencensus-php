import React, { useState } from 'react';
import {
  Box,
  Heading,
  Button,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableToolbar,
  TableToolbarActions,
  TablePagination,
  TablePaginationProps,
  TableRow,
  TableCell,
  Badge,
  Link,
  RepeatIcon,
  EditIcon,
  TrashIcon,
  PlusIcon,
  Spinner,
} from '@razorpay/blade/components';

import InviteMember from './InviteMember';
import { roleToDisplayMap, statusColorMap, statusToDisplayMap } from './constants';
import { TableItemT } from './types';
import useManageTeamPosEkyc from './useManageTeamPosEkyc';

const ManageTeamContainer = () => {
  const [isCtaActionPending, setIsCtaActionPending] = useState(false);
  const {
    isMemberModalOpen,
    openInviteModal,
    openEditModal,
    closeModal,
    formik,
    posAgentUsers,
    resendSms,
    deleteMember,
    isLoading,
    isError,
    renderConfirmModal,
    dialCode,
  } = useManageTeamPosEkyc();

  const [pagination, setPagination] = useState<{
    page: number;
    pageSize: number;
  }>({ page: 1, pageSize: 10 });

  const renderTableToolbar = () => {
    const startCount = (pagination.page - 1) * pagination.pageSize + 1;
    const fetchedCount = posAgentUsers.length ?? 0;
    const endCount = startCount + fetchedCount - 1;
    const title = `Showing ${startCount}-${endCount} [Items]`;

    return (
      <TableToolbar title={title}>
        <TableToolbarActions>
          <Box minWidth="200px">
            <Button
              iconPosition="left"
              icon={PlusIcon}
              marginRight="spacing.4"
              variant="primary"
              onClick={openInviteModal}
              isFullWidth
            >
              Invite New Member
            </Button>
          </Box>
        </TableToolbarActions>
      </TableToolbar>
    );
  };

  const renderTablePagination = () => {
    const handlePageSizeChange = ({ pageSize }: { pageSize: number }): void => {
      setPagination((prev) => ({ ...prev, pageSize }));
    };

    const handlePageChange = ({ page }: { page: number }): void => {
      setPagination((prev) => ({ ...prev, page: page + 1 }));
    };
    return (
      <TablePagination
        defaultPageSize={pagination.pageSize as TablePaginationProps['defaultPageSize']}
        onPageChange={handlePageChange}
        onPageSizeChange={handlePageSizeChange}
        showPageNumberSelector
        showPageSizePicker
      />
    );
  };

  const renderCTAs = (userData: TableItemT): JSX.Element => {
    const isInvitationPending = !userData.isConfirmed;

    const onResendSmsClick = async (): Promise<void> => {
      setIsCtaActionPending(true);
      await resendSms({ id: userData.id, contactMobile: userData.contactMobile });
      setIsCtaActionPending(false);
    };

    const onEditClick = (): void => {
      openEditModal(userData);
    };

    const onDeleteClick = async (): Promise<void> => {
      setIsCtaActionPending(true);
      await deleteMember({ id: userData.id, isConfirmed: userData.isConfirmed });
      setIsCtaActionPending(false);
    };

    return (
      <Box
        display="flex"
        gap="spacing.5"
        alignItems="center"
        marginRight="spacing.4"
        testID={`${userData.id}-ctas`}
      >
        {isInvitationPending && (
          <Link
            size="small"
            variant="button"
            icon={RepeatIcon}
            iconPosition="left"
            onClick={onResendSmsClick}
            isDisabled={isCtaActionPending}
          >
            Resend SMS
          </Link>
        )}

        {isInvitationPending && (
          <Link
            size="small"
            variant="button"
            icon={EditIcon}
            iconPosition="left"
            onClick={onEditClick}
            isDisabled={isCtaActionPending}
          >
            Edit
          </Link>
        )}

        <Link
          size="small"
          variant="button"
          icon={TrashIcon}
          iconPosition="left"
          onClick={onDeleteClick}
          isDisabled={isCtaActionPending}
        >
          Delete
        </Link>
      </Box>
    );
  };

  if (isLoading) {
    return (
      <Box
        width="100%"
        height="100%"
        minHeight="500px"
        backgroundColor="surface.background.gray.intense"
        display="flex"
        justifyContent="center"
        alignItems="center"
      >
        <Spinner accessibilityLabel="Loading manage team" size="xlarge" />
      </Box>
    );
  }

  if (isError) {
    return (
      <Box
        paddingX="spacing.8"
        paddingY="spacing.5"
        backgroundColor="surface.background.gray.intense"
        display="flex"
        justifyContent="center"
        alignItems="center"
      >
        <Heading size="medium">Failed to fetch data. Please try again later</Heading>
      </Box>
    );
  }

  return (
    <Box
      paddingX="spacing.8"
      paddingY="spacing.5"
      backgroundColor="surface.background.gray.intense"
    >
      <Heading size="medium">Add your POS Partner Agents Now!</Heading>
      <Table
        selectionType="none"
        rowDensity="normal"
        onSortChange={() => {}}
        pagination={renderTablePagination()}
        sortFunctions={{
          NAME: (array) => array.sort((a, b) => a.name.localeCompare(b.name)),
          ROLE: (array) => array.sort((a, b) => a.role.localeCompare(b.role)),
          STATUS: (array) => array.sort((a, b) => Number(a.isConfirmed) - Number(b.isConfirmed)),
        }}
        toolbar={renderTableToolbar()}
        data={{
          nodes: posAgentUsers,
        }}
        gridTemplateColumns="repeat(5, auto)"
      >
        {(tableData) => (
          <>
            <TableHeader>
              <TableHeaderRow>
                <TableHeaderCell headerKey="NAME">Name</TableHeaderCell>
                <TableHeaderCell headerKey="NUMBER">Number</TableHeaderCell>
                <TableHeaderCell headerKey="ROLE">Role</TableHeaderCell>
                <TableHeaderCell headerKey="STATUS">Status</TableHeaderCell>
                <TableHeaderCell headerKey="CTAs"> </TableHeaderCell>
              </TableHeaderRow>
            </TableHeader>
            <TableBody>
              {tableData.map((tableItem: TableItemT) => {
                const status = tableItem.isConfirmed ? 'onboarded' : 'awaiting';
                return (
                  <TableRow key={tableItem.id} item={tableItem}>
                    <TableCell>{tableItem.name}</TableCell>
                    <TableCell>{tableItem.contactMobile.replace(dialCode, '')}</TableCell>
                    <TableCell>{roleToDisplayMap[tableItem.role]}</TableCell>
                    <TableCell>
                      <Badge
                        size="medium"
                        emphasis="subtle"
                        color={statusColorMap[status]}
                        testID={`${tableItem.id}-status`}
                      >
                        {statusToDisplayMap[status]}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      {/* no CTAs to be shown for confirmed team members yet. In V2 there will be a show details CTA */}
                      {renderCTAs(tableItem)}
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </>
        )}
      </Table>

      <InviteMember isOpen={isMemberModalOpen} closeModal={closeModal} formik={formik} />
      {renderConfirmModal()}
    </Box>
  );
};

export default ManageTeamContainer;
