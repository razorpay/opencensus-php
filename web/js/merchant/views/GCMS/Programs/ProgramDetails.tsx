import React, { memo } from 'react';
import { Box, ChevronLeftIcon, Divider, Heading, Link, Text } from '@razorpay/blade/components';
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

const ProgramDetails: React.FC = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const { programId } = useParams<{ programId: string }>();

  const mode = 'test';
  const { isLoading, data: program } = useQuery({
    queryKey: ['wallet:programs:id', mode, programId],
    queryFn: () => fetchProgramById({ mode, programId }),
  });

  const handleGoBack = () => {
    const { prevPath = '' } = location?.state ?? {};

    if (prevPath) {
      navigate(-1);
    }
    navigate('/gcms/programs');
  };

  const contentSections = program ? getProgramContentSections(program) : [];
  const denominationSections = program ? getProgramDenominationSections(program) : [];

  return (
    <Wrapper>
      <div className="tabbed-container">
        <Box padding={['spacing.4', 'spacing.0']}>
          <Link variant="button" icon={ChevronLeftIcon} iconPosition="left" onClick={handleGoBack}>
            Go back
          </Link>
        </Box>
        {!isLoading && program && (
          <Box
            backgroundColor="surface.background.level2.lowContrast"
            maxWidth={{
              l: '1200px',
              m: '100%',
              s: '100%',
            }}
          >
            <ProgramHeaderSection
              program={program}
              containerProps={{ height: '116px' }}
              imageProps={{ height: '60px', width: '94px' }}
            />
          </Box>
        )}

        <Box paddingTop="spacing.8" display="flex" flex={1} flexDirection="column">
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
                <Box>
                  <Heading weight="bold">Program Details</Heading>
                </Box>
                <Box paddingTop="spacing.4" display="flex" flexDirection="row">
                  <Box width="342px" height="216px">
                    <img
                      src={program.policies?.image_link}
                      width="100%"
                      height="100%"
                      alt={program.name}
                    />
                  </Box>
                  <Box paddingLeft="spacing.8">
                    <Box>
                      <Heading size="small" weight="bold">
                        Basic Details
                      </Heading>
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
                            <Text color="surface.text.subtle.lowContrast">{section.name}</Text>
                          </Box>
                          <Box>
                            <Text color="surface.text.subtle.lowContrast" weight="bold">
                              {capitalize(section.value)}
                            </Text>
                          </Box>
                        </Box>
                      ))}
                    </Box>
                    <Box padding={['spacing.8', 'spacing.0']}>
                      <Divider />
                    </Box>
                    <Box padding={['spacing.0', 'spacing.0', 'spacing.8']}>
                      <Box>
                        <Heading size="small" weight="bold">
                          Denomination
                        </Heading>
                      </Box>
                      <Box paddingTop="spacing.4">
                        {denominationSections.map((section) => (
                          <Box
                            key={section.name}
                            display="flex"
                            flexDirection="row"
                            paddingTop="spacing.4"
                          >
                            <Box minWidth="180px">
                              <Text color="surface.text.subtle.lowContrast">{section.name}</Text>
                            </Box>
                            {section.name === 'Denomination' ? (
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
                                      borderColor="surface.border.normal.lowContrast"
                                    >
                                      <Text color="surface.text.subtle.lowContrast" weight="bold">
                                        {getFormattedAmountNew(denomination, true)}
                                      </Text>
                                    </Box>
                                  ))
                                ) : (
                                  <Text color="surface.text.subtle.lowContrast" weight="bold">
                                    -
                                  </Text>
                                )}
                              </Box>
                            ) : (
                              <Box>
                                <Text weight="bold">{capitalize(section.value as string)}</Text>
                              </Box>
                            )}
                          </Box>
                        ))}
                      </Box>
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
