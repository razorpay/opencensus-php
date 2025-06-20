import React, { useState, useEffect, useRef, useMemo, useCallback } from 'react';
import styled from 'styled-components';
import {
  Box,
  StepGroup,
  StepItem,
  Text,
  Button,
  Heading,
  Link,
  TrashIcon,
  StepItemIndicator,
  ProgressBar,
  Fade,
} from '@razorpay/blade/components';

import Form from 'common/new-ui/Form';

const SideNavContainer = styled.div`
  width: 250px;
  height: 280px;
  & > div {
    height: 100%;
    display: flex;
    flex-direction: column;
  }
  & > div > div {
    flex: 1;
  }
`;

const FormWizard = ({ tabsData, allValid, errors, submitText, isLoading, onSubmit, onClose }) => {
  const TAB_TITLES: Array<Array<string>> = useMemo(
    () => tabsData.map((tab) => [tab.name.boldText, tab.name.regularText].join(' ')),
    [tabsData],
  );

  const [currentTab, setCurrentTab] = useState(0);
  const [validTabs, setValidTabs] = useState(tabsData.map(() => allValid || false));

  const toggleDisableState = () => {
    const isErrorPresent = tabsData[currentTab].fields.some((field) => field in errors);
    const currentTabStatus = !isErrorPresent;

    if (validTabs[currentTab] !== currentTabStatus) {
      const newValidTabs = [...validTabs];
      newValidTabs[currentTab] = currentTabStatus;
      newValidTabs[newValidTabs.length - 1] = newValidTabs
        .slice(0, newValidTabs.length - 1)
        .every((val) => val);
      setValidTabs(newValidTabs);
    }
  };

  useEffect(() => {
    toggleDisableState();
  }, []);

  useEffect(() => {
    toggleDisableState();
  }, [errors]);

  const changeTab = (step) => {
    setCurrentTab((prevTab) => prevTab + step);
  };

  const handleNext = async () => {
    if (currentTab === tabsData.length - 1) {
      await onSubmit();
    } else {
      setCurrentTab(currentTab + 1);
    }
  };

  const renderForm = () => {
    const selectedTab = tabsData[currentTab];
    return (
      <Fade motionTriggers={['in-view']} delay={'gentle'}>
        <Box marginBottom="spacing.9">
          <Box display="flex" flexDirection="column" marginBottom="spacing.6">
            <Heading weight="regular" size="large">
              <Heading weight="semibold" display="inline-flex" size="large">
                {selectedTab.name.boldText}
              </Heading>
              {' ' + selectedTab.name.regularText}
            </Heading>
            <Text color="interactive.text.gray.muted" marginTop="spacing.3">
              {selectedTab.helpText}
            </Text>
          </Box>
          {selectedTab.customRender ? (
            selectedTab.render()
          ) : (
            <Box
              borderRadius="large"
              padding="24px"
              borderColor="surface.border.gray.muted"
              width="450px"
            >
              {selectedTab.render()}
            </Box>
          )}
        </Box>
      </Fade>
    );
  };

  const renderSideNav = useCallback(
    () => (
      <StepGroup orientation="vertical" size="medium">
        {TAB_TITLES.map((title, index) => (
          <StepItem
            key={title}
            title={title}
            isSelected={index === currentTab}
            onClick={() => setCurrentTab(index)}
            titleColor={
              index === currentTab ? 'surface.text.primary.normal' : 'feedback.text.neutral.intense'
            }
            isDisabled={currentTab !== index && !validTabs[index]}
            marker={
              <StepItemIndicator
                color={
                  index === currentTab ? 'primary' : index < currentTab ? 'positive' : 'neutral'
                }
              />
            }
          />
        ))}
      </StepGroup>
    ),
    [currentTab, validTabs, TAB_TITLES],
  );

  const isNextDisabled = !validTabs[currentTab] || isLoading;

  return (
    <Box
      zIndex="1000"
      width="100vw"
      height="100vh"
      top="spacing.0"
      left="spacing.0"
      position="fixed"
      backgroundColor="surface.background.gray.intense"
      display="flex"
      flexDirection="row"
    >
      <Box position="absolute" zIndex="10000" top="0px" right="0px" left="0px">
        <ProgressBar
          accessibilityLabel="Label"
          value={((currentTab + 1) * 100) / TAB_TITLES.length}
          color="positive"
          size="medium"
          showPercentage={false}
        />
      </Box>
      <Box position="absolute" top="26px" right="32px" zIndex="10000">
        <Link onClick={onClose} icon={TrashIcon} size="small">
          Discard
        </Link>
      </Box>
      <Box
        width="350px"
        minWidth="350px"
        backgroundColor="surface.background.cloud.subtle"
        paddingTop="100px"
        paddingLeft="50px"
        display="flex"
        flexDirection="column"
      >
        <Heading size="large" color="surface.text.gray.muted" weight="regular" marginBottom="24px">
          <Heading
            weight="semibold"
            display="inline-flex"
            color="surface.text.gray.subtle"
            size="large"
          >
            New
          </Heading>{' '}
          Program
        </Heading>
        <SideNavContainer>{renderSideNav()}</SideNavContainer>
      </Box>
      <Box display="flex" flexDirection="column" width="100%" height="100%">
        <Box
          display="flex"
          flexGrow="1"
          justifyContent="center"
          alignItems={tabsData[currentTab].centerAlign ? 'center' : 'flex-start'}
          overflowY="scroll"
          marginTop={tabsData[currentTab].centerAlign ? '0px' : '50px'}
          paddingTop={tabsData[currentTab].centerAlign ? '0px' : '50px'}
        >
          <Form>{renderForm()}</Form>
        </Box>
        <Box
          paddingY="10px"
          display="flex"
          justifyContent="center"
          borderTopColor="surface.border.gray.muted"
          height="90px"
          width="100%"
        >
          <Box
            width="450px"
            display="flex"
            justifyContent="flex-end"
            height="100%"
            alignItems="center"
          >
            {currentTab !== 0 && (
              <Button
                variant="secondary"
                size="medium"
                marginRight="16px"
                onClick={() => changeTab(-1)}
                isDisabled={isLoading}
              >
                Back
              </Button>
            )}
            <Button onClick={handleNext} isLoading={isLoading} isDisabled={isNextDisabled}>
              {currentTab === tabsData.length - 1 ? submitText : 'Next'}
            </Button>
          </Box>
        </Box>
      </Box>
    </Box>
  );
};

export default FormWizard;
