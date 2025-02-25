type NxAffectedList = string[];

interface GenerateDashboardValuesParams {
  selfCommit: string;
  nxAffectedList: NxAffectedList;
}

interface Versions {
  [key: string]: string;
}

interface DashboardValues {
  server: {
    [key: string]: string | undefined;
  };
  browser: {
    [key: string]: string | undefined;
  };
}

export const generateDashboardValues = async ({
  // TODO: Temp till devstack changes are finalized
  selfCommit,
  nxAffectedList,
}: GenerateDashboardValuesParams): Promise<DashboardValues> => {

  try {
    const [appVersionsResponse, commitIdResponse] = await Promise.all([
      fetch('https://dashboard.razorpay.com/app-versions', {
        headers: { Cookie: 'dashboard_legacy=false' },
      }),
      fetch('https://dashboard.razorpay.com/commit.txt'),
    ]);

    if (!appVersionsResponse.ok || !commitIdResponse.ok) {
      throw new Error(
        `Failed to fetch data. Statuses => 
        appVersions: ${appVersionsResponse.status}, 
        commitId: ${commitIdResponse.status}`,
      );
    }

    const coreAppVersions = (await appVersionsResponse.json()) as Versions;
    const phpDeployedCommit = (await commitIdResponse.text()).replace(/\n/g, '');

    const dashboardAppVersions: Versions = {
      ...coreAppVersions,
      // For now PHP will be build regradless.
      // TODO: Add project.json to PHP and remove this
      php: selfCommit,
    };

    nxAffectedList.forEach((project) => {
      if (project === 'shell') {
        dashboardAppVersions['shell-server'] = selfCommit;
      }
      if (dashboardAppVersions[project]) {
        dashboardAppVersions[project] = selfCommit;
      }
    });

    const server = {
      php: selfCommit,
      'shell-server': nxAffectedList.includes('shell')
        ? selfCommit
        : dashboardAppVersions['shell-server'],
    };

    const { php, 'shell-server': shellServer, ...browser } = dashboardAppVersions;

    const dashboardHelmChartValues = {
      server,
      browser,
    };

    console.log('[@libs/shared-core]', dashboardHelmChartValues);

    return dashboardHelmChartValues;
  } catch (error) {
    console.error('Error while fetching dashboard commits', error);
    process.exit(1);
  }
};

export type { GenerateDashboardValuesParams, Versions, DashboardValues };
