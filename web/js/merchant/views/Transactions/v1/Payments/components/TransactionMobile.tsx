import React from 'react';
import { Spinner, Badge, Box, Text } from '@razorpay/blade/components';

import Pager from 'common/ui/Pager';
import { createdAtTime, amount } from 'common/ui/item';
import { titleCase } from 'common/utils/rzp-utils';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'merchant/views/Transactions/v2/common/constants';

import { paymentStatusVariantMap } from './JKBankTransactions/constants';
import { groupItemsByDate } from './JKBankTransactions/helper';
import { StyledContainer } from './JKBankTransactions/styled';

const { PAYMENTS, FAILED_PAYMENTS } = TransactionsEntityRoute;

const TransactionsMobile = (props) => {
  const { items, loading, paginate, count, skip, hasMoreData, handleNavigate } = props;

  let data = {};

  if (items?.length) {
    data = groupItemsByDate(items);
  }

  if (loading) {
    return (
      <Box
        testID="txn-spinner"
        width="100%"
        display="flex"
        justifyContent="center"
        margin={['spacing.4', 'spacing.0']}
      >
        <Spinner size="large" accessibilityLabel="" />
      </Box>
    );
  }

  const handleRowClick = ({ id }) => {
    const { hash } = window.location;

    const currentPath = window.location.pathname.includes(FAILED_PAYMENTS)
      ? FAILED_PAYMENTS
      : PAYMENTS;
    const initiatePage = TransactionsPagesMap[currentPath];

    let url = `${PAYMENTS}/${id}?init_page=${initiatePage}`;
    if (hash) {
      url += hash;
    }

    handleNavigate(url);
  };

  return (
    <div>
      {items.length ? (
        <>
          {Object.keys(data).map((key, index) => {
            return (
              <div data-testid="transactions-header" key={index}>
                <Box
                  padding={['spacing.5', 'spacing.6']}
                  backgroundColor="surface.background.gray.subtle"
                >
                  <Text color="surface.text.gray.normal">{key}</Text>
                </Box>
                {data[key].length ? (
                  <>
                    {data[key].map((item, index) => {
                      return (
                        <StyledContainer
                          onClick={() => {
                            handleRowClick({
                              id: item.id,
                            });
                          }}
                          key={index}
                        >
                          <div>
                            <div>
                              <div>{amount(item)}</div>
                              <Text
                                color="surface.text.gray.muted"
                                size="large"
                                marginTop="spacing.1"
                              >
                                {createdAtTime(item)} · {item.vpa || item?.upi?.vpa}
                              </Text>
                            </div>
                          </div>
                          <Badge color={paymentStatusVariantMap[item.status].variant}>
                            {titleCase(item.status)}
                          </Badge>
                        </StyledContainer>
                      );
                    })}
                  </>
                ) : (
                  ''
                )}
              </div>
            );
          })}
          {paginate && (
            <Pager
              count={count}
              skip={skip}
              length={items.length}
              onClick={paginate}
              hasMoreData={hasMoreData}
            />
          )}
        </>
      ) : (
        <Box
          width="100%"
          display="flex"
          margin={['spacing.4', 'spacing.0']}
          justifyContent="center"
          textAlign="center"
        >
          No payments found for the selected duration and criteria!
        </Box>
      )}
    </div>
  );
};

export default TransactionsMobile;
