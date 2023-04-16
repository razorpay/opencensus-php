import { Box, ChevronDownIcon, ChevronRightIcon, Heading, Text } from '@razorpay/blade/components';
import Collapsible from 'common/components/Collapsible';
import { useResizeLayout } from 'common/hooks/useResizeLayout';
import React, { useEffect, useState } from 'react';
import { Queries } from './FAQQueries';
import {
  CollapsibleIcon,
  StyledSectionContent,
  TabContent,
  TabList,
  TabListItem,
  TabOrder,
  TabQueryItem,
} from './styled';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { connect } from 'react-redux';

const FAQs = (props): JSX.Element => {
  const [activeQuery, setActiveQuery] = useState<number>(0);
  const isDeviceUnderBreakpoint = useResizeLayout({ innerWidth: 940 });
  const QueryComponent =
    activeQuery === -1 ? Queries[1].Component : Queries[activeQuery]?.Component;

  useEffect(() => {
    if (!isDeviceUnderBreakpoint && activeQuery === -1) {
      setActiveQuery(0);
    }
  }, [isDeviceUnderBreakpoint]);

  const instrumentQueryClick = () => {
    const { settlement } = props;

    analyticsTrackWithUserInfo({
      objectName: 'Settlements FQA',
      actionName: 'clicked',
      screen: 'Settlements',
      properties: {
        page: 'Details View',
        settlements_experiment_name: 'v2',
        settlementId: settlement.id,
        settlementStatus: settlement.status,
        sessionId: window?.session_id ? window.session_id : undefined,
      },
    });
  };

  return (
    <Box
      display="flex"
      flexDirection="column"
      gap="spacing.5"
      backgroundColor="surface.background.level2.lowContrast"
      padding={{ base: ['10px', 'spacing.5'], m: ['spacing.4', 'spacing.9', 'spacing.9'] }}
    >
      <Heading weight="bold" size="medium">
        FAQs
      </Heading>
      {isDeviceUnderBreakpoint ? (
        <StyledSectionContent isDeviceUnderBreakpoint>
          <TabList
            isFirstActive={activeQuery === 0}
            isLastActive={activeQuery === Queries.length - 1}
            isDeviceUnderBreakpoint
          >
            {Queries.map(
              ({ query, Component }, index): JSX.Element => (
                <>
                  <TabListItem
                    key={`tab_${index}`}
                    onClick={() => {
                      setActiveQuery((prevState) => (prevState === index ? -1 : index));
                      instrumentQueryClick();
                    }}
                    isActive={index === activeQuery}
                    activeTab={activeQuery}
                    isDeviceUnderBreakpoint
                  >
                    <TabQueryItem>
                      <TabOrder />
                      <Text size="medium" type={index === activeQuery ? 'normal' : 'subtle'}>
                        {query}
                      </Text>
                    </TabQueryItem>
                    <CollapsibleIcon open={index === activeQuery}>
                      <ChevronDownIcon
                        size="large"
                        color={
                          index === activeQuery
                            ? 'action.icon.link.default'
                            : 'feedback.icon.neutral.lowContrast'
                        }
                      />
                    </CollapsibleIcon>
                  </TabListItem>
                  <Collapsible open={index === activeQuery}>
                    <TabContent isLast={index === Queries.length - 1} isDeviceUnderBreakpoint>
                      <Component isMobile={isDeviceUnderBreakpoint} />
                    </TabContent>
                  </Collapsible>
                </>
              ),
            )}
          </TabList>
        </StyledSectionContent>
      ) : (
        <StyledSectionContent>
          <TabList
            isFirstActive={activeQuery === 0}
            isLastActive={activeQuery === Queries.length - 1}
          >
            {Queries.map(
              (each, index): JSX.Element => (
                <TabListItem
                  key={`tab_${index}`}
                  onClick={() => {
                    setActiveQuery(index);
                    instrumentQueryClick();
                  }}
                  isActive={index === activeQuery}
                  activeTab={activeQuery}
                >
                  <TabQueryItem>
                    <TabOrder />
                    <Text size="medium" type={index === activeQuery ? 'normal' : 'subtle'}>
                      {each.query}
                    </Text>
                  </TabQueryItem>
                  <ChevronRightIcon
                    size="large"
                    color={
                      index === activeQuery
                        ? 'action.icon.link.default'
                        : 'feedback.icon.neutral.lowContrast'
                    }
                  />
                </TabListItem>
              ),
            )}
          </TabList>
          <TabContent>
            <QueryComponent isMobile={isDeviceUnderBreakpoint} />
          </TabContent>
        </StyledSectionContent>
      )}
    </Box>
  );
};

export default connect((state) => ({ settlement: state.settlement.settlement }), null)(FAQs);
