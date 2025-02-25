const ENV = process.env;
const { execCommand } = require('./ci-utils');
const { existsSync, writeFileSync, appendFileSync } = require('fs');
const path = require('path');

/**
 * Ensures the directory structure exists.
 * @param {string} dirPath - The directory path to create if it doesn't exist.
 */
const ensureDirectoryExists = async (dirPath) => {
  if (!existsSync(dirPath)) {
    await execCommand(`mkdir -p ${dirPath}`);
  }
};

/**
 * Fetches JSON content from a URL using the Fetch API.
 * @param {string} url - The URL to fetch JSON data from.
 * @returns {Promise<Object>} - The parsed JSON data.
 */
const fetchJson = async (url) => {
  const response = await fetch(url);
  if (!response.ok) {
    throw new Error(`Request failed. Status Code: ${response.status}`);
  }
  return response.json();
};

/**
 * Processes the .last-run.json file from S3.
 * @param {string} s3Url - The S3 URL of the .last-run.json file.
 * @param {string} localPath - The local path to save the file.
 * @param {string} project - The name of the project.
 * @param {Array} fullRerunProjects - Accumulates projects that need a full rerun due to errors.
 * @param {Array} failedRerunProjects - Accumulates projects that failed in last-run.json.
 */
const processLastRunFile = async (
  s3Url,
  localPath,
  project,
  fullRerunProjects,
  failedRerunProjects
) => {
  try {
    const lastRunData = await fetchJson(s3Url);

    if (lastRunData.status === 'failed') {
      await ensureDirectoryExists(path.dirname(localPath));
      writeFileSync(localPath, JSON.stringify(lastRunData, null, 2));
      failedRerunProjects.push(project);
    } else {
      console.log(`Status for ${project} is not failed. Skipping.`);
    }
  } catch (err) {
    console.warn(`Error processing .last-run.json for ${project}:`, err);
    // If the fetch or processing fails, we opt for a full rerun
    fullRerunProjects.push(project);
  }
};

/**
 * Updates environment variables for GitHub Actions and the current process.
 * @param {Array} fullRerunProjects - List of projects that require a full rerun (API or other error).
 * @param {Array} failedRerunProjects - List of projects that had a failed status in .last-run.json.
 */
const updateEnvironmentVariables = (fullRerunProjects, failedRerunProjects) => {
  const fullRerun = fullRerunProjects.join(',');
  const failedRerun = failedRerunProjects.join(',');

  // Persist environment variables for GitHub Actions
  if (ENV.GITHUB_ENV) {
    const envUpdates = 
      `FILTERED_PROJECTS_FOR_FULL_RERUN=${fullRerun}\n` +
      `FILTERED_PROJECTS_FOR_FAILED_RERUN=${failedRerun}\n`;
    appendFileSync(ENV.GITHUB_ENV, envUpdates);
  }
};

(async () => {
  try {
    // First CLI argument: Comma-separated project names
    const targetProjects = process.argv.slice(2)[0].trim().split(',');
    // Second CLI argument: Comma-separated project root paths
    const targetProjectRoots = process.argv.slice(2)[1].trim().split(',');
    const runAttempt = parseInt(ENV.GITHUB_RUN_ATTEMPT || '1', 10);

    // If it's the first run, skip reading .last-run.json and do a full rerun of all projects
    if (runAttempt <= 1) {
      console.log('Workflow is on its first run. Setting a full rerun for all projects...');
      updateEnvironmentVariables(targetProjects, []);
      return;
    }

    // Ensure projects and project roots match
    if (targetProjects.length !== targetProjectRoots.length) {
      console.error('Project and Project Root Mismatch. Please check again.');
      // We likely want all projects to be full rerun if there's a mismatch
      updateEnvironmentVariables(targetProjects, []);
      return;
    }

    // Arrays to track our filtered projects
    const fullRerunProjects = [];
    const failedRerunProjects = [];

    // Process each project's last-run.json
    const promises = targetProjects.map((project, index) => {
      const s3Url = `https://dashboard-assets.np.razorpay.in/dashboard/code-integrity-suite/${
        ENV.COMMIT_SHA
      }/${project}/playwright-analysis/${runAttempt - 1}/run-files/.last-run.json`;
      const localPath = path.join(
        targetProjectRoots[index],
        '.playwright-analysis/run-files/.last-run.json'
      );

      return processLastRunFile(
        s3Url,
        localPath,
        project,
        fullRerunProjects,
        failedRerunProjects
      );
    });

    await Promise.all(promises);

    // Update environment variables based on the collected data
    if (fullRerunProjects.length > 0 || failedRerunProjects.length > 0) {
      console.log('Projects requiring reruns found.');
    } else {
      console.log('No projects with failed or error statuses found.');
    }

    updateEnvironmentVariables(fullRerunProjects, failedRerunProjects);
  } catch (error) {
    console.error('Unexpected error occurred:', error);
    // If something breaks unexpectedly, put all targetProjects in the full rerun.
    const allProjects = process.argv.slice(2)[0].trim().split(',');
    updateEnvironmentVariables(allProjects, []);
  }
})();
