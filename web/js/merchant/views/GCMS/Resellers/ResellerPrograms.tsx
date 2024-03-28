import React, { useState } from 'react';
import { Box, Text } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';

import { ModeT } from 'common/services/mode';
import Spinner from 'common/ui/Spinner';
import EmptyList from 'merchant/components/EmptyList';
import ProgramsListItem from 'merchant/views/GCMS/Programs/ProgramsListItem';
import { LIST_FETCH_BATCH_SIZE } from 'merchant/views/GCMS/Programs/queries';
import { fetchProgramsForReseller } from 'merchant/views/GCMS/Resellers/queries';
import Pagination from 'merchant/views/Settlements/v2/components/Pagination';

const ResellerPrograms = ({ mode }: { mode: ModeT }) => {
  const navigate = useNavigate();
  const { resellerId } = useParams<{ resellerId: string }>();
  const [skip, setSkip] = useState(0);

  const { isLoading, data: programs } = useQuery({
    queryKey: ['gcms:reseller-programs', skip, resellerId],
    queryFn: () => fetchProgramsForReseller({ skip, resellerId, mode }),
  });

  const handleNext = () => {
    setSkip(skip + LIST_FETCH_BATCH_SIZE);
  };

  const handlePrev = () => {
    setSkip(skip - LIST_FETCH_BATCH_SIZE);
  };

  return (
    <div>
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
            paddingTop="spacing.4"
            padding="spacing.4"
            display="flex"
            flex={1}
            flexDirection="row"
            flexWrap="wrap"
          >
            {/* @ts-expect-error array-undefined-check */}
            {Array.isArray(programs?.items) && programs?.items?.length > 0 ? (
              programs?.items?.map((program) => (
                <ProgramsListItem
                  key={program.id}
                  program={program}
                  onClick={() => {
                    navigate(`/gcms/programs/${program.id}`, {
                      state: { prevPath: location.pathname },
                    });
                  }}
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
              <Text
                size="small"
                color="surface.text.subdued.lowContrast"
              >{`Total ${programs?.total_count} records`}</Text>
            </Box>
            <Pagination
              next={handleNext}
              prev={handlePrev}
              listData={programs?.items || []}
              skip={skip}
              count={LIST_FETCH_BATCH_SIZE}
            />
          </Box>
        </Box>
      )}
    </div>
  );
};

export default ResellerPrograms;
