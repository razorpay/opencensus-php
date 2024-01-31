import { useQuery } from '@tanstack/react-query';
import React, { useState } from 'react';
import {
  LIST_FETCH_BATCH_SIZE,
  fetchProgramsForReseller,
} from 'merchant/views/GCMS/Resellers/queries';
import { Box } from '@razorpay/blade/components';
import ProgramsListItem from 'merchant/views/GCMS/Programs/ProgramsListItem';
import EmptyList from 'merchant/components/EmptyList';
import Pagination from 'merchant/views/Settlements/v2/components/Pagination';
import { useParams } from 'react-router-dom';
import Spinner from 'common/ui/Spinner';
import { ModeT } from 'common/services/mode';

const ResellerPrograms = ({ mode }: { mode: ModeT }) => {
  const { resellerId } = useParams<{ resellerId: string }>();
  const [skip, setSkip] = useState(0);

  const { isLoading, data: programs } = useQuery({
    queryKey: ['wallet:reseller-programs', skip, resellerId],
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
          <Spinner center />
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
            {Array.isArray(programs?.items) && programs?.items?.length > 0 ? (
              programs?.items?.map((program) => (
                <ProgramsListItem key={program.id} program={program} />
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
          <Pagination
            next={handleNext}
            prev={handlePrev}
            listData={programs?.items || []}
            skip={skip}
            count={LIST_FETCH_BATCH_SIZE}
          />
        </Box>
      )}
    </div>
  );
};

export default ResellerPrograms;
