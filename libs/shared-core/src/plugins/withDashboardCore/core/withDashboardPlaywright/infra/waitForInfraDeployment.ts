async function checkArgoStatus(): Promise<boolean> {
  const assignedArgoWorkflowName = process.env.ASSIGNED_ARGO_WORKFLOW_NAME;
  try {
    const response = await fetch(
      `https://argo.dev.razorpay.in/api/v1/workflows/argo-workflows-test/${assignedArgoWorkflowName}`,
      {
        method: 'GET',
        headers: {
          Authorization: `Bearer ${process.env.ARGO_TOKEN}`,
        },
      },
    );

    if (!response.ok) {
      console.error(
        `[@libs/shared-core] Error checking deployment status for ${assignedArgoWorkflowName}:`,
        response,
      );
      process.exit(1);
    }

    const data = await response.json();

    switch (data.status.phase) {
      case 'Succeeded':
        console.info(`[@libs/shared-core] Infrastructure setup completed!`);
        return true;
      case 'Failed':
      case "Error":
        console.info(`[@libs/shared-core] Workflow ${assignedArgoWorkflowName} has failed!`);
        process.exit(1);
      default:
        console.info(
          `[@libs/shared-core] Workflow ${assignedArgoWorkflowName} has not succeeded yet!`,
        );
        return false;
    }
  } catch (error) {
    console.info(
      `[@libs/shared-core] Error checking infra status for workflow ${assignedArgoWorkflowName}:`,
      error,
    );
    return false;
  }
}

async function initializePolling(intervalMinutes: number = 2): Promise<void> {
  const avgSetupDelayMs = 5 * 60 * 1000;
  const intervalMs = intervalMinutes * 60 * 1000;
  const timeoutMs = 60 * 60 * 1000;
  const startTime = Date.now();


  console.log(`[@libs/shared-core] Triggering initial ping, just to check if the triggered workflow exists...`);

  let didArgoWorkflowSucceed = await checkArgoStatus();

  console.log(`[@libs/shared-core] Waiting for 5 minutes (min expected time)...`);
  await new Promise((resolve) => setTimeout(resolve, avgSetupDelayMs));

  while (true) {
    // This is enforced via CI, just adding as a fail-safe
    if (Date.now() - startTime >= timeoutMs) {
      console.error(
        '[@libs/shared-core] Fallback Timeout Hit! 60 minutes breached! Please retrigger the workflow!',
      );
      process.exit(1);
    }

    console.log(`[@libs/shared-core] Checking status...`);
    didArgoWorkflowSucceed = await checkArgoStatus();

    if (didArgoWorkflowSucceed) {
      console.log('[@libs/shared-core] Proceeding with the tests...');
      return;
    }

    console.log(`[@libs/shared-core] Retrying in ${intervalMinutes} minutes...`);
    await new Promise((resolve) => setTimeout(resolve, intervalMs));
  }
}

export const waitForInfraDeployment = async ({ intervalMinutes }: { intervalMinutes: number }) => {
  try {
    if (!process.env.ASSIGNED_ARGO_WORKFLOW_NAME || !process.env.ARGO_TOKEN) {
      throw new Error(
        '[@libs/dashboard-core]: Assigned Argo Workflow Name or Argo Token is missing in the environment',
      );
    }

    await initializePolling(intervalMinutes);
  } catch (error) {
    console.error('[@libs/shared-core] Error in waiting for infra health check:', error);
    process.exit(1);
  }
};
