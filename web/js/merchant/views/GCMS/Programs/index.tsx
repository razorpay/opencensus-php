import React, { useState, useCallback } from 'react';
import { Box, Text, Title } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';

import { ModeT } from 'common/services/mode';
import Spinner from 'common/ui/Spinner';
import EmptyList from 'merchant/components/EmptyList';
import ProgramsListItem from 'merchant/views/GCMS/Programs/ProgramsListItem';
import { fetchPrograms, LIST_FETCH_BATCH_SIZE } from 'merchant/views/GCMS/Programs/queries';
import { Program as ProgramType } from 'merchant/views/GCMS/Programs/types';
import Wrapper from 'merchant/views/GCMS/shared/Wrapper';
import { ListApiResponse } from 'merchant/views/GCMS/shared/types';
import Pagination from 'merchant/views/Settlements/v2/components/Pagination';

const Programs = ({ mode }: { mode: ModeT }) => {
  const navigate = useNavigate();
  const [paginationState, setPaginationState] = useState({
    skip: 0,
    count: LIST_FETCH_BATCH_SIZE,
  });
  const { isLoading, data: programs } = useQuery<ListApiResponse<ProgramType>, Error>({
    queryKey: ['gcms:programs', mode, paginationState],
    queryFn: () => fetchPrograms({ ...paginationState, mode }),
  });

  const next = useCallback(() => {
    const skipValue = paginationState.skip + paginationState.count;
    setPaginationState({
      skip: skipValue,
      count: paginationState.count,
    });
  }, [paginationState.count, paginationState.skip]);

  const prev = useCallback(() => {
    const skipValue = paginationState.skip - paginationState.count;
    setPaginationState({
      skip: skipValue,
      count: paginationState.count,
    });
  }, [paginationState.count, paginationState.skip]);

  return (
    <Wrapper>
      <div className="tabbed-container">
        <Box>
          <Title color="surface.text.subtle.lowContrast">Programs</Title>
        </Box>
        <div className="content">
          {isLoading ? (
            <div className="page-spinner-container">
              <Spinner center={undefined} />
            </div>
          ) : (
            <Box
              maxWidth={{
                l: '1200px',
                m: '100%',
                s: '100%',
              }}
            >
              <Box
                marginTop="spacing.4"
                padding="spacing.4"
                display="flex"
                flex={1}
                flexDirection="row"
                flexWrap="wrap"
              >
                {/* @ts-expect-error array-undefined-check */}
                {Array.isArray(programs?.items) && programs.items.length > 0 ? (
                  programs?.items.map((program) => (
                    <ProgramsListItem
                      key={program.id}
                      program={program}
                      onClick={() => navigate(`/gcms/programs/${program.id}`)}
                    />
                  ))
                ) : (
                  <Box width="100%" height="100%">
                    <EmptyList
                      description={
                        <React.Fragment>
                          <div>There are no programs yet!!</div>
                          <div>Start creating new programs now.</div>
                        </React.Fragment>
                      }
                    />
                  </Box>
                )}
              </Box>
              <Box>
                <Box position="absolute" paddingLeft="spacing.6" paddingTop="spacing.1">
                  <Text size="small" color="surface.text.subdued.lowContrast">{`Total ${
                    programs?.total_count || 0
                  } records`}</Text>
                </Box>
                <Pagination
                  next={next}
                  prev={prev}
                  listData={programs?.items || []}
                  skip={paginationState.skip}
                  count={paginationState.count}
                />
              </Box>
            </Box>
          )}
        </div>
      </div>
    </Wrapper>
  );
};

export default connect((state) => ({
  mode: state.session?.mode,
  merchantId: state.session?.user?.current,
}))(Programs);
