import React, { memo, useEffect } from 'react';
import { Box, ChevronLeftIcon, Divider, Link, Text } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { useLocation, useNavigate, useParams } from 'react-router-dom';

import Spinner from 'common/ui/Spinner';
import { capitalize, getFormattedAmountNew } from 'common/utils/rzp-utils';
import EmptyList from 'merchant/components/EmptyList';
import {
  getProgramContentSections,
  getProgramDenominationSections,
} from 'merchant/views/GCMS/Programs/constants';
import { fetchProgramById } from 'merchant/views/GCMS/Programs/queries';
import ProgramHeaderSection from 'merchant/views/GCMS/shared/ProgramHeaderSection';
import Wrapper from 'merchant/views/GCMS/shared/Wrapper';

import { trackProgramsDetailsPageLoadSuccess } from './events';

const ProgramDetails: React.FC = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const { programId } = useParams<{ programId: string }>();

  const mode = 'test';
  const { isLoading, data: program } = useQuery({
    queryKey: ['gcms:programs:id', mode, programId],
    queryFn: () => fetchProgramById({ mode, programId }),
  });

  const handleGoBack = () => {
    const { prevPath = '' } = location?.state ?? {};
    if (prevPath) {
      return navigate(-1);
    }
    return navigate('/gcms/programs');
  };

  const contentSections = program ? getProgramContentSections(program) : [];
  const denominationSections = program ? getProgramDenominationSections(program) : [];
  useEffect(() => {
    if (program?.id)
      trackProgramsDetailsPageLoadSuccess({ programId: program?.id, programName: program?.name });
  }, [program]);

  return (
    <Wrapper>
      <div className="tabbed-container">
        <Box padding={['spacing.4', 'spacing.0']}>
          <Link variant="button" icon={ChevronLeftIcon} iconPosition="left" onClick={handleGoBack}>
            Go back
          </Link>
        </Box>
        {!isLoading && program && (
          <Box backgroundColor="surface.background.gray.intense">
            <ProgramHeaderSection
              program={program}
              containerProps={{ height: '116px' }}
              imageProps={{ height: '60px', width: '94px' }}
            />
          </Box>
        )}

        <Box paddingTop="spacing.7" display="flex" flex={1} flexDirection="column">
          <div className="content">
            {isLoading ? (
              <div className="page-spinner-container">
                <Spinner center={undefined} />
              </div>
            ) : program ? (
              <Box
                padding="spacing.6"
                maxWidth={{
                  l: '1200px',
                  m: '100%',
                  s: '100%',
                }}
              >
                <Box
                  paddingTop="spacing.4"
                  display="flex"
                  flexDirection="row"
                  flexWrap="wrap-reverse"
                >
                  <Box
                    maxWidth={{
                      l: '656px',
                      m: '100%',
                      s: '100%',
                    }}
                    minWidth="464px"
                    flex="1"
                  >
                    <Box>
                      <Text weight="semibold" size="large">
                        Program Details
                      </Text>
                    </Box>
                    <Box paddingTop="spacing.4">
                      {contentSections.map((section) => (
                        <Box
                          key={section.name}
                          display="flex"
                          flexDirection="row"
                          paddingTop="spacing.4"
                        >
                          <Box minWidth="180px">
                            <Text color="surface.text.gray.subtle">{section.name}</Text>
                          </Box>
                          <Box>
                            <Text color="surface.text.gray.subtle" weight="semibold">
                              {capitalize(section.value)}
                            </Text>
                          </Box>
                        </Box>
                      ))}
                    </Box>
                  </Box>
                  <Box>
                    <Box padding={['spacing.9', 'spacing.5', 'spacing.5', 'spacing.0']}>
                      <Box width="312px" height="182px" elevation="highRaised">
                        <img
                          src={program.policies?.image_link}
                          width="100%"
                          height="100%"
                          alt={program.name}
                        />
                      </Box>
                    </Box>
                  </Box>
                </Box>
                <Box>
                  <Box padding={['spacing.8', 'spacing.8', 'spacing.8', 'spacing.0']}>
                    <Divider />
                  </Box>
                  <Box>
                    <Box>
                      <Text weight="semibold" size="large">
                        Denomination
                      </Text>
                    </Box>
                    <Box paddingTop="spacing.4">
                      {denominationSections?.map((section) => (
                        <Box
                          key={section.name}
                          display="flex"
                          flexDirection="row"
                          paddingTop="spacing.4"
                        >
                          {section.name === '' ? (
                            <Box
                              display="flex"
                              flexDirection="row"
                              alignItems="center"
                              flexWrap="wrap"
                            >
                              {section.value ? (
                                (section.value as number[]).map((denomination) => (
                                  <Box
                                    key={denomination}
                                    margin={['spacing.0', 'spacing.4', 'spacing.4', 'spacing.0']}
                                    padding={['spacing.3', 'spacing.6']}
                                    borderRadius="small"
                                    borderWidth="thinner"
                                    borderColor="surface.border.gray.muted"
                                  >
                                    <Text color="surface.text.gray.subtle" weight="semibold">
                                      {getFormattedAmountNew(denomination, true)}
                                    </Text>
                                  </Box>
                                ))
                              ) : (
                                <Text color="surface.text.gray.subtle" weight="semibold">
                                  -
                                </Text>
                              )}
                            </Box>
                          ) : (
                            <Box>
                              <Text weight="semibold">
                                {capitalize(section.value as unknown as string)}
                              </Text>
                            </Box>
                          )}
                        </Box>
                      ))}
                    </Box>
                  </Box>
                </Box>
              </Box>
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
          </div>
        </Box>
      </div>
    </Wrapper>
  );
};

export default memo(ProgramDetails);
