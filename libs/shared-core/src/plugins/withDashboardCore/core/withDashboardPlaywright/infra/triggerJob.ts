type TriggerJobPayload = Record<string, unknown>;

export const triggerJob = async (payload: TriggerJobPayload): Promise<void> => {
  const url: string | undefined =
    'https://argo.dev.razorpay.in/api/v1/events/argo-workflows-test/dashboard-fe-e2e-pr-1102';

  // TODO: Remove this once the argo changes are merged
  // const url: string | undefined =
  // 'https://argo.dev.razorpay.in/api/v1/events/argo-workflows/dashboard-fe-e2e';

  try {
    if (!process.env.ASSIGNED_ARGO_WORKFLOW_NAME || !process.env.ARGO_TOKEN) {
      throw new Error(
        '[@libs/dashboard-core]: Assigned Argo Workflow Name or Argo Token is missing in the environment',
      );
    }

    console.log('[@libs/dashboard-core]: Triggering Job With Payload:', payload);

    const apiCall: Response = await fetch(url, {
      method: 'POST',
      body: JSON.stringify(payload),
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${process.env.ARGO_TOKEN}`,
      },
    });

    if (!apiCall.ok) {
      throw new Error('[@libs/dashboard-core]: Job Trigger Failed');
    }

    console.log('[@libs/dashboard-core]: Job Trigger Succeeded');
    console.log(
      `Argo Workflow Link: https://argo.dev.razorpay.in/workflows/argo-workflows-test/${process.env.ASSIGNED_ARGO_WORKFLOW_NAME}?tab=workflow`,
    );
  } catch (error) {
    console.error(error);
    process.exit(1);
  }
};
