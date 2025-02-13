const fs = require('fs');
const path = require('path');

const SONAR_HOST = process.env.SONAR_HOST;
const PROJECT_KEY_DEV = process.env.PROJECT_KEY_DEV;
const SONAR_TOKEN = process.env.SONAR_TOKEN;
const FILE_HEADING = process.env.FILE_HEADING;
const FILE_NAME = process.env.FILE_NAME;

const CURL_URL_DEV_QUALITY_GATE = `${SONAR_HOST}/api/qualitygates/project_status?projectKey=${PROJECT_KEY_DEV}`;

const filePath = path.resolve(__dirname, FILE_NAME);

function appendToFile(content) {
  fs.appendFileSync(filePath, content, 'utf8');
}

function processQualityGateMetrics(qualityGateData) {
  const { status: qualityGateStatus, conditions } = qualityGateData;

  const newCodeCoverageMetric = conditions?.find(
    (condition) => condition.metricKey === 'new_coverage',
  );

  appendToFile(`#### ${FILE_HEADING}\n`);
  appendToFile(`\n`);
  appendToFile(`| Metric | Value |\n`);
  appendToFile(`| Quality Gate Status | ${qualityGateStatus} |\n`);

  if (newCodeCoverageMetric) {
    const { actualValue, status } = newCodeCoverageMetric;
    console.log('New Code Coverage:', actualValue, status);

    appendToFile(`| New Code Coverage | ${actualValue} |\n`);

    if (status === 'ERROR') {
      console.error('New code coverage below threshold');
      return process.exit(1);
    }
  }

  if (qualityGateStatus === 'ERROR') {
    console.error('Quality gate metrics below threshold', conditions);
    return process.exit(1);
  }

  return process.exit(0);
}

function fetchSonarData() {
  return fetch(CURL_URL_DEV_QUALITY_GATE, {
    method: 'GET',
    headers: {
      Authorization: `Basic ${Buffer.from(`${SONAR_TOKEN}:`).toString('base64')}`,
    },
  })
    .then((response) => {
      return response.json();
    })
    .then((data) => {
      return processQualityGateMetrics(data?.projectStatus);
    })
    .catch((error) => {
      console.error('Error:', error);
    });
}

fetchSonarData();
