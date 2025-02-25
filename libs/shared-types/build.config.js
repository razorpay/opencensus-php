const esbuild = require('esbuild');
const { dts } = require('rollup-plugin-dts');
const { rollup } = require('rollup');
const fs = require('fs').promises;
const { execCommand } = require('@libs/shared-core');

// List of entry points and their corresponding output files
const entries = [
  { entry: './src/common/index.ts', outfile: './dist/index.js' },
  { entry: './src/dashboards/la/index.ts', outfile: './dist/la.js' },
  { entry: './src/dashboards/payments/index.ts', outfile: './dist/payments.js' },
];

// Function to build all entry points using esbuild
async function buildJavaScript() {
  await Promise.all(
    entries.map(async (entry) => {
      await esbuild.build({
        entryPoints: [entry.entry], // Entry point
        outfile: entry.outfile, // Output file
        bundle: true, // Bundle all dependencies
        format: 'esm', // ESM format only
        target: 'esnext', // Target latest ECMAScript
        minify: false, // Enable minification if needed
        sourcemap: false, // Enable source maps if needed,
      });
      console.log(`Bundled: ${entry.outfile}`);
    }),
  );

  console.log('JavaScript bundling completed for all entry points.');
}

// Function to generate TypeScript declarations using tsc ->
async function generateDeclarations() {
  try {
    // Await the execCommand which returns a promise
    await execCommand(`tsc --project tsconfig.lib.json --emitDeclarationOnly --incremental false`);
    console.log('Declaration files generated');
  } catch (error) {
    console.error('Error generating .d.ts files:', error);
    throw error;
  }
}

// Function to clean up directories
async function cleanupDirectories() {
  try {
    const dirsToDelete = ['./dist/common', './dist/dashboards'];

    await Promise.all(
      dirsToDelete.map(async (dir) => {
        const dirPath = dir;
        await fs.rm(dirPath, { recursive: true, force: true });
        console.log(`Deleted: ${dir}`);
      }),
    );
  } catch (error) {
    console.error('Error during directory cleanup:', error);
  }
}

// Function to bundle declarations using rollup-plugin-dts
async function bundleDeclarations() {
  const dtsConfig = [
    // For common
    {
      input: './dist/common/index.d.ts',
      output: {
        file: './dist/index.d.ts',
        format: 'es',
      },
    },
    // For dashboards/la
    {
      input: './dist/dashboards/la/index.d.ts',
      output: {
        file: './dist/la.d.ts',
        format: 'es',
      },
    },
    // For dashboards/payments ->
    {
      input: './dist/dashboards/payments/index.d.ts',
      output: {
        file: './dist/payments.d.ts',
        format: 'es',
      },
    },
  ];

  for (const config of dtsConfig) {
    const bundle = await rollup({
      input: config.input,
      plugins: [dts({
        compilerOptions:{
          incremental:false
        }
      })],
    });

    await bundle.write({
      file: config.output.file,
      format: config.output.format,
    });

    console.log(`Bundled: ${config.output.file}`);
  }

  console.log('TypeScript declaration bundling completed.');
}

// Main build process
(async function () {
  try {
    // Step 1: Build JavaScript files using esbuild
    await buildJavaScript();

    // Step 2: Generate TypeScript declaration files (.d.ts)
    await generateDeclarations();

    // Step 3: Bundle TypeScript declaration files using rollup-plugin-dts
    await bundleDeclarations();

    await cleanupDirectories();

    console.log('Build process completed successfully.');
    process.exit(0);
  } catch (error) {
    console.error('Build process failed:', error);
    process.exit(1);
  }
})();
