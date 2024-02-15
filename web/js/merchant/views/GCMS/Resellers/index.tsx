import React, { useState } from 'react';
import { Title, Box, Text, Badge } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import { ModeT } from 'common/services/mode';
import Spinner from 'common/ui/Spinner';
import TableBody from 'common/ui/TableBody';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import { EmptyListWithTableRow } from 'merchant/components/EmptyList';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import Wrapper from 'merchant/views/GCMS/shared/Wrapper';
import { RESELLERS_STATUS } from 'merchant/views/GCMS/shared/constants';
import Pagination from 'merchant/views/Settlements/v2/components/Pagination';

import ResellersFilter from './ResellersFilters';
import { fetchResellers, LIST_FETCH_BATCH_SIZE } from './queries';

const merchant_name = {
  title: 'Reseller Name',
  value: (item) => (
    <NavLink key={item.merchant_Id} to={`${item.merchant_id}`}>
      {item.merchant_name}
    </NavLink>
  ),
};
const merchant_id = {
  title: 'Reseller ID',
  value: (item) => <Text>{item.merchant_id}</Text>,
};
const eligible_programs = {
  title: 'Eligible Programs',
  value: (item) => <Text>{item.eligible_programs}</Text>,
};
const order_count = {
  title: 'Order Count',
  value: (item) => <Text>{item.order_count}</Text>,
};
const aggregate_order_value = {
  title: 'Aggregate Order Value',
  value: (item) => <Text>{getFormattedAmountNew(item.aggregate_order_value || 0, 10)}</Text>,
};

const status = {
  title: 'Status',
  value: (item) => (
    <Badge color={RESELLERS_STATUS[item.status].color}>{RESELLERS_STATUS[item.status].label}</Badge>
  ),
};

const resellerListColumns = [
  merchant_name,
  merchant_id,
  eligible_programs,
  order_count,
  aggregate_order_value,
  status,
];

const Resellers = ({ mode, merchantId }: { mode: ModeT; merchantId: string }) => {
  const [skip, setSkip] = useState(0);
  const [resellerName, setResellerName] = useState('');
  const [resellerStatus, setResellerStatus] = useState('');

  const { isLoading, data: resellers } = useQuery({
    queryKey: ['wallet:resellers', skip, resellerName, resellerStatus],
    queryFn: () => fetchResellers({ skip, resellerName, resellerStatus, mode, merchantId }),
  });
  const handleNext = () => {
    setSkip(skip + LIST_FETCH_BATCH_SIZE);
  };

  const handlePrev = () => {
    setSkip(skip - LIST_FETCH_BATCH_SIZE);
  };

  const handleSearch = ({ resellerName, status }) => {
    setResellerName(resellerName);
    setResellerStatus(status);
  };

  return (
    <Wrapper>
      <div className="tabbed-container">
        <Box marginBottom="spacing.5">
          <Title color="surface.text.subtle.lowContrast">Reseller</Title>
        </Box>

        <div className="content">
          <ResellersFilter onSearch={handleSearch} />
          {isLoading ? (
            <div className="page-spinner-container">
              <Spinner center={undefined} />
            </div>
          ) : (
            <>
              <div className="table-responsive">
                <table className="table table-hover">
                  <thead>
                    <tr>
                      {resellerListColumns.map(({ title }) => (
                        <th key={title} style={{ paddingLeft: 16 }}>
                          {title}
                        </th>
                      ))}
                    </tr>
                  </thead>
                  <TableBody
                    isLoading={isLoading}
                    colSpan={8}
                    rows={resellers?.items || []}
                    emptyTableRow={() => (
                      <EmptyListWithTableRow
                        colSpan={8}
                        description={
                          <React.Fragment>
                            <div>There are no resellers yet!!</div>
                            <div>Start creating new resellers now.</div>
                          </React.Fragment>
                        }
                      />
                    )}
                  >
                    {resellers?.items?.map((reseller) => (
                      <EntityItemRow key={reseller.merchant_id} id={reseller.merchant_id}>
                        {resellerListColumns.map(({ title, value }) => (
                          <td
                            style={{
                              paddingTop: 16,
                              paddingBottom: 16,
                              paddingLeft: 16,
                              paddingRight: 16,
                            }}
                            key={`${reseller.merchant_id} + ${title}`}
                          >
                            {value(reseller)}
                          </td>
                        ))}
                      </EntityItemRow>
                    ))}
                  </TableBody>
                </table>
              </div>
              <Box>
                <Box position="absolute" paddingLeft="spacing.5" paddingTop="spacing.1">
                  <Text size="small" color="surface.text.subdued.lowContrast">{`Total ${
                    resellers?.total_count || 0
                  } records`}</Text>
                </Box>
                <Pagination
                  next={handleNext}
                  prev={handlePrev}
                  listData={resellers?.items || []}
                  skip={skip}
                  count={LIST_FETCH_BATCH_SIZE}
                />
              </Box>
            </>
          )}
        </div>
      </div>
    </Wrapper>
  );
};

export default connect((state) => ({
  mode: state.session?.mode,
  merchantId: state.session?.user?.current,
}))(Resellers);
