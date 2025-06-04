import { generateDashboardValues } from './generateDashboardValues';

export const getSelfPayload = async ({ repoName, selfCommit, affectedProjects }: any) => {
  const dashboardChartValues = await generateDashboardValues({
    selfCommit,
    nxAffectedList: affectedProjects,
  });

  const payload = {
    name: repoName,
    chart_values: {
      dashboard_shell_requests_memory: '400Mi',
      dashboard_shell_requests_cpu: '400m',
      dashboard_shell_limits_memory: '500Mi',
      dashboard_shell_replicas: 3,
      dashboard_requests_memory: '400Mi',
      dashboard_requests_cpu: '400m',
      dashboard_limits_memory: '500Mi',
      dashboard_replicas: 3,
      dashboard: dashboardChartValues,
    },
  };

  return payload;
};
