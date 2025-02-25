const ENV = process.env;
const { existsSync, mkdirSync, readFileSync, writeFileSync, rmSync, renameSync } = require('fs');
const { resolve, join } = require('path');
const glob = require('glob');
const { execCommand, moveFile } = require('./ci-utils');
const { endGroup, startGroup } = require('./grouped-steps');
const { createCoverageMap } = require('istanbul-lib-coverage');
const { createContext } = require('istanbul-lib-report');
const istanbulReports = require('istanbul-reports');
const { Builder } = require('xml2js');

const TEMP_DIR = resolve('./.jest-temp');
const OUTPUT_DIR = resolve('./.jest-analysis');

async function downloadShards(bucket, project, commitSha) {
  startGroup('Download shards from S3');
  const cacheDir = resolve(TEMP_DIR, '.jest-cache');
  mkdirSync(cacheDir, { recursive: true });

  const s3Path = `s3://${bucket}/dashboard/code-integrity-suite/${commitSha}/${project}/.jest-cache`;
  await execCommand(`aws s3 cp --recursive ${s3Path} ${cacheDir} --only-show-errors`);

  endGroup();
}

async function mergeCoverageJSONShards() {
  startGroup(`Combine coverage-final.json shards`);

  console.log('Finding coverage shards...');
  const patternForShardedCoverageFiles = resolve(
    TEMP_DIR,
    `.jest-cache/jest-analysis-*/coverage-final.json`,
  );
  const outputFile = resolve(OUTPUT_DIR, 'coverage-final.json');

  const shardFiles = glob.sync(resolve(patternForShardedCoverageFiles));

  if (shardFiles.length === 0) {
    console.error('No coverage shards found.');
    process.exit(1);
  }

  console.log('Found coverage shards:', shardFiles);

  const combinedCoverageMap = createCoverageMap({});

  shardFiles.forEach((shardFile) => {
    console.log(`Merging coverage from: ${shardFile}`);
    const shardData = JSON.parse(readFileSync(shardFile, 'utf8'));
    const shardMap = createCoverageMap(shardData);
    combinedCoverageMap.merge(shardMap);
  });

  console.log('Writing combined coverage file...');
  writeFileSync(outputFile, JSON.stringify(combinedCoverageMap.toJSON(), null, 2));
  console.log(`Combined coverage file saved at: ${outputFile}`);
  endGroup();
  return { combinedCoverageMap };
}

function combineMainReportShards() {
  const reportName = 'main-report.json';
  startGroup(`Combine ${reportName}`);

  const shardReports = glob.sync(resolve(TEMP_DIR, `.jest-cache/jest-analysis-*/${reportName}`));
  if (shardReports.length === 0) {
    console.warn(`No JSON reports found for ${reportName}`);
    endGroup();
    return;
  }

  const combined = shardReports.reduce((acc, file) => {
    const content = JSON.parse(readFileSync(file, 'utf-8'));
    return Object.assign(acc, content);
  }, {});

  writeFileSync(resolve(OUTPUT_DIR, reportName), JSON.stringify(combined, null, 2));
  console.log(`Combined ${reportName} saved at ${OUTPUT_DIR}`);

  endGroup();
}

async function mergeLcovFiles() {
  startGroup(`Merge LCOV files via lcov-result-merger`);

  const patternForShardedLcov = resolve(TEMP_DIR, `.jest-cache/jest-analysis-*/lcov.info`);

  // Gather the shard LCOV files
  const shardReports = glob.sync(resolve(patternForShardedLcov));

  if (shardReports.length === 0) {
    console.warn('No LCOV files found to merge.');
    endGroup();
    return;
  } else {
    console.log('Found sharded LCOV files:', shardReports);
  }

  try {
    await execCommand(`npx lcov-result-merger '${patternForShardedLcov}' --legacy-temp-file`);

    const generatedFile = resolve('lcov.info');

    if (existsSync(generatedFile)) {
      console.log('Merged LCOV file saved at', generatedFile);
      moveFile(generatedFile, resolve(OUTPUT_DIR, 'lcov.info'));
    } else {
      console.log('Merged LCOV file not found.');
      process.exit(1);
    }
  } catch (error) {
    console.error('Error merging LCOV files:', error.message);
    process.exit(1);
  }

  endGroup();
}

async function generateCloverReport(coverageMap) {
  startGroup('Generate Clover report');

  try {
    console.log('Starting Clover report generation process...');

    const outputDir = resolve(OUTPUT_DIR);

    await new Promise((resolve, reject) => {
      try {
        console.log('Initializing report context...');
        const context = createContext({ dir: outputDir, coverageMap });

        console.log('Setting up Clover report...');
        const cloverReport = istanbulReports.create('clover', { file: 'clover.xml' });

        console.log('Executing report generation...');
        cloverReport.execute(context);

        resolve();
      } catch (err) {
        reject(err);
      }
    });

    const reportPath = join(outputDir, 'clover.xml');
    console.log(`Clover report generated successfully at: ${reportPath}`);
  } catch (err) {
    console.error('Error during Clover report generation:', err);
    endGroup();
    process.exit(1);
  } finally {
    console.log('Clover report generation process completed.');
    endGroup();
  }
}

async function generateLcovHtmlReport(coverageMap) {
  startGroup(`Generate LCOV HTML report`);

  try {
    console.log('Starting Clover report generation process...');
    const outputDir = resolve(OUTPUT_DIR, 'lcov-report');

    await new Promise((resolve, reject) => {
      try {
        console.log('Initializing report context...');
        const context = createContext({ dir: outputDir, coverageMap });

        console.log('Setting up report...');
        const cloverReport = istanbulReports.create('html');

        console.log('Executing report generation...');
        cloverReport.execute(context);

        resolve();
      } catch (err) {
        reject(err);
      }
    });

    console.log(`LCOV HTML report generated successfully at: ${outputDir}`);
  } catch (err) {
    console.error('Error during LCOV HTML report generation:', err);
    endGroup();
    process.exit(1);
  } finally {
    console.log('LCOV HTML report generation process completed.');
    endGroup();
  }
}

// TODO: Implement Jest HTML report generation (For Sharded Jest Reports, till then rely on CI outputs)
async function generateJestHtmlReport() {
  startGroup(`Generate Jest HTML report`);
  endGroup();
}

function generateSonarXmlFromCoverageMap(coverageMap) {
  startGroup(`Generate SonarQube XML report`);
  console.log('Building SonarQube XML...');

  const outputFile = resolve(OUTPUT_DIR, 'sonar-report.xml');

  const coverage = { coverage: { file: [] } };

  coverageMap.files().forEach((filePath) => {
    const fileCoverage = coverageMap.fileCoverageFor(filePath);
    const fileElement = {
      $: { path: filePath },
      lineToCover: [],
    };

    Object.entries(fileCoverage.statementMap).forEach(([statementId, statement]) => {
      const lineNumber = statement.start.line;
      const hits = fileCoverage.s[statementId];
      fileElement.lineToCover.push({
        $: { lineNumber, covered: hits > 0 ? 'true' : 'false' },
      });
    });

    coverage.coverage.file.push(fileElement);
  });

  const builder = new Builder();
  const xml = builder.buildObject(coverage);

  writeFileSync(outputFile, xml, 'utf8');
  console.log(`SonarQube XML report generated at: ${outputFile}`);
  endGroup();
}

function processSonarReport() {
  startGroup(`Process Sonar report`);

  console.log('Processing sonar-report.xml from shards...');
  // Placeholder for your future Sonar analysis or merging logic

  endGroup();
}

function checkIfCoverageThresholdWasMet() {}

async function main() {
  // checkDependencies(); // optionally re-enable if you want to ensure tools exist

  try {
    // Expect user to pass 2 CLI args: "bucket" and "projects"
    // e.g. node analysis.js my-bucket "project1,project2"
    const [targetBucket, targetProjects] = process.argv.slice(2).map((arg) => arg.trim());
    if (!targetBucket || !targetProjects) {
      throw new Error('Bucket or project not specified.');
    }

    const projects = targetProjects.split(',');

    // Clean and recreate temp & output dirs
    rmSync(TEMP_DIR, { recursive: true, force: true });
    rmSync(OUTPUT_DIR, { recursive: true, force: true });
    mkdirSync(TEMP_DIR, { recursive: true });
    mkdirSync(OUTPUT_DIR, { recursive: true });

    for (const project of projects) {
      console.log(`Processing project: ${project}`);
      await downloadShards(targetBucket, project, ENV.COMMIT_SHA);

      combineMainReportShards();

      const { combinedCoverageMap } = await mergeCoverageJSONShards();

      await mergeLcovFiles();
      await generateCloverReport(combinedCoverageMap);
      await generateLcovHtmlReport(combinedCoverageMap);
      // await generateJestHtmlReport();
      generateSonarXmlFromCoverageMap(combinedCoverageMap);
      processSonarReport();

      // Upload everything to S3
      startGroup(`Upload results to S3`);
      const uploadCmd = `aws s3 cp --recursive "${OUTPUT_DIR}" "s3://${targetBucket}/dashboard/code-integrity-suite/${ENV.COMMIT_SHA}/${project}/jest-analysis"`;
      await execCommand(uploadCmd);
      endGroup();
    }
  } catch (error) {
    console.error('Error:', error);
    process.exit(1);
  } finally {
    startGroup(`Execute Clean Up`);

    rmSync(TEMP_DIR, { recursive: true, force: true });
    rmSync(OUTPUT_DIR, { recursive: true, force: true });

    endGroup();
  }
}

main();
