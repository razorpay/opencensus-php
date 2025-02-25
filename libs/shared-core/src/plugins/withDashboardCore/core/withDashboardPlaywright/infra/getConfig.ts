export const getConfig = () => {
  const repository = process.env.GITHUB_REPOSITORY;
  const repoName = process.env.REPO_NAME;
  const pullNumber = process.env.PR_NUMBER;
  const author = process.env.GITHUB_ACTOR?.toLowerCase();
  const selfCommit = process.env.COMMIT_ID;
  const assigneddevstackLabel = process.env.ASSIGNED_DEVSTACK_LABEL;
  const affectedProjects = JSON.parse(process.env.AFFECTED_PROJECTS || '[]');
  const intervalMinutes = +`${process.env.WAIT_FOR_INFRA_DEPLOYMENT_POLL_INTERVAL_MIN}`;
  const servicesTTL = process.env.SERVICES_TTL;

  return {
    repository,
    repoName,
    pullNumber,
    author,
    selfCommit,
    affectedProjects,
    assigneddevstackLabel,
    intervalMinutes,
    servicesTTL,
  };
};
