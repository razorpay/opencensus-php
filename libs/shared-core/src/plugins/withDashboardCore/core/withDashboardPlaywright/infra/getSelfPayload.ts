import { generateDashboardValues } from './generateDashboardValues';

export const getSelfPayload = async ({ repoName, selfCommit, affectedProjects }: any) => {
  const dashboardChartValues = await generateDashboardValues({
    selfCommit,
    nxAffectedList: affectedProjects,
  });

  const payload = {
    name: repoName,
    chart_values: {
      web_requests_memory: '350Mi',
      web_requests_cpu: '500m',
      replicas: 3,
      dashboard: dashboardChartValues,
    },
  };

  return payload;
};
