import React, { useState, useEffect } from 'react';
import { Box, Button, PlusIcon, Spinner, ToastContainer } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';

import { ModeT } from 'common/services/mode';
import EmptyList from 'merchant/components/EmptyList';
import ProgramsListItem from 'merchant/views/GCMS/Programs/ProgramsListItem';
import Wrapper from 'merchant/views/GCMS/shared/Wrapper';
import Pagination from '@dashboards/payments/views/GCMS/Programs/Pagination';
import useFetchPrograms from './hooks/useFetchPrograms';

import { trackProgramsCardClicked, trackProgramsPageLoadSuccess } from './events';
import CreateProgram from './CreateProgram';
import PageLayout from 'merchant/views/GCMS/shared/PageLayout';

const Programs = ({ mode, merchantId }: { mode: ModeT }) => {
  const [isOpen, setIsOpen] = useState(false);
  const navigate = useNavigate();
  const {
    isLoading,
    programs,
    next,
    prev,
    skip,
    count,
    changePageSize,
    programImages,
    isImageLoading,
    refetch,
  } = useFetchPrograms({
    mode,
  });
  const startProgramCreationFlow = () => {
    setIsOpen(true);
  };

  return (
    <Wrapper>
      <PageLayout
        title="Gift Card Programs"
        subtitle="A gift card program is a set of rules that determines how your gift cards work for different resellers."
        leading={
          <Button
            icon={PlusIcon}
            variant="primary"
            onClick={startProgramCreationFlow}
            testID="program-creation-flow"
            size="medium"
          >
            New Program
          </Button>
        }
      >
        {isLoading ? (
          <Box display="flex" alignItems="center" justifyContent="center" minHeight="200px">
            <Spinner
              accessibilityLabel="loading programs"
              label="loading..."
              labelPosition="bottom"
            />
          </Box>
        ) : (
          <Box backgroundColor="#F9FAFC" padding="spacing.5" borderRadius="large">
            <Box display="flex" rowGap="10px" columnGap="10px" flexWrap="wrap">
              {Array.isArray(programs?.items) && programs.items.length > 0 ? (
                programs?.items.map((program) => (
                  <ProgramsListItem
                    key={program.id}
                    program={program}
                    programImages={programImages}
                    isProgramImagesLoading={isImageLoading}
                    onClick={() => {
                      trackProgramsCardClicked({
                        programId: program?.id,
                        programName: program?.name,
                      });
                      navigate(`/gcms/programs/${program.id}`);
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
            {programs?.items?.length && (
              <Box marginTop="spacing.4">
                <Pagination
                  next={next}
                  prev={prev}
                  listData={programs?.items || []}
                  skip={skip}
                  count={count}
                  changePageSize={changePageSize}
                  totalCount={programs?.total_count}
                />
              </Box>
            )}
          </Box>
        )}
      </PageLayout>
      <Box zIndex="10001">
        <ToastContainer />
      </Box>
      {isOpen && (
        <CreateProgram
          submitText="Create Program"
          merchantId={merchantId}
          mode={mode}
          onClose={() => setIsOpen(false)}
          refetch={refetch}
        />
      )}
    </Wrapper>
  );
};

export default connect((state) => ({
  mode: state.session?.mode,
  merchantId: state.session?.user?.current,
}))(Programs);
