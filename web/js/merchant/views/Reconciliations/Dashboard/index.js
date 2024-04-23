import React, { useState } from 'react';
import { Card, CardBody, Box, Tabs, TabList, TabItem, TabPanel } from '@razorpay/blade/components';

import Detail from './Detail';
import Overview from './Overview';
import Processes from './Processes';
import Runs from './Runs';

const ReconDashboard = () => {
  const [activeProcess, setActiveProcess] = useState({});
  const [isOpen, setIsOpen] = React.useState(false);
  const [openWorkflowId, setOpenWorkflowId] = React.useState('');

  const openRunDetailModal = (id) => {
    setOpenWorkflowId(id);
    setIsOpen(true);
  };
  return (
    <Box paddingTop="spacing.1">
      <Box />
      {isOpen ? (
        <Detail
          closeDetail={() => setIsOpen(false)}
          fileWorkflowId={openWorkflowId}
          openDetail={openRunDetailModal}
          activeProcess={activeProcess}
        />
      ) : !activeProcess?.id ? (
        <Card margin="spacing.6">
          <CardBody>
            <Tabs variant="bordered" orientation="horizontal" isLazy>
              <TabList>
                <TabItem value="processes">Processes</TabItem>
                <TabItem value="runs">Runs</TabItem>
              </TabList>

              <TabPanel value="processes">
                <Box paddingTop="spacing.4">
                  <Processes openDetail={setActiveProcess} />
                </Box>
              </TabPanel>
              <TabPanel value="runs">
                <Box paddingTop="spacing.4">
                  <Runs openDetail={openRunDetailModal} />
                </Box>
              </TabPanel>
            </Tabs>
          </CardBody>
        </Card>
      ) : (
        <Overview
          activeProcess={activeProcess}
          closeDetail={setActiveProcess}
          openRunDetail={openRunDetailModal}
        />
      )}
    </Box>
  );
};

export default ReconDashboard;
